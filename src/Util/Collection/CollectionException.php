<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Util\Collection;

final class CollectionException extends \RuntimeException
{
    public static function invalidType(string $givenType, string $expectedType): self
    {
        return new self("The object {$givenType} is expected to be an instance of {$expectedType}");
    }
}
