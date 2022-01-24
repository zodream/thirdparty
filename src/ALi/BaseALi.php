<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\Helpers\Arr;
use Zodream\Helpers\Json;
use Zodream\Http\Http;
use Zodream\Service\Factory;
use Zodream\ThirdParty\ThirdParty;
use Zodream\Disk\File;
use Exception;

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

    protected string $configKey = 'alipay';

    protected string $signKey = 'sign';

    protected array $ignoreKeys = [
        'sign',
        'sign_type'
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
        return $this->getHttp('https://openapi.alipay.com/gateway.do?charset=utf-8')
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
                return $this->verifyResponse($data);
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
     * 获取rsa
     */
    public function getRsa() {
        $rsa = new Rsa();
        $rsa->setPrivateKey(empty($this->getPrivateKeyFile())
            || !$this->getPrivateKeyFile()->exist() ? $this->privateKey : $this->getPrivateKeyFile())
            ->setPublicKey(empty($this->getPublicKeyFile())
                || !$this->getPublicKeyFile()->exist() ? $this->publicKey : $this->getPublicKeyFile())
            ->setPadding(self::RSA2 == $this->getSignType()
                ? OPENSSL_ALGO_SHA256 : OPENSSL_PKCS1_PADDING);
        return $rsa;
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
            $data[] = sprintf('"%s":"%s"', $key, $item);
        }
        $arg = '{'.implode(',', $data).'}';
        if (!empty($this->encryptKey)
            && $this->encryptType == self::AES) {
            return (new Aes($this->encryptKey))->encrypt($arg);
        }
        return $arg;
    }


    /**
     * 签名 签名必须包括sign_type
     * @param array|string $content
     * @return string
     * @throws \Exception
     */
    public function sign($content) {
        if (is_array($content)) {
            $this->ignoreKeys = ['sign'];
            $content = $this->getSignContent($content);
        }
        if ($this->getSignType() == self::MD5) {
            return md5($content.$this->key);
        }
        return $this->getRsa()->sign($content);
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
     * 验签 验签不包括 sign_type
     * @param array $params
     * @param string $sign
     * @return bool
     * @throws \Exception
     */
    public function verify($params, $sign = null) {
        list($content, $sign) = $this->getSignAndSource($params, $sign);
        $result = $this->verifyContent($content, $sign);
        if (!$result && strpos($content, '\\/') > 0) {
            $content = str_replace('\\/', '/', $content);
            return $this->verifyContent($content, $sign);
        }
        return $result;
    }

    public function verifyResponse($data) {
        if (is_array($data)) {
            if ($this->verify($data)) {
                return reset($data);
            }
            throw new Exception(
                __('verify response error!')
            );
        }
        $args = iconv('gb2312', 'utf-8//IGNORE', $data);
        Http::log($args);
        $args = Json::decode($args);
        if (empty($args)) {
            throw new Exception(
                __('verify response error!')
            );
        }
        $nodeName = key($args);
        $source = $this->getSource($data, $nodeName);
        if ($this->verify($source, $args[$this->signKey])) {
            return reset($args);
        }
        throw new Exception(
            __('verify response error!')
        );
    }

    protected function getSource($data, $nodeName) {
        $nodeIndex = strpos($data, $nodeName);
        $signDataStartIndex = $nodeIndex + strlen($nodeName) + 2;
        $signIndex = strrpos($data, sprintf('"%s"', $this->signKey));
        // 签名前-逗号
        $signDataEndIndex = $signIndex - 1;
        $indexLen = $signDataEndIndex - $signDataStartIndex;
        if ($indexLen < 0) {
            return null;
        }
        return substr($data, $signDataStartIndex, $indexLen);
    }

    protected function getSignAndSource($params, $sign) {
        if (!is_array($params)) {
            return [$params, $sign];
        }
        if (is_null($sign)) {
            $sign = $params[$this->signKey];
        }
        if (array_key_exists('sign_type', $params)) {
            $this->sign_type = strtoupper($params['sign_type']);
        }
        if (isset($params[$this->signKey])
            && Arr::isMultidimensional($params)) {
            $params = reset($params);
        }
        $content = $this->getSignContent($params);
        return [$content, $sign];
    }

    public function verifyContent($content, $sign) {
        if ($this->getSignType() == self::MD5) {
            return md5($content. $this->key) == $sign;
        }
        return $this->getRsa()->verify($content, $sign);
    }

    public static function renderForm(Http $http, $isReady = true) {
        $html = '<form id="alipaysubmit" name="alipaysubmit" action="'.$http->getUrl().'?charset=utf-8" method="POST">';
        $data = $http->getPostSource();
        foreach ($data as $key => $item) {
            $html .= '<input type="hidden" name="'.$key.'" value=\''.
                str_replace('\'', "&apos;", $item).'\'>';
        }
        $html .= '<button type="submit">Submit</button></form>';
        if ($isReady) {
            $html .= '<script>document.forms[\'alipaysubmit\'].submit();</script>';
        }
        return $html;
    }
}