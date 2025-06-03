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
        $this->addSampleLectures($databaseClient);
    }

    protected function addSampleUsers(DatabaseClient $databaseClient): void
    {
        $users = [
            // Studenci
            [
                'id' => 'student-1',
                'name' => 'Student Example',
                'role' => UserRole::STUDENT,
                'assignTo' => 'studentUser', // główny student do testów
            ],
            [
                'id' => 'student-2',
                'name' => 'Student Two',
                'role' => UserRole::STUDENT,
            ],
            [
                'id' => 'student-3',
                'name' => 'Student Three',
                'role' => UserRole::STUDENT,
            ],
            // Wykładowcy
            [
                'id' => 'lecturer-1',
                'name' => 'Lecturer Example',
                'role' => UserRole::LECTURER,
                'assignTo' => 'lecturerUser', // główny wykładowca do testów
            ],
            [
                'id' => 'lecturer-2',
                'name' => 'Lecturer Two',
                'role' => UserRole::LECTURER,
            ],
        ];

        foreach ($users as $userData) {
            $user = new User(
                new StringId($userData['id']),
                $userData['name'],
                $userData['role']
            );
            $databaseClient->upsert(
                'user',
                ['id' => (string)$user->getId()],
                [
                    '$set' => [
                        'id' => (string)$user->getId(),
                        'name' => $user->getName(),
                        'role' => $user->getRole()->value,
                    ]
                ]
            );
            // Przypisz do właściwości klasy jeśli trzeba
            if (isset($userData['assignTo'])) {
                $this->{$userData['assignTo']} = $user;
            }
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
                    ]
                ]
            );
        }
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
