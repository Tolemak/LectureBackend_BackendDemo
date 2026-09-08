<?php

declare(strict_types=1);

namespace App\Lecture\Exception;

final class StudentLimitReachedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Student limit reached');
    }
}
