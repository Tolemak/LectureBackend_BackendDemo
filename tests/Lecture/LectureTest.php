<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;

final class LectureTest extends ApiTestCase
{
    /** @test */
    public function lecturerCanCreateNewLecture(): void
    {
        $payload = [
            'id' => 'lecture-new',
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
            if (isset($lecture['id']) && $lecture['id'] === 'lecture-new') {
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
        $this->markTestIncomplete('Not implemented');
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
        $this->markTestIncomplete('Not implemented');
    }

    /** @test */
    public function cannotEnrollToLectureIfAlreadyStarted(): void
    {
        $this->markTestIncomplete('Not implemented');
    }

    /** @test */
    public function cannotEnrollToSameLectureMoreThanOnce(): void
    {
        $this->markTestIncomplete('Not implemented');
    }

    /** @test */
    public function studentCanFetchListOfEnrolledLectures(): void
    {
        $this->markTestIncomplete('Not implemented');
    }
}
