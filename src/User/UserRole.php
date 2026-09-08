<?php

declare(strict_types=1);

namespace App\User;

enum UserRole: string
{
    case LECTURER = 'lecturer';
    case STUDENT = 'student';
}
