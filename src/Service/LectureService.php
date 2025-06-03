<?php

namespace Gwo\AppsRecruitmentTask\Service;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Util\StringId;

class LectureService
{
    public function __construct(
        private readonly DatabaseClient $databaseClient
    ) {}

    public function getAllLectures(): array
    {
        return $this->databaseClient->getByQuery('lectures', []);
    }

    public function canCreateLecture(string $userId): bool
    {
        $users = $this->databaseClient->getByQuery('user', ['id' => $userId]);
        if (empty($users)) {
            return false;
        }
        return ($users[0]['role'] ?? null) === 'lecturer';
    }

    public function createLecture(array $data): void
    {
        $lecture = new Lecture(
            id: new StringId($data['id']),
            lecturerId: new StringId($data['lecturerId']),
            name: $data['name'],
            studentLimit: $data['studentLimit'],
            startDate: new \DateTimeImmutable($data['startDate']),
            endDate: new \DateTimeImmutable($data['endDate'])
        );

        $this->databaseClient->upsert(
            'lectures',
            ['id' => (string)$lecture->getId()],
            [
                '$set' => [
                    'id' => (string)$lecture->getId(),
                    'lecturerId' => (string)$lecture->getLecturerId(),
                    'name' => $lecture->getName(),
                    'studentLimit' => $lecture->getStudentLimit(),
                    'startDate' => $lecture->getStartDate()->format(DATE_ATOM),
                    'endDate' => $lecture->getEndDate()->format(DATE_ATOM),
                ]
            ]
        );
    }

    public function enrollStudent(string $lectureId, string $studentId): void
    {
        $lectures = $this->databaseClient->getByQuery('lectures', ['id' => $lectureId]);
        if (empty($lectures)) {
            throw new \InvalidArgumentException('Lecture not found');
        }
        $lecture = $lectures[0];
        $students = $lecture['students'] ?? [];
        if (in_array($studentId, $students, true)) {
            return;
        }
        if (isset($lecture['studentLimit']) && count($students) >= $lecture['studentLimit']) {
            throw new \RuntimeException('Student limit reached');
        }
        $students[] = $studentId;
        $this->databaseClient->upsert(
            'lectures',
            ['id' => $lectureId],
            [
                '$set' => [
                    'students' => $students,
                ]
            ]
        );
    }

    public function removeStudent(string $lectureId, string $studentId): void
    {
        // Implementacja logiki usuwania ucznia z wykładu
    }
}