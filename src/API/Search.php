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

    protected string $configKey = 'search';

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

    /**
     * 使用indexnow提交网址
     * @param array|string $urls
     * @param string $searchEngine
     * @return bool
     * @throws \Exception
     */
    public function indexNow(array|string $urls, string $searchEngine = 'www.bing.com'): bool {
        if (empty($searchEngine) || empty($urls)) {
            return false;
        }
        $postUri = sprintf('https://%s/indexnow', $searchEngine);
        $http = $this->getHttp();
        if (!is_array($urls)) {
            $http->url($postUri, [
                    'url' => $urls,
                    'key' => $this->get('bing_indexnow'),
                    'keyLocation'
                ])->get();
        } else {
            $http->url($postUri)->maps([
                    'host' => parse_url($urls[0], PHP_URL_HOST),
                    'key' => $this->get('bing_indexnow'),
                    'urlList' => $urls,
                    'keyLocation'
                ])->encode()->post();
        }
        return $http->getStatusCode() === 200;
    }
}