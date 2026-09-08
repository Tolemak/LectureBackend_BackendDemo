<?php

declare(strict_types=1);

namespace App\User;

use App\Util\StringId;

final readonly class User
{
    public function __construct(
        private StringId $id,
        private string $name,
        private UserRole $role,
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
