<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\User;

enum UserRole: string
{
    case LECTURER = 'lecturer';
    case STUDENT = 'student';
}
