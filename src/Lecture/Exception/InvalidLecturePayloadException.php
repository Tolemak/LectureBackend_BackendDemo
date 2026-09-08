<?php

declare(strict_types=1);

namespace App\Lecture\Exception;

final class InvalidLecturePayloadException extends \InvalidArgumentException
{
    /**
     * @param string[] $missingFields
     */
    public static function forMissingFields(array $missingFields): self
    {
        return new self('Missing or empty required field(s): ' . implode(', ', $missingFields));
    }
}
