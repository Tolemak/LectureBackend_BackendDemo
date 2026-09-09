<?php

declare(strict_types=1);

namespace App\Tests\Lecture;

use App\Tests\ApiTestCase;
use App\User\UserRole;
use App\Util\StringId;

use function PHPUnit\Framework\assertEquals;

final class LectureTest extends ApiTestCase
{
    /** @test */
    public function lecturerCanCreateNewLecture(): void
    {
        $payload = [
            'lecturerId' => (string)$this->lecturerUser->getId(),
            'name' => 'Historia',
            'studentLimit' => 40,
            'startDate' => (new \DateTimeImmutable('+1 day'))->format(DATE_ATOM),
            'endDate' => (new \DateTimeImmutable('+2 days'))->format(DATE_ATOM),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(201, $response->getStatusCode());

        $created = json_decode($response->getContent(), true);
        $this->assertEquals('created', $created['status']);
        $this->assertNotEmpty($created['id']);

        $response = $this->makeRequest('GET', '/lectures');
        $lectures = json_decode($response->getContent(), true);

        $found = false;
        foreach ($lectures as $lecture) {
            if (isset($lecture['id']) && $lecture['id'] === $created['id']) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Nowy wykład powinien być widoczny na liście wykładów');
    }

    /** @test */
    public function creatingLectureWithMissingFieldsIsRejected(): void
    {
        $payload = [
            'lecturerId' => (string)$this->lecturerUser->getId(),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function studentCannotCreateNewLecture(): void
    {
        assertEquals(UserRole::STUDENT, $this->studentUser->getRole());
        $payload = [
            'lecturerId' => (string)$this->studentUser->getId(),
            'name' => 'Zakazana Historia',
            'studentLimit' => 30,
            'startDate' => (new \DateTimeImmutable('+1 day'))->format(DATE_ATOM),
            'endDate' => (new \DateTimeImmutable('+2 days'))->format(DATE_ATOM),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Access denied']),
            $response->getContent()
        );
    }

    /** @test */
    public function lecturerCanRemoveStudentFromOwnLecture(): void
    {
        $studentId = (string)$this->studentUser->getId();

        $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode(['studentId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );

        // lecture-1 is owned by lecturer-1 ($this->lecturerUser) — see ApiTestCase::addSampleLectures().
        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            json_encode(['requesterId' => (string)$this->lecturerUser->getId()]),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'removed']),
            $response->getContent()
        );

        $enrollments = $this->httpClient->getContainer()
            ->get(\App\Service\LectureService::class)
            ->getEnrolledStudents('lecture-1');

        $studentIds = array_map(
            fn($enrollment) => (string)$enrollment->getStudentId(),
            $enrollments->getItems()
        );

        $this->assertNotContains(
            $studentId,
            $studentIds,
            'Student nie powinien być już zapisany na wykład lecture-1',
        );
    }

    /** @test */
    public function studentCanRemoveOwnEnrollment(): void
    {
        $studentId = (string)$this->studentUser->getId();

        $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode(['studentId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            json_encode(['requesterId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function unrelatedUserCannotRemoveStudentFromLecture(): void
    {
        $studentId = (string)$this->studentUser->getId();

        $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode(['studentId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            json_encode(['requesterId' => 'student-2']),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(403, $response->getStatusCode());

        $enrollments = $this->httpClient->getContainer()
            ->get(\App\Service\LectureService::class)
            ->getEnrolledStudents('lecture-1');

        $studentIds = array_map(
            fn($enrollment) => (string)$enrollment->getStudentId(),
            $enrollments->getItems()
        );

        $this->assertContains($studentId, $studentIds, 'Usunięcie bez autoryzacji nie powinno się powieść');
    }

    /** @test */
    public function studentCanEnrollToLecture(): void
    {
        $payload = [
            'studentId' => (string)$this->studentUser->getId(),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'enrolled']),
            $response->getContent()
        );

        $enrollments = $this->httpClient->getContainer()
            ->get(\App\Service\LectureService::class)
            ->getEnrolledStudents('lecture-1');

        $studentIds = array_map(
            fn($enrollment) => (string)$enrollment->getStudentId(),
            $enrollments->getItems()
        );

        $this->assertContains(
            (string)$this->studentUser->getId(),
            $studentIds,
            'Student powinien być zapisany na wykład lecture-1',
        );
    }

    /** @test */
    public function cannotEnrollToLectureIfStudentLimitExceeded(): void
    {
        $this->httpClient->getContainer()->get(\App\Persistence\DatabaseClient::class)
            ->upsert(
                'lectures',
                ['id' => 'lecture-2'],
                ['$set' => ['studentLimit' => 1]]
            );

        $payload1 = [
            'studentId' => 'student-1',
        ];
        $response1 = $this->makeRequest(
            'POST',
            '/lectures/lecture-2/enroll',
            json_encode($payload1),
            ['CONTENT_TYPE' => 'application/json']
        );
        $this->assertEquals(200, $response1->getStatusCode());

        $payload2 = [
            'studentId' => 'student-2',
        ];
        $response2 = $this->makeRequest(
            'POST',
            '/lectures/lecture-2/enroll',
            json_encode($payload2),
            ['CONTENT_TYPE' => 'application/json']
        );
        $this->assertEquals(409, $response2->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Student limit reached']),
            $response2->getContent()
        );
    }

    /** @test */
    public function cannotEnrollToLectureIfAlreadyStarted(): void
    {
        $lectureId = 'lecture-already-started';
        $studentId = (string)$this->studentUser->getId();

        $this->httpClient->getContainer()->get(\App\Persistence\DatabaseClient::class)
            ->upsert(
                'lectures',
                ['id' => $lectureId],
                [
                    '$set' => [
                        'id' => $lectureId,
                        'lecturerId' => (string)$this->lecturerUser->getId(),
                        'name' => 'Już rozpoczęty wykład',
                        'studentLimit' => 10,
                        'startDate' => (new \DateTimeImmutable('-2 hours'))->format(DATE_ATOM),
                        'endDate' => (new \DateTimeImmutable('+2 hours'))->format(DATE_ATOM),
                    ]
                ]
            );

        $payload = [
            'studentId' => $studentId,
        ];
        $response = $this->makeRequest(
            'POST',
            '/lectures/' . $lectureId . '/enroll',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture already started']),
            $response->getContent()
        );
    }

    /** @test */
    public function cannotEnrollToNonExistingLecture(): void
    {
        $payload = ['studentId' => (string)$this->studentUser->getId()];

        $response = $this->makeRequest(
            'POST',
            '/lectures/non-existing-lecture/enroll',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture not found']),
            $response->getContent()
        );
    }

    /** @test */
    public function cannotEnrollToSameLectureMoreThanOnce(): void
    {
        $payload = [
            'studentId' => (string)$this->studentUser->getId(),
        ];

        $response1 = $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'enrolled']),
            $response1->getContent()
        );

        $response2 = $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode($payload),
            ['CONTENT_TYPE' => 'application/json']
        );
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'enrolled']),
            $response2->getContent()
        );

        $enrollments = $this->httpClient->getContainer()
            ->get(\App\Service\LectureService::class)
            ->getEnrolledStudents('lecture-1');

        $studentIds = array_map(
            fn($enrollment) => (string)$enrollment->getStudentId(),
            $enrollments->getItems()
        );

        $count = 0;
        foreach ($studentIds as $id) {
            if ($id === (string)$this->studentUser->getId()) {
                $count++;
            }
        }
        $this->assertEquals(1, $count, 'Student powinien być zapisany tylko raz na wykład lecture-1');
    }

    /** @test */
    public function studentCanFetchListOfEnrolledLectures(): void
    {
        $studentId = (string)$this->studentUser->getId();

        $this->makeRequest(
            'POST',
            '/lectures/lecture-1/enroll',
            json_encode(['studentId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );
        $this->makeRequest(
            'POST',
            '/lectures/lecture-2/enroll',
            json_encode(['studentId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );

        $lectureService = $this->httpClient->getContainer()
            ->get(\App\Service\LectureService::class);

        $enrolledLectures = [];
        foreach (['lecture-1', 'lecture-2'] as $lectureId) {
            $enrollments = $lectureService->getEnrolledStudents($lectureId);
            foreach ($enrollments->getItems() as $enrollment) {
                if ((string)$enrollment->getStudentId() === $studentId) {
                    $enrolledLectures[] = $lectureId;
                }
            }
        }

        sort($enrolledLectures);

        $this->assertEquals(['lecture-1', 'lecture-2'], $enrolledLectures);
    }

    /** @test */
    public function cannotRemoveStudentFromNonExistingLecture(): void
    {
        $studentId = (string)$this->studentUser->getId();

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/non-existing-lecture/students/' . $studentId,
            json_encode(['requesterId' => $studentId]),
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture not found']),
            $response->getContent()
        );
    }
}
