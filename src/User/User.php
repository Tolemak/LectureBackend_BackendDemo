<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\User;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class User
{
    public function __construct(
        private StringId $id,
        private string $name,
        public UserRole $role,
    ) {
    }

    public function getId(): StringId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }
}
