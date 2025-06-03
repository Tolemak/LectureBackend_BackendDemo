<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use Gwo\AppsRecruitmentTask\Util\StringId;

final class LectureTest extends ApiTestCase
{
    /** @test */
    public function lecturerCanCreateNewLecture(): void
    {
        $lectureId = StringId::new()->__toString();
        $payload = [
            'id' => $lectureId,
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

        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'created']),
            $response->getContent()
        );

        $response = $this->makeRequest('GET', '/lectures');
        $lectures = json_decode($response->getContent(), true);

        $found = false;
        foreach ($lectures as $lecture) {
            if (isset($lecture['id']) && $lecture['id'] === $lectureId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Nowy wykład powinien być widoczny na liście wykładów');
    }

    /** @test */
    public function studentCannotCreateNewLecture(): void
    {
        $payload = [
            'id' => 'lecture-student',
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

        $response = $this->makeRequest(
            'DELETE',
            '/lectures/lecture-1/students/' . $studentId
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'removed']),
            $response->getContent()
        );

        $response = $this->makeRequest('GET', '/lectures');
        $lectures = json_decode($response->getContent(), true);

        $found = false;
        foreach ($lectures as $lecture) {
            if ($lecture['id'] === 'lecture-1' && isset($lecture['students']) && in_array($studentId, $lecture['students'], true)) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, 'Student nie powinien być już zapisany na wykład lecture-1');
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

        // Sprawdź, czy student jest na liście studentów wykładu
        $response = $this->makeRequest('GET', '/lectures');
        $lectures = json_decode($response->getContent(), true);

        $found = false;
        foreach ($lectures as $lecture) {
            if ($lecture['id'] === 'lecture-1' && isset($lecture['students']) && in_array((string)$this->studentUser->getId(), $lecture['students'], true)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Student powinien być zapisany na wykład lecture-1');
    }

    /** @test */
    public function cannotEnrollToLectureIfStudentLimitExceeded(): void
    {
        $this->httpClient->getContainer()->get(\Gwo\AppsRecruitmentTask\Persistence\DatabaseClient::class)
            ->upsert(
                'lectures',
                ['id' => 'lecture-2'],
                ['$set' => ['studentLimit' => 1, 'students' => []]]
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

        $this->httpClient->getContainer()->get(\Gwo\AppsRecruitmentTask\Persistence\DatabaseClient::class)
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
                        'students' => [],
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

        $response = $this->makeRequest('GET', '/lectures');
        $lectures = json_decode($response->getContent(), true);

        $count = 0;
        foreach ($lectures as $lecture) {
            if ($lecture['id'] === 'lecture-1' && isset($lecture['students'])) {
                foreach ($lecture['students'] as $studentId) {
                    if ($studentId === (string)$this->studentUser->getId()) {
                        $count++;
                    }
                }
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

        $response = $this->makeRequest('GET', '/lectures');
        $lectures = json_decode($response->getContent(), true);

        $enrolledLectures = [];
        foreach ($lectures as $lecture) {
            if (isset($lecture['students']) && in_array($studentId, $lecture['students'], true)) {
                $enrolledLectures[] = $lecture['id'];
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
            '/lectures/non-existing-lecture/students/' . $studentId
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Lecture not found']),
            $response->getContent()
        );
    }
}
