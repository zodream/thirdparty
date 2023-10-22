<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Translate;

use Zodream\ThirdParty\ThirdParty;

/**
 *
 * 语言
 * BG - Bulgarian
 * CS - Czech
 * DA - Danish
 * DE - German
 * EL - Greek
 * EN - English
 * ES - Spanish
 * ET - Estonian
 * FI - Finnish
 * FR - French
 * HU - Hungarian
 * ID - Indonesian
 * IT - Italian
 * JA - Japanese
 * KO - Korean
 * LT - Lithuanian
 * LV - Latvian
 * NB - Norwegian (Bokmål)
 * NL - Dutch
 * PL - Polish
 * PT - Portuguese (all Portuguese varieties mixed)
 * RO - Romanian
 * RU - Russian
 * SK - Slovak
 * SL - Slovenian
 * SV - Swedish
 * TR - Turkish
 * UK - Ukrainian
 * ZH - Chinese
 */
class DeepL extends ThirdParty implements ITranslateProtocol {

    public function getBaseHttp() {
        return $this->getHttp('https://api-free.deepl.com/v2/translate')
            ->header('Authorization', 'DeepL-Auth-Key '.$this->get('authKey'))
            ->header('Content-Type', 'application/json')
            ->maps([
                '#text',
                'source_lang',
                '#target_lang',
                'split_sentences',
                'preserve_formatting',
                'formality',
                'glossary_id',
                'tag_handling',
                'outline_detection',
                'non_splitting_tags',
                'splitting_tags',
                'ignore_tags'
            ])->parameters($this->get())
            ->encode();
    }

    public function trans(string $sourceLang, string $targetLang, array|string $text): string|array {
        $data = $this->getBaseHttp()
            ->parameters([
                'text' => (array)$text,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
            ])->json();
        if (empty($data['translations'])) {
            throw new \Exception('any error');
        }
        $res = array_column($data['translations'], 'text');
        if (!is_array($text)) {
            return current($res);
        }
        return $res;
    }
}