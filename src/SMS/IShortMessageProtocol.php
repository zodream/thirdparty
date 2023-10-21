<?php
declare(strict_types=1);
namespace Zodream\ThirdParty\SMS;

interface IShortMessageProtocol {
    /**
     * 是否只支持模板
     * @return bool
     */
    public function isOnlyTemplate(): bool;

    public function send(string $mobile, string $templateId, array $data, string $signName = ''): bool|string;
}