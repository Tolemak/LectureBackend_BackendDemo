<?php

declare(strict_types=1);

namespace App\Lecture\Exception;

final class LectureAlreadyStartedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Lecture already started');
    }
}
