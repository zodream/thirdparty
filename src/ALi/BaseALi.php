<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\Domain\Filter\Filters\RequiredFilter;
use Zodream\Helpers\Json;
use Zodream\Http\Http;
use Zodream\ThirdParty\ThirdParty;
use Zodream\Disk\File;

/**
 * Class BaseALi
 * @package Zodream\ThirdParty\ALi
 * @property string $sign_type 签名类型
 * @property string $key MD5 密钥
 * @property string $privateKey 商户私钥
 * @property string $publicKey 支付宝公钥
 *
 * @property string $encryptType AES
 * @property string $encryptKey 解密biz_content的密钥
 */
abstract class BaseALi extends ThirdParty {

    const MD5 = 'MD5';
    const RSA = 'RSA';
    const RSA2 = 'RSA2';

    const AES = 'AES';

    protected $configKey = 'alipay';

    protected $signKey = 'sign';

    protected $ignoreKeys = [
        'sign'
    ];

    /**
     * @var File
     */
    protected $caFile;

    /**
     * 商户私钥文件路径
     * @var File
     */
    protected $privateKeyFile;

    /**
     * 支付宝公钥路径
     * @var File
     */
    protected $publicKeyFile;

    public function __construct(array $config = array()) {
        parent::__construct($config);
        if ($this->has('privateKeyFile')) {
            $this->setPrivateKeyFile($this->get('privateKeyFile'));
        }
        if ($this->has('publicKeyFile')) {
            $this->setPublicKeyFile($this->get('publicKeyFile'));
        }
        if ($this->has('caFile')) {
            $this->setCaFile($this->get('caFile'));
        }
        if ($this->has('ignoreKeys')) {
            $this->ignoreKeys = $this->get('ignoreKeys');
        }
    }

    /**
     * @return Http
     */
    public function getBaseHttp() {
        return $this->getHttp('https://openapi.alipay.com/gateway.do')
            ->maps([
                '#app_id',
                'method' => '',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'sign',
                '#timestamp', //yyyy-MM-dd HH:mm:ss
                'version' => '1.0',
                'auth_token',
                'app_auth_token'
            ])->encode(function($data) {
                if (array_key_exists('biz_content', $data)
                    && is_array($data['biz_content'])) {
                    $data['biz_content'] = $this->encodeContent($data['biz_content']);
                }
                $data[$this->signKey] = $this->sign($data);
                return $data;
            })->parameters($this->get())->parameters([
                'timestamp' => date('Y-m-d H:i:s')
            ])->decode(function ($data) {
                $data = Json::decode($data);
                if ($this->verify($data)) {
                    return reset($data);
                }
                throw new \Exception('结果验证失败！');
            });
    }

    /**
     * @return File
     */
    public function getPrivateKeyFile() {
        return $this->privateKeyFile;
    }

    public function setPrivateKeyFile($file) {
        $this->privateKeyFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    /**
     * @return File
     */
    public function getPublicKeyFile() {
        return $this->publicKeyFile;
    }

    public function setPublicKeyFile($file) {
        $this->publicKeyFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    public function getCaFile() {
        return $this->caFile;
    }

    public function setCaFile($file) {
        $this->caFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    /**
     * 获取签名类型
     * @return string
     */
    public function getSignType() {
        return strtoupper($this->get('sign_type', self::RSA2));
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
        $arg = '{'.implode(',', $data).'}';
        if (!empty($this->encryptKey)
            && $this->encryptType == self::AES) {
            return (new Aes($this->encryptKey))->encrypt($arg);
        }
        return $arg;
    }


    /**
     * 签名
     * @param array|string $content
     * @return string
     * @throws \Exception
     */
    public function sign($content) {
        if (is_array($content)) {
            $content = $this->getSignContent($content);
        }
        if ($this->getSignType() == self::MD5) {
            return md5($content.$this->key);
        }
        if (empty($this->getPrivateKeyFile())
            || !$this->getPrivateKeyFile()->exist()) {
            $priKey = $this->privateKey;
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }else {
            $priKey = $this->getPrivateKeyFile()->read();
            $res = openssl_get_privatekey($priKey);
        }
        if (!$res) {
            throw new \Exception('您使用的私钥格式错误，请检查RSA私钥配置');
        }
        if (self::RSA2 == $this->getSignType()) {
            openssl_sign($content, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($content, $sign, $res);
        }
        if (is_resource($res)) {
            openssl_free_key($res);
        }
        return base64_encode($sign);
    }

    protected function getSignContent(array $params) {
        ksort($params);
        $args = [];
        foreach ($params as $key => $item) {
            if ($this->isEmpty($item)
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
     * 验签
     * @param array $params
     * @param string $sign
     * @return bool
     * @throws \Exception
     */
    public function verify(array $params, $sign = null) {
        if (is_null($sign)) {
            $sign = $params[$this->signKey];
        }
        if (array_key_exists('sign_type', $params)) {
            $this->sign_type = strtoupper($params['sign_type']);
        }
        $content = $this->getSignContent($params);
        $result = $this->verifyContent($content, $sign);
        if (!$result && strpos($content, '\\/') > 0) {
            $content = str_replace('\\/', '/', $content);
            return $this->verifyContent($content, $sign);
        }
        return $result;
    }

    public function verifyContent($content, $sign) {
        if ($this->getSignType() == self::MD5) {
            return md5($content. $this->key) == $sign;
        }
        if(empty($this->getPublicKeyFile())
            || !$this->getPublicKeyFile()->exist()){

            $pubKey= $this->publicKey;
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($pubKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }else {
            //读取公钥文件
            $pubKey = $this->getPublicKeyFile()->read();
            //转换为openssl格式密钥
            $res = openssl_get_publickey($pubKey);
        }
        if (!$res) {
            throw new \Exception('支付宝RSA公钥错误。请检查公钥文件格式是否正确');
        }

        //调用openssl内置方法验签，返回bool值

        if (self::RSA2 == $this->getSignType()) {
            $result = (bool)openssl_verify($content,
                base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($content, base64_decode($sign), $res);
        }

        //释放资源
        if (is_resource($res)) {
            openssl_free_key($res);
        }
        return $result;
    }

    protected function isEmpty($value) {
        static $validator;
        if (empty($validator)) {
            $validator = new RequiredFilter();
        }
        return !$validator->validate($value);
    }
}