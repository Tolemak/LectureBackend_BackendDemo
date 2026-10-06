<?php

declare(strict_types=1);

namespace App\Tests\Lecture;

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
            self::json($payload),
            $this->authHeaders($this->lecturerUser),
        );

        $this->assertEquals(201, $response->getStatusCode());

        $created = self::decodeJson($response);
        $this->assertEquals('created', $created['status']);
        $this->assertNotEmpty($created['id']);

        $response = $this->makeRequest('GET', '/lectures', '', $this->authHeaders($this->lecturerUser));
        $lectures = self::decodeJson($response);

        $ids = array_column($lectures, 'id');
        $this->assertContains($created['id'], $ids, 'The new lecture should show up in the lecture list');
    }

    #[Test]
    public function creatingLectureWithMissingFieldsIsRejected(): void
    {
        $response = $this->makeRequest(
            'POST',
            '/lectures',
            self::json([]),
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
            self::json($payload),
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
            self::json($payload),
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
            self::json(['userId' => (string)$this->studentUser->getId(), 'password' => 'wrong']),
            ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function lecturerCanRemoveStudentFromOwnLecture(): void
    {
        $studentId = (string)$this->studentUser->getId();
        $this->enroll('lecture-1', $this->studentUser);

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId,
            '',
            $this->authHeaders($this->lecturerUser),
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            self::json(['status' => 'removed']),
            self::body($response),
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
            self::json(['status' => 'enrolled']),
            self::body($response),
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
        $this->databaseClient()
            ->upsert('lectures', ['id' => 'lecture-2'], ['$set' => ['studentLimit' => 1]]);

        $this->assertEquals(200, $this->enroll('lecture-2', $this->studentUser)->getStatusCode());

        $response = $this->enroll('lecture-2', $this->otherStudentUser);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            self::json(['error' => 'Student limit reached']),
            self::body($response),
        );
    }

    #[Test]
    public function cannotEnrollToLectureIfAlreadyStarted(): void
    {
        $lectureId = 'lecture-already-started';

        $this->databaseClient()->upsert(
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
            self::json(['error' => 'Lecture already started']),
            self::body($response),
        );
    }

    #[Test]
    public function cannotEnrollToNonExistingLecture(): void
    {
        $response = $this->enroll('non-existing-lecture', $this->studentUser);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            self::json(['error' => 'Lecture not found']),
            self::body($response),
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

        $ids = array_column(self::decodeJson($response), 'id');
        sort($ids);

        $this->assertEquals(['lecture-1', 'lecture-2'], $ids);
    }

    #[Test]
    public function enrolledLectureListIsScopedToTheAuthenticatedStudent(): void
    {
        $this->enroll('lecture-1', $this->studentUser);

        $response = $this->makeRequest('GET', '/lectures/mine', '', $this->authHeaders($this->otherStudentUser));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], self::decodeJson($response));
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
            self::json(['error' => 'Lecture not found']),
            self::body($response),
        );
    }
}
