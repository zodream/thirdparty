<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\ThirdParty\ThirdParty;

abstract class BaseALi extends ThirdParty {

    const MD5 = 'MD5';
    const RSA = 'RSA';
    const RSA2 = 'RSA2';

    protected $configKey = 'alipay';

    protected $ignoreKeys = [
        'sign',
        'sign_type'
    ];

    protected $baseUrl = 'https://openapi.alipay.com/gateway.do';

    protected $baseMap = [
        '#app_id',
        'method' => '',
        'format' => 'JSON',
        'charset' => 'utf-8',
        'sign_type' => 'RSA2',
        'sign',
        '#timestamp', //yyyy-MM-dd HH:mm:ss
        'version' => '1.0',
        'app_auth_token'
    ];


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

    public function getMap($name) {
        if (!array_key_exists($name, $this->apiMap)){
            throw new \InvalidArgumentException('API NOT EXIST!');
        }
        $data = array_merge($this->baseMap, $this->apiMap[$name]);
        return [
            $this->baseUrl,
            $data,
            'POST'
        ];
    }

    protected function getJson($name, $args = array()) {
        $args['timestamp'] = date('Y-m-d H:i:s');
        return $this->getByApi($name, $args);
    }


    /**
     * @param array|string $args
     * @return string
     */
    public function sign($args) {

    }

    /**
     * @param $args
     * @param $sign
     * @return bool
     */
    public function verify(array $args, $sign = null) {

    }
}