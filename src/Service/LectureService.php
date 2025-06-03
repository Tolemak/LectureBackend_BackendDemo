<?php

namespace Gwo\AppsRecruitmentTask\Service;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Lecture\LectureCollection;
use Gwo\AppsRecruitmentTask\Util\StringId;

class LectureService
{
    public function __construct(
        private readonly DatabaseClient $databaseClient
    ) {}

    public function getAllLectures(): LectureCollection
    {
        $lecturesData = $this->databaseClient->getByQuery('lectures', []);
        $lectures = array_map(
            fn(array $data) => new Lecture(
                id: new StringId($data['id']),
                lecturerId: new StringId($data['lecturerId']),
                name: $data['name'],
                studentLimit: $data['studentLimit'],
                startDate: new \DateTimeImmutable($data['startDate']),
                endDate: new \DateTimeImmutable($data['endDate']),
                students: isset($data['students']) ? (array)$data['students'] : []
            ),
            $lecturesData
        );
        return new LectureCollection($lectures);
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
        $lectureId = isset($data['id']) ? new StringId($data['id']) : StringId::new();
        $lecture = new Lecture(
            id: $lectureId,
            lecturerId: new StringId($data['lecturerId']),
            name: $data['name'],
            studentLimit: $data['studentLimit'],
            startDate: new \DateTimeImmutable($data['startDate']),
            endDate: new \DateTimeImmutable($data['endDate']),
            students: $data['students'] ?? []
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
                    'students' => $lecture->getStudents(),
                ]
            ]
        );
    }

    public function enrollStudent(string $lectureId, string $studentId): void
    {
        $lectureCollection = $this->getAllLectures();
        $lectureIdObj = new StringId($lectureId);
        $lecture = null;
        foreach ($lectureCollection->getItems() as $l) {
            if ($l->getId()->equals($lectureIdObj)) {
                $lecture = $l;
                break;
            }
        }
        if (!$lecture) {
            throw new \InvalidArgumentException('Lecture not found');
        }

        $now = new \DateTimeImmutable();
        if ($lecture->getStartDate() <= $now) {
            throw new \RuntimeException('Lecture already started');
        }

        $students = $lecture->getStudents();
        if (in_array($studentId, $students, true)) {
            return;
        }
        if ($lecture->getStudentLimit() !== null && count($students) >= $lecture->getStudentLimit()) {
            throw new \RuntimeException('Student limit reached');
        }
        $students[] = $studentId;
        $this->databaseClient->upsert(
            'lectures',
            ['id' => (string)$lecture->getId()],
            [
                '$set' => [
                    'students' => $students,
                ]
            ]
        );
    }

    public function removeStudent(string $lectureId, string $studentId): void
    {
        $lectureCollection = $this->getAllLectures();
        $lectureIdObj = new StringId($lectureId);
        $lecture = null;
        foreach ($lectureCollection->getItems() as $l) {
            if ($l->getId()->equals($lectureIdObj)) {
                $lecture = $l;
                break;
            }
        }
        if (!$lecture) {
            throw new \InvalidArgumentException('Lecture not found');
        }
        $students = array_filter(
            $lecture->getStudents(),
            fn($id) => $id !== $studentId
        );
        $this->databaseClient->upsert(
            'lectures',
            ['id' => (string)$lecture->getId()],
            [
                '$set' => [
                    'students' => array_values($students),
                ]
            ]
        );
    }
}