<?php
namespace Zodream\ThirdParty\API;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/2
 * Time: 12:55
 */
use Zodream\ThirdParty\ThirdParty;

class Microsoft extends ThirdParty  {

    public function getFaceScore() {
        return $this->getHttp('http://kan.msxiaobing.com/Api/ImageAnalyze/Process?service=yanzhi')
            ->maps([
                'MsgId',
                'CreateTime',
                'Content[imageUrl]'
            ]);
    }

    public function getUpload() {
        return $this->getHttp('http://kan.msxiaobing.com/Api/Image/UploadBase64');
    }

    /**
     * 颜值测试
     * @param string $img base64_encode
     * @return array
     * @throws \Exception
     */
    public function faceScore($img) {
        /**
         * {"Host":"","Url":""}
         */
        $data = $this->getUpload()->parameters($img)->json();
        $this->set(array(
            'MsgId' => time().'063',
            'CreateTime' => time(),
            'Content[imageUrl]' => $data['Host'].$data['Url']
        ));
        return $this->getFaceScore()
            ->parameters($this->get())->json();
    }
}