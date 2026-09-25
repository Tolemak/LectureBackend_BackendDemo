<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Tests\ApiTestCase;
use App\User\UserRole;
use PHPUnit\Framework\Attributes\Test;

final class LoginThrottlingTest extends ApiTestCase
{
    private const int MAX_ATTEMPTS = 5;

    #[Test]
    public function loginIsBlockedAfterTooManyFailedAttempts(): void
    {
        $userId = $this->createUniqueStudent();

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $this->assertSame(401, $this->login($userId, 'wrong-password')->getStatusCode());
        }

        $response = $this->login($userId, self::TEST_PASSWORD);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Too many failed login attempts', self::body($response));
    }

    #[Test]
    public function throttlingIsScopedToTheAttackedAccount(): void
    {
        $userId = $this->createUniqueStudent();

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS + 1; $attempt++) {
            $this->login($userId, 'wrong-password');
        }

        $this->assertSame(200, $this->login((string)$this->studentUser->getId(), self::TEST_PASSWORD)->getStatusCode());
    }

    #[Test]
    public function successfulLoginBelowTheLimitIsNotThrottled(): void
    {
        $userId = $this->createUniqueStudent();

        for ($attempt = 1; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $this->login($userId, 'wrong-password');
        }

        $this->assertSame(200, $this->login($userId, self::TEST_PASSWORD)->getStatusCode());
    }

    private function createUniqueStudent(): string
    {
        $userId = 'throttled-' . bin2hex(random_bytes(6));

        $this->databaseClient()->upsert(
            'user',
            ['id' => $userId],
            [
                '$set' => [
                    'id' => $userId,
                    'role' => UserRole::STUDENT->value,
                    'password' => password_hash(self::TEST_PASSWORD, PASSWORD_BCRYPT),
                ],
            ],
        );

        return $userId;
    }
}
