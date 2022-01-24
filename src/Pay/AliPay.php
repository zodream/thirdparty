<?php
namespace Zodream\ThirdParty\Pay;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/17
 * Time: 15:21
 */
use Zodream\Http\Http;
use Zodream\Http\Uri;
use Exception;

class AliPay extends BasePay {

    /**
     * EXAMPLE:
    'alipay' => array(
        'app_id' => '',
        'app_auth_token' => '',
        'privateKeyFile' => '/alipay/rsa_private_key.pem',
        'publicKeyFile' => '/alipay/alipay_rsa_public_key.pem',
        'notify_url' => ''
    )
     * @var string
     */
    protected string $configKey = 'alipay';

    protected array $ignoreKeys = [
        'sign',
        'sign_type'
    ];

    public function getQuery() {
        return $this->getBaseHttp()
            ->url('https://openapi.alipay.com/gateway.do', [
                '#app_id',
                'method' => 'alipay.trade.query',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA',
                'sign',
                '#timestamp', // yyyy-MM-dd HH:mm:ss,
                'version' => '1.0',
                'app_auth_token',
                '#biz_content' => [
                    [
                        'out_trade_no',
                        'trade_no'
                    ]
                ]
            ], [$this, 'encodeSign']);
    }

    /**
     * 即时支付
     * @return Http
     */
    public function getWebPay() {
        return $this->getBaseHttp()
            ->url('https://mapi.alipay.com/gateway.do', [
                'service' => 'create_direct_pay_by_user',
                '#partner',
                '_input_charset' => 'utf-8',
                'sign_type' => 'MD5', //DSA、RSA、MD5
                'sign',
                'notify_url',
                'return_url',
                '#out_trade_no',
                '#subject',
                'payment_type' => 1,
                '#total_fee', // 2位小数
                [
                    'seller_id',
                    'seller_email',
                    'seller_account_name'
                ],
                'buyer_id',
                'buyer_email',
                'buyer_account_name',

                'price',
                'quantity',
                'body',
                'show_url',
                'paymethod',
                'enable_paymethod',
                'anti_phishing_key',
                'exter_invoke_ip',
                'extra_common_param',
                'it_b_pay' => '1h',
                'token',
                'qr_pay_mode',
                'qrcode_width',
                'need_buyer_realnamed',
                'promo_param',
                'hb_fq_param',
                'goods_type'
            ], [$this, 'encodeSign']);
    }

    public function getWapPay() {
        return $this->getBaseHttp()
            ->url('https://mapi.alipay.com/gateway.do', [
                'service' => 'alipay.wap.create.direct.pay.by.user',
                '#partner',
                '_input_charset' => 'utf-8',
                'sign_type' => 'MD5', //DSA、RSA、MD5
                'sign',
                'notify_url',
                'return_url',
                '#out_trade_no',
                '#subject',
                'payment_type' => 1,
                '#total_fee', // 2位小数
                '#seller_id',

                'body',
                'show_url',
                'extern_token',
                'otherfee',
                'airticket',
                'rn_check',
                'buyer_cert_no',
                'it_b_pay' => '1h',
                'buyer_real_name',
                'scene',
                'hb_fq_param',
                'app_pay' => 'Y',
                'promo_param',
                'enable_paymethod',
                'goods_type',
                'extend_params',
                'ext_user_info'
            ], [$this, 'encodeSign']);
    }

    public function getPay() {
        return $this->getBaseHttp()
            ->url('https://openapi.alipay.com/gateway.do', [
                '#app_id',
                'method' => 'alipay.trade.app.pay',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA',
                'sign',
                '#timestamp', // yyyy-MM-dd HH:mm:ss,
                'version' => '1.0',
                '#notify_url',
                'app_auth_token',
                '#biz_content' => [
                    'body',
                    'scene' => 'bar_code',   //bar_code,wave_code
                    '#auth_code',
                    'discountable_amount',
                    'undiscountable_amount',
                    'extend_params',
                    'royalty_info',
                    'sub_merchant',
                    '#subject',
                    '#out_trade_no',
                    '#total_amount',    // 只能有两位小数
                    'seller_id'
                ]
            ], [$this, 'encodeSign']);
    }

    public function getAppPay() {
        return $this->encodeSign(Http::getMapParameters([
            '#app_id',
            'method' => 'alipay.trade.app.pay',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'sign',
            '#timestamp', // yyyy-MM-dd HH:mm:ss,
            'version' => '1.0',
            '#notify_url',
            '#biz_content' => [
                'body',
                '#subject',
                '#out_trade_no',
                'timeout_express',
                '#total_amount',  // 只能有两位小数
                'seller_id',
                'product_code' => 'QUICK_MSECURITY_PAY'
            ]
        ], $this->get()));
    }

    public function getMobilePay() {
        $data = Http::getMapParameters([
            'service' => 'mobile.securitypay.pay',
            '#partner',
            '_input_charset' => 'UTF-8',
            'sign_type' => 'RSA',
            'sign',
            '#notify_url',
            'app_id',
            'appenv',
            '#out_trade_no',
            '#subject',
            'payment_type' => 1,
            '#seller_id',
            '#total_fee',
            '#body',
            'goods_type' => 1,
            'hb_fq_param',
            'rn_check',
            'it_b_pay' => '90m',
            'extern_token',
            'promo_params'
        ], $this->get());
        ksort($data);
        reset($data);
        $args = [];
        foreach ($data as $key => $item) {
            if (Http::isEmpty($item)
                || in_array($key, $this->ignoreKeys)) {
                continue;
            }
            $args[] = $key.'="'.$item.'"';
        }
        $content = implode('&', $args);
        $data['sign'] = urlencode($this->sign($content));
        return $content.'&sign='.'"'.$data['sign'].'"'.'&sign_type='.'"'.$data['sign_type'].'"';
    }

    public function getRefundOrder() {
        return $this->getBaseHttp()
            ->url('https://mapi.alipay.com/gateway.do', [
                'service' => 'refund_fastpay_by_platform_pwd',
                '#partner',
                '_input_charset' => 'UTF-8',
                'sign_type' => 'MD5',
                'sign',
                'notify_url',
                [
                    'seller_email',
                    'seller_user_id'
                ],
                '#refund_date',   //yyyy-MM-dd HH:mm:ss
                '#batch_no',
                '#batch_num' => 1,
                '#detail_data' // 第一笔交易退款数据集#第二笔交易退款数据集
                //交易退款数据集的格式为：原付款支付宝交易号^退款总金额^退款理由
            ], [$this, 'encodeSign']);
    }

    public function getDeclareOrder() {
        return $this->getBaseHttp()
            ->url('https://mapi.alipay.com/gateway.do', [
                'service' => 'alipay.acquire.customs',
                '#partner',
                '_input_charset' => 'UTF-8',
                'sign_type' => 'MD5',
                'sign',
                '#out_request_no',
                '#trade_no',
                '#merchant_customs_code',
                '#amount',
                '#customs_place',
                '#merchant_customs_name',
                'is_split',
                'sub_out_biz_no',
                'buyer_name',
                'buyer_id_no'
            ], [$this, 'encodeSign']);
    }


    public function setSignType($arg = null) {
        parent::setSignType($arg);
        $this->set('sign_type', $this->signType);
        return $this;
    }

    protected function encodeSign(array $data) {
        if (array_key_exists('sign_type', $data)) {
            $this->signType = $data['sign_type'] =
                ($data['sign_type'] == static::RSA
                && !empty($this->privateKeyFile)) ? static::RSA : static::MD5;
        }
        if (array_key_exists('biz_content', $data)) {
            $data['biz_content'] = $this->encodeContent($data['biz_content']);
        }
        $data[$this->signKey] = $this->sign($data);
        return $data;
    }

    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    protected function encrypt($str){
        //AES, 128 模式加密数据 CBC
        $screct_key = base64_decode($this->key);
        $str = trim($str);
        $str = $this->addPKCS7Padding($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), 1);
        $encrypt_str =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * @param string $str
     * @return string
     */
    function decrypt($str){
        //AES, 128 模式加密数据 CBC
        $str = base64_decode($str);
        $screct_key = base64_decode($this->key);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), 1);
        $encrypt_str =  mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC);
        $encrypt_str = trim($encrypt_str);

        $encrypt_str = stripPKSC7Padding($encrypt_str);
        return $encrypt_str;

    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    protected function addPKCS7Padding($source){
        $source = trim($source);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }
    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    protected function stripPKSC7Padding($source){
        $source = trim($source);
        $char = substr($source, -1);
        $num = ord($char);
        if($num==62)return $source;
        $source = substr($source,0,-$num);
        return $source;
    }

    public function rsaEncrypt($data) {
        //转换为openssl格式密钥
        $res = openssl_get_publickey($this->publicKeyFile->read());
        $blocks = $this->splitCN($data, 0, 30, 'utf-8');
        $chrtext  = null;
        $encodes  = [];
        foreach ($blocks as $n => $block) {
            if (!openssl_public_encrypt($block, $chrtext , $res)) {
                throw new \InvalidArgumentException(openssl_error_string());
            }
            $encodes[] = $chrtext ;
        }
        $chrtext = implode(',', $encodes);

        return $chrtext;
    }

    /**
     * @param $data
     * @param $rsaPrivateKeyPem
     * @return string
     */
    public function rsaDecrypt($data, $rsaPrivateKeyPem) {
        //读取私钥文件
        $priKey = file_get_contents($rsaPrivateKeyPem);
        //转换为openssl格式密钥
        $res = openssl_get_privatekey($priKey);
        $decodes = explode(',', $data);
        $strnull = '';
        $dcyCont = '';
        foreach ($decodes as $n => $decode) {
            if (!openssl_private_decrypt($decode, $dcyCont, $res)) {
                throw new \InvalidArgumentException(openssl_error_string());
            }
            $strnull .= $dcyCont;
        }
        return $strnull;
    }

    /**
     * 生成请求参数的签名
     *
     * @param string|array $content
     * @return string
     * @throws Exception
     */
    public function sign($content) {
        if (is_array($content)) {
            $content = $this->getSignContent($content);
        }
        $res = $this->getPrivateKeyResource();
        if ($this->signType == self::MD5
            || empty($res)) {
            return md5($content.$this->key);
        }
        if ($this->signType != self::RSA
            && $this->signType != self::RSA2) {
            return null;
        }
        openssl_sign($content, $sign, $res,
            $this->signType == self::RSA ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
        if (is_resource($res)) {
            openssl_free_key($res);
        }
        //base64编码
        return base64_encode($sign);
    }

    /**
     * 验签
     * @param array $params
     * @param null $sign
     * @return bool
     * @throws Exception
     */
    public function verify(array $params, $sign = null) {
        if (is_null($sign)) {
            $sign = $params[$this->signKey];
        }
        if (array_key_exists('sign_type', $params)) {
            $this->signType = strtoupper($params['sign_type']);
        }
        $content = $this->getSignContent($params);
        $result = $this->verifyContent($content, $sign);
        if (!$result && strpos($content, '\\/') > 0) {
            $content = str_replace('\\/', '/', $content);
            return $this->verifyContent($content, $sign);
        }
        return $result;
    }

    /**
     * @param $content
     * @param $sign
     * @return bool
     * @throws Exception
     */
    public function verifyContent($content, $sign) {
        if ($this->signType == self::MD5) {
            return md5($content. $this->key) == $sign;
        }
        if ($this->signType != self::RSA
            && $this->signType != self::RSA2) {
            return false;
        }
        $res = $this->getPublicKeyResource();
        if (!$res) {
            throw new Exception('支付宝RSA公钥错误。请检查公钥文件格式是否正确');
        }
        $result = (bool)openssl_verify($content, base64_decode($sign), $res,
            $this->signType == self::RSA ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256);
        if (is_resource($res)) {
            openssl_free_key($res);
        }
        return $result;
    }

    protected function getSignContent(array $params) {
        ksort($params);
        $args = [];
        foreach ($params as $key => $item) {
            if (Http::isEmpty($item)
                || in_array($key, $this->ignoreKeys)
                || strpos($item, '@') === 0
            ) {
                continue;
            }
            $args[] = $key.'='.$item;
        }
        return implode('&', $args);
    }

    /**
     *
     * @return mixed
     * @throws \Exception
     */
    public function callback() {
        Http::log('ALIPAY CALLBACK: '.var_export($_POST, true));
        $data = $_POST;//Requests::isPost() ? $_POST : $_GET;
        if (!$this->verify($data)) {
            throw new \Exception('验签失败！');
        }
        return $data;
    }

    /**
     * 查询接口
     * EXMAPLE:
     * [
     * 'timestamp' => date('Y-m-d H:i:s'),
     * 'out_trade_no' => ''
     * ]
     * @param array $args
     * @return array|bool|mixed
     * @throws \Exception
     */
    public function queryOrder(array $args = array()) {
        $args = $this->getQuery()->parameters($this->merge($args))->json();
        if (!array_key_exists('alipay_trade_query_response', $args)) {
            throw new \Exception('未知错误！');
        }
        $args = $args['alipay_trade_query_response'];
        if ($args['code'] != 10000) {
            throw new \Exception($args['msg']);
        }
        if (!$this->verify($args)) {
            throw new \Exception('数据验签失败！');
        }
        return $args;
    }

    /**
     * 获取APP支付
     * @param array $args
     * @return string
     */
    public function appPay($args = array()) {
        return http_build_query($this->set($args)->getAppPay());
    }

    /**
     * 合并biz_content
     * @param array $args
     * @return string
     */
    protected function encodeContent(array $args) {
        $data = [];
        foreach ($args as $key => $item) {
            $data[] = sprintf('"%s"="%s"', $key, $item);
        }
        return '{'.implode(',', $data).'}';
    }

    /**
     * APP 支付 异步回调必须输出 success
     * EXAMPLE:
    [
        'timestamp' => date('Y-m-d H:i:s'),
        'subject' => '',
        'out_trade_no' => '',
        'total_amount' => 0.01,
        'body' => ''
    ]
     * @param array $args
     * @return string
     */
    public function mobilePay($args = array()) {
        return $this->set($args)->getMobilePay();
    }

    /**
     *  获取支付的网址
     * @param array $args
     * @return Uri
     * @throws \Exception
     */
    public function pay($args = array()) {
        return $this->getPay()->parameters($this->merge($args))->getUrl();
    }

    /**
     * 及时支付
     * @param array $args
     * @return Uri
     * @throws \Exception
     */
    public function webPay($args = array()) {
        return $this->getWebPay()->parameters($this->merge($args))->getUrl();
    }

    /**
     * h5 端支付
     * @param array $args
     * @return Uri
     * @throws \Exception
     */
    public function wapPay($args = array()) {
        return $this->getWapPay()->parameters($this->merge($args))->getUrl();
    }

    /**
     * 退款
     * @param array $args
     * @return Uri
     * @throws Exception
     */
    public function refundOrder($args = array()) {
        return $this->getRefundOrder()->parameters($this->merge($args))->getUrl();
    }

    /**
     * 报关
     * @param array $args
     * @return array|mixed
     * @throws \Exception
     */
    public function declareOrder(array $args = array()) {
        $args = $this->getDeclareOrder()
            ->parameters($this->merge($args))->json();
        if ($args['result_code'] != 'SUCCESS') {
            throw new \Exception($args['detail_error_des']);
        }
        if (!$this->verify($args)) {
            throw new \Exception('数据验签失败！');
        }
        $this->set($args);
        return $args;
    }
}