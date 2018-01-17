<?php
namespace Zodream\ThirdParty\API;
/**
 * ALL SEARCH ENGINE API
 * User: zx648
 * Date: 2016/7/27
 * Time: 12:03
 */
use Zodream\ThirdParty\ThirdParty;

class Search extends ThirdParty {

    public function getBaidu() {
        return $this->getHttp()
            ->url('http://data.zz.baidu.com/urls')
            ->maps([
                '#site',
                '#token'
            ]);
    }

    /**
     * INITIATIVE PUT URLS TO BAIDU
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function putBaiDu(array $args) {
        return $this->getBaidu()->setHeader([
            'Content-Type' => 'text/plain'
        ])->encode(function ($data) {
            return implode("\n", $data);
        })->json();
    }


}