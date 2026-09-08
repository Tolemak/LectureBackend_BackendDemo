<?php

declare(strict_types=1);

namespace App\Lecture\Exception;

final class NotAuthorizedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Not authorized to perform this action');
    }
}
