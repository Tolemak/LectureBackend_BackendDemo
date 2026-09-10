<?php

declare(strict_types=1);

namespace App\Security;

use App\User\UserRole;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly string $id,
        private readonly UserRole $role,
        private ?string $passwordHash = null,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_' . strtoupper($this->role->value)];
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
        $this->passwordHash = null;
    }
}
