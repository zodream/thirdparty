<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\Helpers\Json;
use Zodream\Http\Http;
use Exception;
use Zodream\ThirdParty\ThirdParty;

class TaoBaoKe extends ThirdParty {

    protected string $configKey = 'taobaoke';

    protected string $signKey = 'sign';

    protected array $ignoreKeys = [
        'sign',
        'sign_method'
    ];

    /**
     * @param $method
     * @param array $append
     * @return Http
     */
    public function getBaseHttp($method, $append = []) {
        return $this->getHttp('https://eco.taobao.com/router/rest')
            ->maps(array_merge([
                '#app_key',
                'method' => $method,
                'target_app_key',
                'sign_method' => 'hmac', //hmac，md5
                'sign',
                'session',
                '#timestamp',
                'format' => 'json',
                'v' => '2.0',
                'partner_id',
                'simplify' => 'false',
            ], $append))->parameters($this->get())
            ->encode(function ($data) {
                $data[$this->signKey] = $this->sign($data);
                return $data;
            })->decode(function ($data) {
                $data = Json::decode($data);
                foreach ($data as $key => $item) {
                    if (strrchr($key, '_response') != '_response') {
                        continue;
                    }
                    if ($key === 'error_response') {
                        throw new Exception(
                            sprintf(
                                __('error code: %s, %s'),
                                $item['msg'], $item['sub_msg']
                            )
                        );
                    }
                    return $item;
                }
                return null;
            });
    }

    public function sign($content) {
        if (is_array($content)) {
            $content = $this->getSignContent($content);
        }
        $secret = $this->get('secret');
        if ($this->getSignType() == 'md5') {
            return md5(sprintf('%s%s%s', $secret, $content, $secret));
        }
        return hash_hmac('md5', $content, $secret);
    }

    public function getSignType() {
        return strtoupper($this->get('sign_method', 'hmac'));
    }

    protected function getSignContent(array $params) {
        $keys = array_keys($params);
        sort($keys);
        return implode('', $keys);
    }


    public function getSearch() {
        return $this->getBaseHttp('taobao.tbk.item.get', [
            'fields' => 'num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick',
            'q',
            'cat',
            'sort',
            'is_tmall',
            'is_overseas',
            'start_price',
            'end_price',
            'start_tk_rate',
            'end_tk_rate',
            'platform',
            'page_no',
            'page_size'
        ]);
    }

    public function getRecommend() {
        return $this->getBaseHttp('taobao.tbk.item.recommend.get', [
            'fields' => 'num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url',
            '#num_iid',
            'count',
            'platform',
        ]);
    }

    public function getInfo() {
        return $this->getBaseHttp('taobao.tbk.item.info.get', [
            '#num_iid',
            'ip',
            'platform',
        ]);
    }

    public function getLinks() {
        return $this->getBaseHttp('taobao.tbk.ju.tqg.get', [
            '#adzone_id',
            'fields' => 'click_url,pic_url,reserve_price,zk_final_price,total_amount,sold_num,title,category_name,start_time,end_time',
            'start_time',
            'end_time',
            'page_no',
            'page_size'
        ]);
    }

    /**
     * 获取淘宝客商品链接转换
     * @return Http
     */
    public function getConvert() {
        return $this->getBaseHttp('taobao.tbk.item.convert', [
            'fields' => 'num_iid,click_url',
            '#num_iids',
            '#adzone_id',
            'platform',
            'unid',
            'dx'
        ]);
    }

    public function getCreateTPwd() {
        return $this->getBaseHttp('taobao.tbk.tpwd.create', [
            'user_id',
            '#text',
            '#url',
            'logo',
            'ext',
        ]);
    }

    public function search($keywords, $page = 1) {
        $res = $this->getSearch()->parameters([
            'q' => $keywords,
            'page_no' => $page
        ])->text();
        return $res['results'];
    }

    public function recommend($id) {
        $res = $this->getSearch()->parameters([
            'num_iid' => $id,
        ])->text();
        return $res['results'];
    }

    public function info($id) {
        $res = $this->getSearch()->parameters([
            'num_iid' => $id,
        ])->text();
        return $res['results']['n_tbk_item'];
    }

    /**
     * 获取淘抢购的数据，淘客商品转淘客链接
     * @param $adzone_id
     * @param $start_time
     * @param $end_time
     * @return mixed
     * @throws Exception
     */
    public function links($adzone_id, $start_time, $end_time) {
        $res = $this->getSearch()
            ->parameters(compact('adzone_id', 'start_time', 'end_time'))->text();
        return $res['results']['results'];
    }

    public function createTPwd($text, $url) {
        $res = $this->getSearch()->parameters(compact('text', 'url'))->text();
        return $res['data']['model'];
    }

    /**
     * 获取淘宝客商品链接转换(需要拥有此权限)
     * @param $id
     * @param $adzone_id
     * @return mixed
     * @throws Exception
     */
    public function convert($id, $adzone_id) {
        $res = $this->getConvert()->parameters([
            'num_iids' => $id,
            'adzone_id' => $adzone_id,
        ])->text();
        return $res['results']['n_tbk_item'];
    }


}