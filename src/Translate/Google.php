<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Translate;

use Zodream\ThirdParty\ThirdParty;

class Google extends ThirdParty implements ITranslateProtocol {

    public function getBaseHttp() {
        return $this->getHttp('https://translation.googleapis.com/language/translate/v2')
            ->header('Authorization', 'Bearer '.$this->get('token'))
            ->header('x-goog-user-project', $this->get('project'))
            ->header('Content-Type', 'application/json')
            ->maps([
                '#q',
                'source',
                '#target',
                'format'
            ])->parameters($this->get())->encode();
    }

    public function trans(string $sourceLang, string $targetLang, array|string $text): string|array {
        $data = $this->getBaseHttp()
            ->parameters([
                'q' => implode("\n", (array)$text),
                'source' => $sourceLang,
                'target' => $targetLang,
            ])->json();
        if (empty($data) || empty($data['data']['translations'])) {
            throw new \Exception('any error');
        }
        $res = array_column($data['data']['translations'], 'translatedText');
        if (!is_array($text)) {
            return current($res);
        }
        return $res;
    }
}