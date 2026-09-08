<?php

declare(strict_types=1);

namespace App\Lecture\Exception;

final class LectureNotFoundException extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Lecture not found');
    }
}
