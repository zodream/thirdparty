<?php
namespace Zodream\ThirdParty\API;

use Zodream\ThirdParty\ThirdParty;
use Zodream\Http\Http;

class Common extends ThirdParty  {

    /**
     * 获取天气情况
     * @return Http
     */
    public function getWeather() {
        return $this->getHttp()
            ->url('http://op.juhe.cn/onebox/weather/query', [
                '#cityname',
                '#key',
                'dtype' // 返回数据的格式,xml或json，默认json
            ]);
    }

    /**
     * 站点扫描
     * @return Http
     */
    public function getWebscan() {
        return $this->getHttp()
            ->url('http://apis.juhe.cn/webscan/', [
                '#domain',
                'dtype' => 'json',  //返回类型,xml/json/jsonp可选
                'callback',
                '#key'
            ]);
    }

    public function getWooyun() {
        return $this->getHttp()
            ->url('http://op.juhe.cn/wooyun/index', [
                '#key',
                'type',   // 查询方式，可选值为submit、confirm、public、unclaim，不提供则默认为查询最新的漏洞
                'limit',
                'dtype' //json或xml，默认为json
            ]);
    }

    public function getIp() {
        return $this->getHttp()
            ->url('http://apis.juhe.cn/ip/ip2addr', [
                '#key',
                '#ip',
                'dtype' //json xml
            ]);
    }

    public function getKuaidi($isCompany = false) {
        if ($isCompany) {
            return $this->getHttp('http://poll.kuaidi100.com/poll/query.do')
                ->maps([
                    '#customer',
                    'sign',
                    '#param'
                ]);
        }
        return $this->getHttp()
            ->url('http://api.kuaidi100.com/api', [
                '#id',
                '#com', //公司编码
                '#nu',
                'show',  //0：返回json字符串， 1：返回xml对象， 2：返回html对象， 3：返回text文本。
                'muti',  //1:返回多行完整的信息， 0:只返回一行信息。 不填默认返回多行。
                'order'   //desc：按时间由新到旧排列， asc：按时间由旧到新排列。 不填默认返回倒序（大小写不敏感）
            ]);
    }

    public function getExchange() {
        return $this->getHttp()
            ->url('http://apis.baidu.com/apistore/currencyservice/currency', [
                '#fromCurrency',
                '#toCurrency',
                '#amount'
            ]);
    }

    public function getSinaIp() {
        return $this->getHttp()
            ->url('http://int.dpool.sina.com.cn/iplookup/iplookup.php', [
                    'format' => 'json',
                    'ip'
                ]);
    }

    public function getTaoBaoIp() {
        return $this->getHttp()
            ->url('http://ip.taobao.com/service/getIpInfo.php', [
                '#ip'
            ]);
    }


    /**
     * 汇率查询
     * @param string $from
     * @param string $to
     * @param int $amount
     * @return array
     * @throws \Exception
     */
    public function exchange($from, $to, $amount = 1) {
        return $this->getExchange()->setHeader([
            'apikey' => $this->get('apikey')
        ])->parameters([
            'fromCurrency' => $from,
            'toCurrency' => $to,
            'amount' => $amount
        ])->json();
    }

    public function getAddressByIp($ip = null) {
        if (empty($ip)) {
            return $this->getSinaIp()->text();
        }
        return $this->getTaoBaoIp()->parameters([
            'ip' => $ip
        ])->text();
    }

    public function kuaiDi100(array $data) {
        $param = [
            'com' => $data['com'] . '',
            'num' => $data['num'] . ''
        ];
        if (array_key_exists('from', $data)) {
            $param['from'] = $data['from'] . '';
        }
        if (array_key_exists('to', $data)) {
            $param['from'] = $data['to'] . '';
        }
        $param = json_encode($param);
        $sign = strtoupper(md5($param.$data['key'].$data['customer']));
        return $this->getKuaidi(true)->parameters([
            'customer' => $data['customer'],
            'sign' => $sign,
            'param' => $param
        ]);
    }
}