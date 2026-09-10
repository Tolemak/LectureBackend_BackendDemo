<?php

declare(strict_types=1);

namespace App\Tests;

use App\Persistence\DatabaseClient;
use App\User\User;
use App\User\UserRole;
use App\Util\StringId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected const string TEST_PASSWORD = 'test-password';

    protected readonly KernelBrowser $httpClient;
    protected User $studentUser;
    protected User $otherStudentUser;
    protected User $lecturerUser;

    protected function setUp(): void
    {
        $this->httpClient = static::createClient();

        /** @var DatabaseClient $databaseClient */
        $databaseClient = $this->httpClient->getContainer()->get(DatabaseClient::class);
        $databaseClient->dropDatabase();

        $this->addSampleUsers($databaseClient);
        $this->addSampleLectures($databaseClient);
    }

    protected function addSampleUsers(DatabaseClient $databaseClient): void
    {
        $this->studentUser = new User(new StringId('student-1'), 'Student Example', UserRole::STUDENT);
        $this->otherStudentUser = new User(new StringId('student-2'), 'Student Two', UserRole::STUDENT);
        $this->lecturerUser = new User(new StringId('lecturer-1'), 'Lecturer Example', UserRole::LECTURER);

        $users = [
            $this->studentUser,
            $this->otherStudentUser,
            new User(new StringId('student-3'), 'Student Three', UserRole::STUDENT),
            $this->lecturerUser,
            new User(new StringId('lecturer-2'), 'Lecturer Two', UserRole::LECTURER),
        ];

        foreach ($users as $user) {
            $databaseClient->upsert(
                'user',
                ['id' => (string)$user->getId()],
                [
                    '$set' => [
                        'id' => (string)$user->getId(),
                        'name' => $user->getName(),
                        'role' => $user->getRole()->value,
                        'password' => password_hash(self::TEST_PASSWORD, PASSWORD_BCRYPT),
                    ],
                ],
            );
        }
    }

    protected function addSampleLectures(DatabaseClient $databaseClient): void
    {
        $lectures = [
            [
                'id' => 'lecture-1',
                'lecturerId' => 'lecturer-1',
                'name' => 'Matematyka',
                'studentLimit' => 30,
                'startDate' => (new \DateTimeImmutable('+1 day'))->format(DATE_ATOM),
                'endDate' => (new \DateTimeImmutable('+2 days'))->format(DATE_ATOM),
            ],
            [
                'id' => 'lecture-2',
                'lecturerId' => 'lecturer-1',
                'name' => 'Fizyka',
                'studentLimit' => 25,
                'startDate' => (new \DateTimeImmutable('+3 days'))->format(DATE_ATOM),
                'endDate' => (new \DateTimeImmutable('+4 days'))->format(DATE_ATOM),
            ],
            [
                'id' => 'lecture-3',
                'lecturerId' => 'lecturer-2',
                'name' => 'Chemia',
                'studentLimit' => 20,
                'startDate' => (new \DateTimeImmutable('+5 days'))->format(DATE_ATOM),
                'endDate' => (new \DateTimeImmutable('+6 days'))->format(DATE_ATOM),
            ],
            [
                'id' => 'lecture-4',
                'lecturerId' => 'lecturer-2',
                'name' => 'Biologia',
                'studentLimit' => 15,
                'startDate' => (new \DateTimeImmutable('+7 days'))->format(DATE_ATOM),
                'endDate' => (new \DateTimeImmutable('+8 days'))->format(DATE_ATOM),
            ],
            [
                'id' => 'lecture-5',
                'lecturerId' => 'lecturer-1',
                'name' => 'Historia',
                'studentLimit' => 40,
                'startDate' => (new \DateTimeImmutable('+9 days'))->format(DATE_ATOM),
                'endDate' => (new \DateTimeImmutable('+10 days'))->format(DATE_ATOM),
            ],
        ];

        foreach ($lectures as $lecture) {
            $databaseClient->upsert(
                'lectures',
                ['id' => $lecture['id']],
                [
                    '$set' => [
                        'id' => $lecture['id'],
                        'lecturerId' => $lecture['lecturerId'],
                        'name' => $lecture['name'],
                        'studentLimit' => $lecture['studentLimit'],
                        'startDate' => $lecture['startDate'],
                        'endDate' => $lecture['endDate'],
                    ],
                ],
            );
        }
    }

    protected function tokenFor(User $user): string
    {
        $response = $this->makeRequest(
            'POST',
            '/auth/login',
            json_encode(['userId' => (string)$user->getId(), 'password' => self::TEST_PASSWORD], JSON_THROW_ON_ERROR),
            ['CONTENT_TYPE' => 'application/json'],
        );

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['token'];
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(User $user): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->tokenFor($user),
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    protected function enroll(string $lectureId, User $student): Response
    {
        return $this->makeRequest(
            'POST',
            '/lectures/' . $lectureId . '/enroll',
            '',
            $this->authHeaders($student),
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
