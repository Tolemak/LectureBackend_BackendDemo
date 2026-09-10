<?php

declare(strict_types=1);

namespace App\Tests\Lecture;

use App\Persistence\DatabaseClient;
use App\Service\LectureService;
use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class LectureTest extends ApiTestCase
{
    #[Test]
    public function lecturerCanCreateNewLecture(): void
    {
        $payload = [
            'name' => 'Historia',
            'studentLimit' => 40,
            'startDate' => (new \DateTimeImmutable('+1 day'))->format(DATE_ATOM),
            'endDate' => (new \DateTimeImmutable('+2 days'))->format(DATE_ATOM),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode($payload),
            $this->authHeaders($this->lecturerUser),
        );

        $this->assertEquals(201, $response->getStatusCode());

        $created = json_decode($response->getContent(), true);
        $this->assertEquals('created', $created['status']);
        $this->assertNotEmpty($created['id']);

        $response = $this->makeRequest('GET', '/lectures', '', $this->authHeaders($this->lecturerUser));
        $lectures = json_decode($response->getContent(), true);

        $ids = array_column($lectures, 'id');
        $this->assertContains($created['id'], $ids, 'The new lecture should show up in the lecture list');
    }

    #[Test]
    public function creatingLectureWithMissingFieldsIsRejected(): void
    {
        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode([]),
            $this->authHeaders($this->lecturerUser),
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function creatingLectureEndingBeforeItStartsIsRejected(): void
    {
        $payload = [
            'name' => 'Odwrócona chronologia',
            'studentLimit' => 10,
            'startDate' => (new \DateTimeImmutable('+2 days'))->format(DATE_ATOM),
            'endDate' => (new \DateTimeImmutable('+1 day'))->format(DATE_ATOM),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode($payload),
            $this->authHeaders($this->lecturerUser),
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function studentCannotCreateNewLecture(): void
    {
        $payload = [
            'name' => 'Zakazana Historia',
            'studentLimit' => 30,
            'startDate' => (new \DateTimeImmutable('+1 day'))->format(DATE_ATOM),
            'endDate' => (new \DateTimeImmutable('+2 days'))->format(DATE_ATOM),
        ];

        $response = $this->makeRequest(
            'POST',
            '/lectures',
            json_encode($payload),
            $this->authHeaders($this->studentUser),
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function requestWithoutTokenIsRejected(): void
    {
        $response = $this->makeRequest('GET', '/lectures');

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function requestWithMalformedTokenIsRejected(): void
    {
        $response = $this->makeRequest(
            'GET',
            '/lectures',
            '',
            ['HTTP_AUTHORIZATION' => 'Bearer not-a-real-token'],
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function loginWithWrongPasswordIsRejected(): void
    {
        $response = $this->makeRequest(
            'POST',
            '/auth/login',
            json_encode(['userId' => (string)$this->studentUser->getId(), 'password' => 'wrong']),
            ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function lecturerCanRemoveStudentFromOwnLecture(): void
    {
        $studentId = (string)$this->studentUser->getId();
        $this->enroll('lecture-1', $this->studentUser);

        // lecture-1 is owned by lecturer-1 ($this->lecturerUser) — see ApiTestCase::addSampleLectures().
        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            '',
            $this->authHeaders($this->lecturerUser),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'removed']),
            $response->getContent(),
        );

        $this->assertNotContains(
            $studentId,
            $this->enrolledStudentIds('lecture-1'),
            'The student should no longer be enrolled in lecture-1',
        );
    }

    #[Test]
    public function studentCanRemoveOwnEnrollment(): void
    {
        $studentId = (string)$this->studentUser->getId();
        $this->enroll('lecture-1', $this->studentUser);

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            '',
            $this->authHeaders($this->studentUser),
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function unrelatedUserCannotRemoveStudentFromLecture(): void
    {
        $studentId = (string)$this->studentUser->getId();
        $this->enroll('lecture-1', $this->studentUser);

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            '',
            $this->authHeaders($this->otherStudentUser),
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertContains(
            $studentId,
            $this->enrolledStudentIds('lecture-1'),
            'An unauthorized removal must not take effect',
        );
    }

    #[Test]
    public function studentCanEnrollToLecture(): void
    {
        $response = $this->enroll('lecture-1', $this->studentUser);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'enrolled']),
            $response->getContent(),
        );

        $this->assertContains(
            (string)$this->studentUser->getId(),
            $this->enrolledStudentIds('lecture-1'),
            'The student should be enrolled in lecture-1',
        );
    }

    #[Test]
    public function cannotEnrollToLectureIfStudentLimitExceeded(): void
    {
        $this->httpClient->getContainer()->get(DatabaseClient::class)
            ->upsert('lectures', ['id' => 'lecture-2'], ['$set' => ['studentLimit' => 1]]);

        $this->assertEquals(200, $this->enroll('lecture-2', $this->studentUser)->getStatusCode());

        $response = $this->enroll('lecture-2', $this->otherStudentUser);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Student limit reached']),
            $response->getContent(),
        );
    }

    #[Test]
    public function cannotEnrollToLectureIfAlreadyStarted(): void
    {
        $lectureId = 'lecture-already-started';

        $this->httpClient->getContainer()->get(DatabaseClient::class)->upsert(
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
                ],
            ],
        );

        $response = $this->enroll($lectureId, $this->studentUser);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture already started']),
            $response->getContent(),
        );
    }

    #[Test]
    public function cannotEnrollToNonExistingLecture(): void
    {
        $response = $this->enroll('non-existing-lecture', $this->studentUser);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture not found']),
            $response->getContent(),
        );
    }

    #[Test]
    public function cannotEnrollToSameLectureMoreThanOnce(): void
    {
        $this->assertEquals(200, $this->enroll('lecture-1', $this->studentUser)->getStatusCode());
        $this->assertEquals(200, $this->enroll('lecture-1', $this->studentUser)->getStatusCode());

        $studentId = (string)$this->studentUser->getId();
        $occurrences = array_filter(
            $this->enrolledStudentIds('lecture-1'),
            static fn(string $id) => $id === $studentId,
        );

        $this->assertCount(1, $occurrences, 'The student should be enrolled in lecture-1 exactly once');
    }

    #[Test]
    public function studentCanFetchListOfEnrolledLectures(): void
    {
        $this->enroll('lecture-1', $this->studentUser);
        $this->enroll('lecture-2', $this->studentUser);

        $response = $this->makeRequest('GET', '/lectures/mine', '', $this->authHeaders($this->studentUser));

        $this->assertEquals(200, $response->getStatusCode());

        $ids = array_column(json_decode($response->getContent(), true), 'id');
        sort($ids);

        $this->assertEquals(['lecture-1', 'lecture-2'], $ids);
    }

    #[Test]
    public function enrolledLectureListIsScopedToTheAuthenticatedStudent(): void
    {
        $this->enroll('lecture-1', $this->studentUser);

        $response = $this->makeRequest('GET', '/lectures/mine', '', $this->authHeaders($this->otherStudentUser));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    #[Test]
    public function cannotRemoveStudentFromNonExistingLecture(): void
    {
        $response = $this->makeRequest(
            'DELETE',
            '/lectures/non-existing-lecture/students/' . (string)$this->studentUser->getId(),
            '',
            $this->authHeaders($this->studentUser),
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture not found']),
            $response->getContent(),
        );
    }

    /**
     * @return list<string>
     */
    private function enrolledStudentIds(string $lectureId): array
    {
        $enrollments = $this->httpClient->getContainer()
            ->get(LectureService::class)
            ->getEnrolledStudents($lectureId);

        return array_map(
            static fn($enrollment) => (string)$enrollment->getStudentId(),
            $enrollments->getItems(),
        );
    }
}
