<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Translate;


use Zodream\ThirdParty\ThirdParty;

class Baidu extends ThirdParty implements ITranslateProtocol {

    public function getBaseHttp() {
        return $this->getHttp('http://api.fanyi.baidu.com/api/trans/vip/translate')
            ->maps([
                '#q',
                '#appid',
                'salt' => rand(10000,99999),
                'from',
                '#to',
            ])->parameters($this->get())->encode(function (array $data) {
                $data['sign'] = md5($data['appid']. $data['q']. $data['salt'].$this->get('secret'));
                return $data;
            });
    }

    public function trans(string $sourceLang, string $targetLang, array|string $text): string|array {
        $data = $this->getBaseHttp()
            ->parameters([
                'form' => $sourceLang,
                'to' => $targetLang,
                'q' => implode("\n", (array)$text)
            ])->json();
        if (empty($data) || empty($data['trans_result'])) {
            throw new \Exception($data['error_msg'] ?? 'any error');
        }
        $res = array_column($data['trans_result'], 'dst');
        if (!is_array($text)) {
            return current($res);
        }
        return $res;
    }
}