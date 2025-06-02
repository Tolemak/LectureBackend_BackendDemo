<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Util;

use Ramsey\Uuid\Uuid;

final readonly class StringId implements \Stringable
{
    public function __construct(
        protected string $value,
    ) {
    }

    public static function new(): self
    {
        return new self(Uuid::uuid4()->serialize());
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $another): bool
    {
        return "$this" === "$another";
    }
}
