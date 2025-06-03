<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\User\UserRole;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected readonly KernelBrowser $httpClient;
    protected User $studentUser;
    protected User $lecturerUser;

    protected function setUp(): void
    {
        $this->httpClient = static::createClient();

        /** @var DatabaseClient $databaseClient */
        $databaseClient = $this->httpClient->getContainer()->get(DatabaseClient::class);
        $databaseClient->dropDatabase();

        $this->addSampleUsers($databaseClient);
    }

    protected function addSampleUsers(DatabaseClient $databaseClient): void
    {
        $this->studentUser = new User(
            new StringId('student-1'),
            'Student Example',
            UserRole::STUDENT
        );
        $databaseClient->upsert(
            'user',
            ['id' => (string)$this->studentUser->getId()],
            [
                '$set' => [
                    'id' => (string)$this->studentUser->getId(),
                    'name' => $this->studentUser->getName(),
                    'role' => $this->studentUser->getRole()->value,
                ]
            ]
        );

        $this->lecturerUser = new User(
            new StringId('lecturer-1'),
            'Lecturer Example',
            UserRole::LECTURER
        );
        $databaseClient->upsert(
            'user',
            ['id' => (string)$this->lecturerUser->getId()],
            [
                '$set' => [
                    'id' => (string)$this->lecturerUser->getId(),
                    'name' => $this->lecturerUser->getName(),
                    'role' => $this->lecturerUser->getRole()->value,
                ]
            ]
        );
    }

    protected function makeRequest(string $method, string $uri, string $content = '', array $headers = []): Response
    {
        $this->httpClient->request(
            $method,
            $uri,
            [],
            [],
            $headers,
            $content,
        );

        return $this->httpClient->getResponse();
    }
}
