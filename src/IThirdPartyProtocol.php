<?php
declare(strict_types=1);
namespace Zodream\ThirdParty;

/**
 * 第三接口对接
 */
interface IThirdPartyProtocol {
    public function getName(): string;


    public function invoke(string $name, mixed ...$args): mixed;
}