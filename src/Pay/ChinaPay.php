<?php
namespace Zodream\ThirdParty\Pay;

use Exception;
use Zodream\Http\Http;

/**
 * 银联电子支付
 * 私钥是在网银控制台里申请，然后从浏览器的 证书管理里导出 并设置私钥密码 .pfx
 * 公钥是给的 .cer
 * 在通知url中的参数不参与签名 请加入忽略项中
 * @package Zodream\ThirdParty\Pay
 *
 */
class ChinaPay extends BasePay {

    protected string $configKey = 'chinapay';

    protected string $signKey = 'Signature';

    protected $privateKey = [];

    protected $publicKey = '';

    /**
     * 私钥密码
     * @var array|string
     */
    protected $password = '';

    /**
     * 证书编号
     * @var
     */
    protected $publicCERCertId;

    /**
     * 私钥证书编号
     * @var
     */
    protected $privatePFXCertId;

    protected array $ignoreKeys = [
        'Signature',
        'CertId'
    ];

    /**
     * ChinaPay constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config = array()) {
        parent::__construct($config);
        $this->password = $this->get('password', '');
        $this->init();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function init() {
        if (!empty($this->privateKey)) {
            return $this->privateKey;
        }
        $merPkCs12 = $this->getPrivateKeyFile()->read();
        $pkcs12 = openssl_pkcs12_read($merPkCs12, $this->privateKey, $this->password);
        if (!$pkcs12) {
            throw new Exception('解析pfx证书内容错误');
        }
        $x509data = $this->privateKey['cert'];
        if (!openssl_x509_read($x509data)) {
            throw new Exception('读取pfx证书公钥错误');
        }
        $certdata = openssl_x509_parse($x509data);
        if (empty($certdata)) {
            throw new Exception('解析pfx证书公钥成功，但解析证书错误');
        }
        $this->privatePFXCertId = $certdata['serialNumber'];
        //解析pfx证书公钥成功，证书编号
        $this->publicKey = $this->getPublicKeyFile()->read();
        if (empty($this->publicKey)) {
            throw new Exception('读取CP公钥证书文件失败');
        }
        $pk = openssl_pkey_get_public($this->publicKey);
        $a = openssl_pkey_get_details($pk);
        $certdata = openssl_x509_parse($this->publicKey, false);
        if (empty($certdata)) {
            throw new Exception('解析CP证书公钥成功，但解析证书错误');
        }
        $this->publicCERCertId = $certdata['serialNumber'];
    }

    public function getDeclareOrder() {
        return $this->getBaseHttp('https://gateway.95516.com/gateway/api/backTransReq.do')
            ->maps([
                'version' => '5.1.0',		      //版本号
                'encoding' => 'utf-8',		      //编码方式
                'signMethod' => '01',		      //签名方法
                'txnType' => '82',		          //交易类型
                'txnSubType' => '00',		      //交易子类
                'bizType' => '000000',		      //业务类型
                'accessType' => '0',		      //接入类型
                'channelType' => '07',		      //渠道类型
                'currencyCode' => '156',          //交易币种，境内商户勿改

                '#merId',		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
                '#orderId',	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
                '#txnTime',	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
                '#txnAmt',	//交易金额，单位分，此处默认取demo演示页面传递的参数
                '#origOrderId',	//原交易订单号，取原消费/预授权完成的orderId
                '#origTxnTime',	//原交易订单发送时间。取原消费/预授权完成的txnTime

                '#customsData',     //海关信息域，按规范填写
                'customerInfo' => [
                    'certifTp' => '01', //证件类型，01-身份证
                    '#certifId', //证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
                    '#customerNm', //姓名
                ], //持卡人身份信息
            ])->encode([$this, 'encodeSign']);
    }

    public function getRefund() {
        return $this->getBaseHttp('https://gateway.95516.com/gateway/api/backTransReq.do')
            ->maps([
                'version' => '5.1.0',		      //版本号
                'encoding' => 'UTF-8',		      //编码方式
                'bizType' => '000000',		      //业务类型

                'txnTime' => date('YmdHis'),
                '#backUrl',
                '#txnAmt',   // 以分为单位
                'txnType' => '04',
                'txnSubType' => '00',
                'accessType' => 0,
                'signature',
                'signMethod' => '01',
                '#channelType',
                '#merId',
                '#orderId',
                '#origQryId',

                'subMerId',
                'subMerAbbr',
                'subMerName',
                'certId',
                'reserved',
                'reqReserved',
                'termId'
            ])->encode([$this, 'encodeSign'])
            ->decode(function ($data) {
                $res = [];
                parse_str($data, $res);
                return $res;
            });
    }

    /**
     * 签名
     * @param array|string $content
     * @return string
     * @throws \Exception
     * @internal param array|string $args
     */
    public function sign($content) {
        if (is_array($content)) {
            $content = $this->getSignContent($content);
        }
        $sign_flag = openssl_sign($content,
            $signature,
            $this->privateKey['pkey'],
            OPENSSL_ALGO_SHA512);
        if (!$sign_flag) {
            throw new \Exception('签名失败！');
        }
        return base64_encode($signature);
    }

    protected function getSignContent(array $params) {
        ksort($params);
        $args = [];
        foreach ($params as $key => $item) {
            if (Http::isEmpty($item)
                || in_array($key, $this->ignoreKeys)
            ) {
                continue;
            }
            $args[] = $key.'='.$item;
        }
        return implode('&', $args);
    }

    /**
     * @param array $args
     * @param $sign
     * @return bool
     * @throws \Exception
     */
    public function verify(array $args, $sign = null) {
        if (is_null($sign)) {
            $sign = $args[$this->signKey];
        }
        $verifySignData = $this->sign($args);
        $result = openssl_verify($verifySignData, base64_decode($sign),
            $this->publicKey, OPENSSL_ALGO_SHA512);
        if ($result == 1) {
            return true;
        }
        throw new \Exception($result == 0 ? '验签失败' : '验签过程发生错误');
    }

    /**
     * 数组转换成字符串
     * @param array $args
     * @return string
     */
    public function getEncodeArray(array $args) {
        $data = [];
        foreach ($args as $key => $item) {
            $data[] = sprintf('%s=%s', urlencode($key), urlencode($item));
        }
        return sprintf('{%s}', implode('&', $data));
    }

    /**
     * 生成提交表单
     * @param array $args
     * @param string $buttonTip
     * @return string
     * @throws Exception
     */
    public function oldForm(array $args, $buttonTip = '立即使用银联支付') {
        $data = $this->encodeSign(Http::getMapParameters([
            'Version' => '20140728',
            'AccessType',// => '0',
            'InstuId',
            'AcqCode',
            '#MerId',
            '#MerOrderNo',
            '#TranDate',// => date('Ymd'),
            '#TranTime',// => date('His'),
            '#OrderAmt',// => $order['order_amount'] * 100, 以分为单位
            'TranType' => '0001',  //0001个人网银支付 0002企业网银支付 0003授信交易 0004快捷支付 0005账单支付、 ChinaPay手机控件支付 0006认证支付 0007分期付款 0008后台支付 0201预授权交易

            'BusiType' => '0001',
            'CurryNo',// => 'CNY',
            'SplitType', //0001：实时分账 0002：延时分账
            'SplitMethod', //0：按金额分账 1：按比例分账
            'MerSplitMsg',
            'BankInstNo',

            '#MerPageUrl',// => $pageUrl,
            '#MerBgUrl',// => $bgUrl,
            'CommodityMsg',

            'MerResv' => 'chinapay',
            'TranReserved',
            'CardTranData',
            'PayTimeOut',
            'TimeStamp', // YmdHis
            'RiskData',
            'Signature',
            '#RemoteAddr'// => real_ip()
        ], $this->merge($args)));

        $button ='<form action="https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0" method="POST" target="_blank">';// （这里action的内容为提交交易数据的URL地址）
        foreach ($data as $key => $item) {
            $button .= '<input type="hidden" name="'.$key.'" value="'.$item.'">';
        }
        return $button.'<input type="submit" value="'.$buttonTip.'"/></form>';
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function callback() {
        $args = [];
        foreach ($_POST as $key => $value) {
            $args[$key] = urldecode($value);
        }
        if (!$this->verify($args)) {
            throw new Exception('验签失败！');
        }
        if ($args['OrderStatus'] != '0000') {
            throw new Exception('支付失败');
        }
        return $args;
    }

    /**
     * 报关
     * @param array $args
     * @return array|mixed
     * @throws Exception
     */
    public function declareOrder(array $args = array()) {
        $args = $this->getDeclareOrder()
            ->parameters($this->merge($args))->json();
        // 签名和验签方法不一样，要改
        if (!$this->verify($args)) {
            throw new Exception('数据验签失败！');
        }
        if ($args['respCode'] != '01') {
            return $args;
        }
        if ($args["respCode"] == "03"
            || $args["respCode"] == "04"
            || $args["respCode"] == "05") {
            throw new Exception('处理超时，请稍后查询');
        }
        throw new Exception($args['respMsg']);
    }

    public function refund(array $args = array()) {
        $args = $this->getRefund()
            ->parameters($this->merge($args))->text();
        // 签名和验签方法不一样，要改
        if (!$this->verify($args)) {
            throw new Exception('数据验签失败！');
        }
        if ($args['respCode'] != '00') {
            return $args;
        }
        if ($args["respCode"] == "03"
            || $args["respCode"] == "04"
            || $args["respCode"] == "05") {
            throw new Exception('处理超时，请稍后查询');
        }
        throw new Exception($args['respMsg']);
    }
}