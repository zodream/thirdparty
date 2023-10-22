<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\Translate;

interface ITranslateProtocol {

    /**
     * 翻译文本
     * @param string $sourceLang
     * @param string $targetLang
     * @param string|array $text
     * @return string|array
     */
    public function trans(string $sourceLang, string $targetLang, string|array $text): string|array;
}