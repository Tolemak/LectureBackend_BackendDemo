<?php

declare(strict_types=1);

namespace App\Security;

use App\Persistence\DatabaseClient;
use App\User\UserRole;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<ApiUser>
 */
final class ApiUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly DatabaseClient $databaseClient,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $users = $this->databaseClient->getByQuery('user', ['id' => $identifier]);
        $user = $users[0] ?? null;

        $role = $user === null ? null : UserRole::tryFrom($user['role'] ?? '');
        if ($role === null) {
            throw new UserNotFoundException();
        }

        return new ApiUser($identifier, $role, $user['password'] ?? null);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof ApiUser) {
            throw new UnsupportedUserException();
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiUser::class;
    }
}
