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

    public function getBaiDu() {
        return $this->getHttp()
            ->url('http://data.zz.baidu.com/urls', [
                '#site',
                '#token'
            ])->maps([
                '#urls'
            ]);
    }

    /**
     * INITIATIVE PUT URLS TO BAIDU
     * @param array $urls
     * @return array
     * @throws \Exception
     */
    public function putBaiDu(array $urls) {
        return $this->getBaiDu()->setHeader([
            'Content-Type' => 'text/plain'
        ])->parameters($this->merge([
            'urls' => $urls
        ]))->encode(function ($data) {
            return implode("\n", $data['urls']);
        })->json();
    }


}