<?php

namespace Gwo\AppsRecruitmentTask\Service;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Lecture\LectureCollection;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollment;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentCollection;
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
                endDate: new \DateTimeImmutable($data['endDate'])
            ),
            $lecturesData
        );
        return new LectureCollection($lectures);
    }

    public function canCreateLecture(string $userId): bool
    {
        $users = $this->databaseClient->getByQuery('user', ['id' => $userId]);
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
        $lectureCollection = $this->getAllLectures();
        $lectureIdObj = new StringId($lectureId);
        $lecture = null;
        foreach ($lectureCollection->getItems() as $l) {
            if ($l->getId()->equals($lectureIdObj)) {
                $lecture = $l;
                break;
            }
        }

        $now = new \DateTimeImmutable();
        if ($lecture->getStartDate() <= $now) {
            throw new \RuntimeException('Lecture already started');
        }

        $enrollmentsData = $this->databaseClient->getByQuery('lecture_enrollments', ['lectureId' => (string)$lectureId]);
        $enrollments = array_map(
            fn($e) => new LectureEnrollment(
                new StringId($e['lectureId']),
                new StringId($e['studentId'])
            ),
            $enrollmentsData
        );
        $enrollmentCollection = new LectureEnrollmentCollection($enrollments);

        if ($lecture->getStudentLimit() !== null && $enrollmentCollection->count() >= $lecture->getStudentLimit()) {
            throw new \RuntimeException('Student limit reached');
        }

        $alreadyEnrolled = $enrollmentCollection->filter(
            fn(LectureEnrollment $e) => $e->getStudentId()->equals(new StringId($studentId))
        );
        if ($alreadyEnrolled->count() > 0) {
            return;
        }

        $this->databaseClient->upsert(
            'lecture_enrollments',
            [
                'lectureId' => (string)$lectureId,
                'studentId' => (string)$studentId
            ],
            [
                '$set' => [
                    'lectureId' => (string)$lectureId,
                    'studentId' => (string)$studentId
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

        $enrollment = new LectureEnrollment(
            new StringId($lectureId),
            new StringId($studentId)
        );

        $this->databaseClient->upsert(
            'lecture_enrollments',
            [
                'lectureId' => (string)$enrollment->getLectureId(),
                'studentId' => (string)$enrollment->getStudentId()
            ],
            [
                '$unset' => [
                    'lectureId' => '',
                    'studentId' => ''
                ]
            ]
        );
    }

    public function getEnrolledStudents(string $lectureId): LectureEnrollmentCollection
    {
        $enrollmentsData = $this->databaseClient->getByQuery('lecture_enrollments', ['lectureId' => (string)$lectureId]);
        $enrollments = array_map(
            fn($e) => new LectureEnrollment(
                new StringId($e['lectureId']),
                new StringId($e['studentId'])
            ),
            $enrollmentsData
        );
        return new LectureEnrollmentCollection($enrollments);
    }
}