<?php
namespace Zodream\ThirdParty\API;
/**
 * ALL SEARCH ENGINE API
 * User: zx648
 * Date: 2016/7/27
 * Time: 12:03
 */
use Zodream\Helpers\Json;
use Zodream\ThirdParty\ThirdParty;

class Search extends ThirdParty {
    protected $apiMap = [
        'baidu' => [
            'http://data.zz.baidu.com/urls',
            [
                '!site',
                '!token'
            ]
        ]
    ];

    /**
     * INITIATIVE PUT URLS TO BAIDU
     * @param array $args
     * @return array
     */
    public function putBaiDu(array $args) {
        return Json::decode($this->http
            ->setHeader('Content-Type', 'text/plain')
            ->setUrl($this->getUrl('baidu'))
            ->request()->post(implode("\n", $args)));
    }


}