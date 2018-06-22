<?php
namespace Zodream\ThirdParty\ALi;

use Zodream\Helpers\Json;
use Zodream\Http\Http;
use Exception;

abstract class BaseZhiMa extends BaseALi {

    /**
     * @return Http
     */
    public function getBaseHttp() {
        return $this->getHttp('https://zmopenapi.zmxy.com.cn/openapi.do')
            ->maps([
                '#app_id',
                'method' => '',
                'charset' => 'utf-8',
                'sign',
                'version' => '1.0',
                'platform' => 'zmop',
            ])->parameters($this->get())
            ->encode(function ($data) {
                if (array_key_exists('params', $data)
                    && is_array($data['params'])) {
                    $data['params'] = $this->buildQuery($data['params']);
                }
                $data[$this->signKey] = $this->sign($data);
                $data['params'] = $this->getRsa()->encrypt($data['params']);
                return $data;
            })->decode(function ($data) {
                $data = Json::decode($data);
                foreach ($data as $key => $item) {
                    if (strrchr($key, '_response') != '_response') {
                        continue;
                    }
                    if (isset($data['biz_response_sign'])
                        && !$this->verify($item, $data['biz_response_sign'])) {
                        throw new Exception('结果验证失败！');
                    }
                    if (isset($data['encrypted']) && $data['encrypted']) {
                        $item = $this->getRsa()->decrypt($item);
                    }
                    $item = Json::decode($item);
                    if (isset($item['error_code'])) {
                        throw new Exception(
                            sprintf(
                                __('error code: %s'),
                                $item['error_code']
                            )
                        );
                    }
                    return $item;
                }
                return null;
            });
    }

    protected function buildQuery(array $params, $needEncode = true) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if ($needEncode){
                $v = urlencode($v);
            }
            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }
            $i++;
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
}