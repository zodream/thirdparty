<?php
namespace Zodream\ThirdParty\Pay;
/**
 * URL: https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_1
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/18
 * Time: 19:07
 */
use Zodream\Http\Http;
use Zodream\Image\Image;
use Zodream\Image\QrCode;
use Zodream\Disk\File;
use Zodream\Helpers\Str;
use Zodream\Helpers\Xml;
use Zodream\Infrastructure\Http\Request;
use Zodream\Http\Uri;
use Zodream\Service\Factory;

class WeChat extends BasePay {
    /**
     * EXAMPLE: 
     * 'wechat' => array(
            'appid' => '',
            'mch_id' => '',
            'notify_url' => '',
            'trade_type' => 'APP'
        )
     * @var string
     */
    protected $configKey = 'wechat';

    protected $ignoreKeys = ['sign'];

    public function getBaseHttp($url = null) {
        $arg = Str::random(32);
        $this->set([
            'noncestr' => $arg,
            'nonce_str' => $arg
        ]);
        return parent::getBaseHttp($url)->encode([$this, 'encodeXml']);
    }

    /**
     * 统一下单
     * @return Http
     */
    public function getOrder() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/pay/unifiedorder')
            ->maps([
                '#appid',
                '#mch_id',
                '#device_info',
                '#nonce_str',
                '#body',
                'detail',
                'attach',
                '#out_trade_no',
                'fee_type',
                '#total_fee',   //以分为单位
                '#spbill_create_ip',
                'time_start',
                'time_expire',
                'goods_tag',
                '#notify_url',  //不能带参数
                '#trade_type',
                'limit_pay',
                'sign',
                'openid', // JSAPI必须
                'product_id'  //NATIVE 必须
            ]);
    }

    /**
     * app调起支付参数
     * @return array
     */
    public function getAppPay() {
        $data = Http::getMapParameters([
            '#appid',
            '#mch_id:partnerid',
            '#prepay_id:prepayid',
            'package' => 'Sign=WXPay',
            '#noncestr',
            '#timestamp',
            'sign'
        ], $this->get());
        return $this->encodeXml($data);
    }

    /**
     * app支付结果通用通知商户处理后同步返回给微信参数：
     * @return array
     */
    public function getAppReturn() {
        return [
            'return_code' => 'SUCCESS',   //SUCCESS/FAIL
            'return_msg' => 'OK'
        ];
    }

    public function getQuery() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/pay/orderquery')
            ->maps([
                '#appid',
                '#mch_id',
                [
                    'transaction_id', // 二选一
                    'out_trade_no'
                ],
                '#nonce_str',
                'sign'
            ]);
    }

    public function getClose() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/pay/closeorder')
            ->maps([
                '#appid',
                '#mch_id',
                '#out_trade_no',
                '#nonce_str',
                'sign'
            ]);
    }

    public function getRefund() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/secapi/pay/refund')
            ->maps([
                '#appid',
                '#mch_id',
                'device_info',
                '#nonce_str',
                'sign',
                [
                    'transaction_id',
                    'out_trade_no',
                ],
                '#out_refund_no',
                '#total_fee',
                '#refund_fee',
                'refund_fee_type',
                '#op_user_id'
            ]);
    }

    public function getQueryRefund() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/pay/refundquery')
            ->maps([
                '#appid',
                '#mch_id',
                'device_info',
                '#nonce_str',
                'sign',
                [
                    'transaction_id',  //四选一
                    'out_trade_no',
                    'out_refund_no',
                    'refund_id'
                ]
            ]);
    }

    public function getBill() {
        return $this->getBaseHttp()
            ->url('https://api.mch.weixin.qq.com/pay/downloadbill', [
                '#appid',
                '#mch_id',
                'device_info',
                '#nonce_str',
                'sign',
                '#bill_date',
                'bill_type'
            ]);
    }

    /**
     * 生成支付二维码
     * @return Http
     */
    public function getPayQr() {
        return $this->getBaseHttp()
            ->url('weixin://wxpay/bizpayurl',
                [
                    '#appid', // 微信分配的公众账号ID
                    '#mch_id',
                    '#time_stamp',
                    '#nonce_str',
                    '#product_id',
                    'sign'
                ]);
    }

    /**
     * 二维码支付回调输出返回
     * @return array
     */
    public function getQrReturn() {
        $data = Http::getMapParameters([
            'return_code' => 'SUCCESS',
            'return_msg',
            '#appid',
            '#mch_id',
            '#nonce_str',
            '#prepay_id',
            'result_code' => 'SUCCESS',
            'err_code_des',
            'sign'
        ], $this->get());
        return $this->encodeXml($data);
    }

    /**
     * 先生成预支付订单再生成二维码
     * @return Http
     */
    public function getOrderQr() {
        return $this->getBaseHttp()
            ->url('weixin://wxpay/bizpayurl', [
                'qr'
            ]);
    }

    /**
     * 公众号支付 网页端调起支付API
     * @return array
     */
    public function getJsApi() {
        return Http::getMapParameters([
            '#appId',
            '#timeStamp',
            '#nonceStr',
            '#package' => [
                '#prepay_id'
            ],
            'signType' => 'MD5',
            'paySign'
        ], $this->get());
    }

    /**
     * 海关申报
     * @return Http
     */
    public function getDeclareOrder() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder')
            ->maps([
                'sign',
                '#appid',
                '#mch_id',
                '#out_trade_no',
                '#transaction_id',
                '#customs',
                'mch_customs_no',
                'duty',
                //拆单或重新报关时必传
                'sub_order_no',
                'fee_type',
                'order_fee',
                'transport_fee',
                'product_fee',
                //微信缺少用户信息时必传，如果商户上传了用户信息，则以商户上传的信息为准,
                'cert_type',
                'cert_id',
                'name'
            ]);
    }

    /**
     * 企业付款给个人微信零钱
     * @return Http
     */
    public function getTransfer() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers')
            ->maps([
                '#mch_appid',
                '#mchid',
                'device_info',
                '#nonce_str',
                'sign',
                '#partner_trade_no',
                '#openid',
                'check_name' => 'NO_CHECK', //NO_CHECK：不校验真实姓名  FORCE_CHECK：强校验真实姓名
                're_user_name',
                '#amount',
                '#desc',
                '#spbill_create_ip'
            ]);
    }

    /**
     * 查询企业付款
     * @return Http
     */
    public function getQueryTransfer() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo')
            ->maps([
                '#nonce_str',
                'sign',
                '#partner_trade_no',
                '#mch_id',
                '#appid '
            ]);
    }

    /**
     * 企业付款到银行卡
     * @return Http
     */
    public function getTransferBank() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/mmpaysptrans/pay_bank')
            ->maps([
                '#mchid',
                '#nonce_str',
                'sign',
                '#partner_trade_no',
                '#enc_bank_no',
                '#enc_true_name',
                '#bank_code',
                '#amount',
                '#desc',
            ]);
    }

    /**
     * 查询企业付款银行卡
     * @return Http
     */
    public function getQueryTransferBank() {
        return $this->getBaseHttp('https://api.mch.weixin.qq.com/mmpaysptrans/query_bank')
            ->maps([
                '#nonce_str',
                'sign',
                '#partner_trade_no',
                '#mch_id',
            ]);
    }

    protected function encodeXml(array $data) {
        return Xml::encode(
            $data, 'xml'
        );
    }

    /**
     * 生成预支付订单
     * [
     * 'nonce_str' => '',
     * 'body' => '',
     * 'out_trade_no' => ',
     * 'total_fee' => 1,
     * 'spbill_create_ip' => '',
     * 'time_start' => date('Ymdis')
     * ]
     * @param array $args
     * @return array|bool|mixed|object
     * @throws \ErrorException
     * @throws \Exception
     */
    public function order(array $args = array()) {
        $args = $this->getOrder()->parameters($this->merge($args))->xml();
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if ($args['result_code'] != 'SUCCESS') {
            throw new \ErrorException($args['err_code_des']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        $this->set($args);
        return $args;
    }

    /**
     * 查询订单
     * EXAMPLE:
     * [
     * 'out_trade_no' =>
     * ]
     * @param array $args
     * @return array|bool|mixed|object
     * @throws \ErrorException
     * @throws \Exception
     */
    public function queryOrder(array $args = array()) {
        $args = $this->getQuery()->parameters($this->merge($args))->xml();
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        return $args;
    }

    /**
     * 关闭订单
     * @param array $args
     * @return array|bool|mixed|object
     * @throws \ErrorException
     * @throws \Exception
     */
    public function closeOrder(array $args = array()) {
        $args = $this->getClose()->parameters($this->merge($args))->xml();
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        return $args;
    }

    /**
     * APP支付参数 异步回调必须输出 appCallbackReturn()
     *
     * @param array $args
     * @return array
     */
    public function appPay(array $args = array()) {
        if (!isset($args['timestamp'])) {
            $args['timestamp'] = time();
        }
        return $this->set($args)->getAppPay();
    }

    public function appCallbackReturn() {
        return Xml::specialEncode($this->getAppReturn());
    }

    /**
     * 下载对账单
     * @param string|File $file
     * @param array $args
     * @return int
     * @throws \Exception
     */
    public function downloadBill($file, array $args = array()) {
        return $this->getBill()->parameters($this->merge($args))->save($file);
    }

    /**
     * 查询退款
     * @param array $args
     * @return array|bool|mixed|object
     * @throws \ErrorException
     * @throws \Exception
     */
    public function queryRefund(array $args = array()) {
        $args = $this->getQueryRefund()->parameters($this->merge($args))->xml();
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        return $args;
    }

    /**
     * 退款
     * @param array $args
     * @return array|bool|mixed|object
     * @throws \ErrorException
     * @throws \Exception
     */
    public function refundOrder(array $args = array()) {
        //第一种方法，cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        //curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/cert.pem');
        //默认格式为PEM，可以注释
        //curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        //curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/private.pem');

        //第二种方式，两个文件合成一个.pem文件
        $args = $this->getRefund()
            ->setOption(CURLOPT_SSLCERT, (string)$this->privateKeyFile)
            ->parameters($this->merge($args))->xml();
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        return $args;
    }

    /**
     * 生成签名
     * @param array $args
     * @return string
     */
    public function sign($args) {
        if (empty($this->key)) {
            throw new \InvalidArgumentException('KEY IS NEED');
        }
        ksort($args);
        reset($args);
        $arg = '';
        foreach ($args as $key => $item) {
            if (Http::isEmpty($item) ||
                in_array($key, $this->ignoreKeys)) {
                continue;
            }
            $arg .= "{$key}={$item}&";
        }
        return strtoupper(md5($arg.'key='.$this->key));
    }

    /**
     * 验证
     * @param array $args
     * @param $sign
     * @return bool
     */
    public function verify(array $args, $sign = null) {
        if (is_null($sign)) {
            $sign = $args[$this->signKey];
        }
        return $this->sign($args) === $sign;
    }

    /**
     * 交易完成回调
     * @return mixed
     * @throws \ErrorException
     */
    public function callback() {
        $args = Xml::specialDecode(Request::input());
        Factory::log()
            ->info('WECHAT PAY CALLBACK: '.Request::input());
        if (!is_array($args)) {
            throw new \InvalidArgumentException('非法数据');
        }
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        return $args;
    }

    /**
     * 微信二维码支付
     * @param array $args
     * @return Image
     * @throws \Exception
     */
    public function qrPay(array $args = array()) {
        $url = $this->getPayQr()->parameters($this->merge($args))->getUrl();
        return (new QrCode())->create((string)$url);
    }

    /**
     * https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_4
     * @return array|mixed|object
     */
    public function qrCallback() {
        /*$args = $this->callback();
        if ($args === false) {
            return false;
        }
        $order = $this->getOrder($args);
        if ($order === false) {
            return false;
        }*/
        return $this->getQrReturn();
    }

    /**
     * 商户后台系统先调用微信支付的统一下单接口，微信后台系统返回链接参数code_url，商户后台系统将code_url值生成二维码图片
     * @param array $args
     * @return bool|Image
     * @throws \Exception
     */
    public function orderQr(array $args = array()) {
        $data = $this->order($args);
        if ($data === false) {
            return false;
        }
        if (array_key_exists('code_url', $data)) {
            return (new QrCode())->create($data['code_url']);
        }
        throw new \Exception('unkown');
        //return (new QrCode())->create($this->getUrl('orderQr', $data));
    }

    /**
     * h5下单获取支付链接，并加上回调地址
     * @param array $args
     * @param string|Uri $redirect_url
     * @return string
     * @throws \Exception
     */
    public function h5Pay(array $args = [], $redirect_url = null) {
        $args['trade_type'] = 'MWEB';
        $data = $this->order($args);
        if ($data === false) {
            return false;
        }
        if (array_key_exists('mweb_url', $data)) {
            return $data['mweb_url'].'&redirect_url='.urlencode((string)$redirect_url);
        }
        throw new \Exception('unkown');
    }

    /**
     * 公众号支付 在微信浏览器里面打开H5网页中执行JS调起支付。接口输入输出数据格式为JSON。
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_7&index=6
     * @param array $args
     * @return array
     */
    public function jsPay(array $args = array()) {
        $args['appId'] = $this->get('appid'); //防止微信返回appid
        $args['nonceStr'] = Str::random(32);
        $args['timeStamp'] = time();
        $data = $this->set($args)->getJsApi();
        $data['package'] = 'prepay_id='.$data['package']['prepay_id'];
        $data['paySign'] = $this->sign($data);
        return $data;
    }

    /**
     * 报关
     * @param array $args
     * @return array|mixed
     * @throws \ErrorException
     * @throws \Exception
     */
    public function declareOrder(array $args = array()) {
        $args = $this->getDeclareOrder()->parameters($this->merge($args))->xml();
        if ($args['return_code'] != 'SUCCESS') {
            throw new \ErrorException($args['return_msg']);
        }
        if (!$this->verify($args)) {
            throw new \InvalidArgumentException('数据验签失败！');
        }
        $this->set($args);
        return $args;
    }
}