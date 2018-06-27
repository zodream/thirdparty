<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\Infrastructure\Security\Rsa as BaseRsa;

class Rsa extends BaseRsa {

    public function sign($data) {
        if (empty($this->privateKey)) {
            return false;
        }
        $encrypted = '';
        if (!openssl_sign($data, $encrypted, $this->privateKey, $this->padding)) {
            return false;
        }
        return base64_encode($encrypted);
    }

    public function verify($data, $sign) {
        return (bool)openssl_verify($data, base64_decode($sign), $this->publicKey, $this->padding);
    }

    /**
     * rsa加密
     * @param $data 要加密的数据
     * @return string 加密后的密文
     */
    public function encrypt($data){
        $maxlength = $this->getMaxEncryptBlockSize($this->publicKey);
        $output='';
        while(strlen($data)){
            $input = substr($data, 0, $maxlength);
            $data = substr($data, $maxlength);
            openssl_public_encrypt($input, $encrypted, $this->publicKey);
            $output .= $encrypted;
        }
        $encryptedData =  base64_encode($output);
        return $encryptedData;
    }

    /**
     * 解密
     * @param $data 要解密的数据
     * @return string 解密后的明文
     */
    public function decrypt($data){
        $data = base64_decode($data);
        $maxlength = $this->getMaxDecryptBlockSize($this->privateKey);
        $output='';
        while(strlen($data)){
            $input = substr($data, 0, $maxlength);
            $data = substr($data, $maxlength);
            openssl_private_decrypt($input, $out, $this->privateKey);
            $output .= $out;
        }
        return $output;
    }

    /**
     *根据key的内容获取最大加密lock的大小，兼容各种长度的rsa keysize（比如1024,2048）
     * 对于1024长度的RSA Key，返回值为117
     * @param $keyRes
     * @return float
     */
    public function getMaxEncryptBlockSize($keyRes){
        $keyDetail = openssl_pkey_get_details($keyRes);
        $modulusSize = $keyDetail['bits'];
        return $modulusSize/8 - 11;
    }

    /**
     * 根据key的内容获取最大解密block的大小，兼容各种长度的rsa keysize（比如1024,2048）
     * 对于1024长度的RSA Key，返回值为128
     * @param $keyRes
     * @return float
     */
    public function getMaxDecryptBlockSize($keyRes){
        $keyDetail = openssl_pkey_get_details($keyRes);
        $modulusSize = $keyDetail['bits'];
        return $modulusSize / 8;
    }
}