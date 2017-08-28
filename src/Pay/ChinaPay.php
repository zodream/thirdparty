<?php
namespace Zodream\ThirdParty\Pay;

use Exception;

/**
 * 银联电子支付
 * 私钥是在网银控制台里申请，然后从浏览器的 证书管理里导出 并设置私钥密码 .pfx
 * 公钥是给的 .cer
 * @package Zodream\ThirdParty\Pay
 *
 */
class ChinaPay extends BasePay {

    protected $configKey = 'chinapay';

    protected $signKey = 'Signature';

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

    protected $ignoreKeys = [
        'Signature',
        'CertId'
    ];

    protected $apiMap = [
        'form' => [
            'https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0',
            [
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
            ]
        ]
    ];

    public function __construct(array $config = array()) {
        parent::__construct($config);
        $this->password = $this->get('password', '');
        $this->init();
    }

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
            if ($this->checkEmpty($item)
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
     * 生成提交表单
     * @param array $args
     * @return string
     */
    public function form(array $args) {
        $args[$this->signKey] = $this->getSignData('form', $args);

        $button ='<form action="'.$this->apiMap['form'][0].'" method="POST" target="_blank">';// （这里action的内容为提交交易数据的URL地址）
        foreach ($args as $key => $item) {
            $button .= '<input type="hidden" name="'.$key.'" value="'.$item.'">';
        }
        return $button.'<input type="submit" value="立即使用银联支付"/>
       </form>';
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
}