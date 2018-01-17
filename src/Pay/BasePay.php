<?php
namespace Zodream\ThirdParty\Pay;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/17
 * Time: 15:22
 */
use Zodream\Http\Http;
use Zodream\ThirdParty\ThirdParty;
use Zodream\Disk\File;

abstract class BasePay extends ThirdParty  {

    const MD5 = 'MD5';
    const RSA = 'RSA';
    const RSA2 = 'RSA2';

    protected $signType = self::MD5;

    protected $key;

    protected $signKey = 'sign';

    /**
     * 不参加签名字段
     * @var array
     */
    protected $ignoreKeys = [];

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
     * @var File
     */
    protected $publicKeyFile;

    public function __construct(array $config = array()) {
        parent::__construct($config);
        if ($this->has('key')) {
            $this->key = $this->get('key');
        }
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
        $this->setSignType($this->get('signType'));
    }

    public function getBaseHttp($url = null) {
        return $this->getHttp($url);
    }
    
    public function setSignType($arg = null) {
        if (empty($arg)) {
            $arg = empty($this->privateKeyFile) ? self::MD5 : self::RSA;
        }
        $this->signType = strtoupper($arg);
        return $this;
    }

    public function getKey() {
        return $this->key;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function getPrivateKeyFile() {
        return $this->privateKeyFile;
    }

    public function setPrivateKeyFile($file) {
        $this->privateKeyFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

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
     * 获取带签名的参数
     * @param array $data
     * @return array
     */
    protected function encodeSign(array $data) {
        $data[$this->signKey] = $this->sign($data);
        return $data;
    }


    /**
     * @param array|string $args
     * @return string
     */
    abstract public function sign($args);

    /**
     * @param $args
     * @param $sign
     * @return bool
     */
    abstract public function verify(array $args, $sign = null);

    /**
     * @return mixed
     */
    abstract public function callback();
}