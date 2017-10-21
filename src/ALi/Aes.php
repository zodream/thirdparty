<?php
namespace Zodream\ThirdParty\ALi;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/6
 * Time: 19:37
 */
use Zodream\Infrastructure\Security\BaseSecurity;

class Aes extends BaseSecurity {

    protected $key;

    protected $blockSize = 32;

    public function __construct($key) {
        $this->key = base64_decode($key);
        $this->blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    }

    /**
     * ENCRYPT STRING
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        $data = trim($data);
        $str = $this->pkcs7Pad($data, $this->blockSize);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),1);
        $encrypt_str =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->key, $str, MCRYPT_MODE_CBC);
        return base64_encode($encrypt_str);
    }

    /**
     * DECRYPT STRING
     * @param string $data
     * @return string
     */
    public function decrypt($data) {
        //使用BASE64对需要解密的字符串进行解码
        $str = base64_decode($data);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),1);
        $encrypt_str =  mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, $str, MCRYPT_MODE_CBC);
        $encrypt_str = trim($encrypt_str);
        $encrypt_str = $this->pkcs7UnPad($encrypt_str, $this->blockSize);
        return $encrypt_str;
    }
}