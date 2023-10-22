<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Translate;

use Zodream\ThirdParty\ThirdParty;

class Azure extends ThirdParty implements ITranslateProtocol
{

    public function getBaseHttp() {
        return $this->getHttp('https://api.cognitive.microsofttranslator.com')
            ->header('Ocp-Apim-Subscription-Key', $this->get('authKey'))
            ->header('Ocp-Apim-Subscription-Region', $this->get('location'))
            ->header('Content-Type', 'application/json')
            ->url('translate', [
                'api-version' => '3.0',
                '#to',
                'includeSentenceLength' => true,
            ])->parameters($this->get())->encode();
    }

    public function trans(string $sourceLang, string $targetLang, array|string $text): string|array {
        $form = [];
        foreach ((array)$text as $v) {
            $form[] = [
                'text' => $v
            ];
        }
        $data = $this->getBaseHttp()
            ->maps($form)
            ->parameters([
                'to' => $targetLang,
            ])->json();
        if (empty($data) || empty($data[0]['translations'])) {
            throw new \Exception('any error');
        }
        $res = array_column($data[0]['translations'], 'text');
        if (!is_array($text)) {
            return current($res);
        }
        return $res;
    }
}