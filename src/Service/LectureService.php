<?php

declare(strict_types=1);

namespace App\Service;

use App\Persistence\DatabaseClient;
use App\Lecture\Exception\LectureAlreadyStartedException;
use App\Lecture\Exception\LectureNotFoundException;
use App\Lecture\Exception\NotAuthorizedException;
use App\Lecture\Exception\StudentLimitReachedException;
use App\Lecture\Lecture;
use App\Lecture\LectureCollection;
use App\Lecture\LectureEnrollment;
use App\Lecture\LectureEnrollmentCollection;
use App\Util\StringId;

class LectureService
{
    public function __construct(
        private readonly DatabaseClient $databaseClient,
    ) {
    }

    public function getAllLectures(): LectureCollection
    {
        $lecturesData = $this->databaseClient->getByQuery('lectures', []);
        $lectures = array_map(
            fn(array $data) => $this->hydrateLecture($data),
            $lecturesData,
        );
        return new LectureCollection($lectures);
    }

    public function getLectureById(StringId $lectureId): ?Lecture
    {
        $lecturesData = $this->databaseClient->getByQuery('lectures', ['id' => (string)$lectureId]);
        if (count($lecturesData) === 0) {
            return null;
        }
        return $this->hydrateLecture($lecturesData[0]);
    }

    public function getLecturesForStudent(StringId $studentId): LectureCollection
    {
        $enrollments = $this->databaseClient->getByQuery(
            'lecture_enrollments',
            ['studentId' => (string)$studentId],
        );

        $lectures = [];
        foreach ($enrollments as $enrollment) {
            $lecture = $this->getLectureById(new StringId($enrollment['lectureId']));
            if ($lecture !== null) {
                $lectures[] = $lecture;
            }
        }

        return new LectureCollection($lectures);
    }

    public function createLecture(
        StringId $lecturerId,
        string $name,
        int $studentLimit,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): Lecture {
        // The id is always server-generated — a client-supplied id would let
        // callers overwrite an existing lecture via upsert.
        $lecture = new Lecture(
            id: StringId::new(),
            lecturerId: $lecturerId,
            name: $name,
            studentLimit: $studentLimit,
            startDate: $startDate,
            endDate: $endDate,
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
                ],
            ],
        );

        return $lecture;
    }

    public function enrollStudent(string $lectureId, string $studentId): void
    {
        $lecture = $this->getLectureById(new StringId($lectureId));
        if ($lecture === null) {
            throw new LectureNotFoundException();
        }

        if ($lecture->getStartDate() <= new \DateTimeImmutable()) {
            throw new LectureAlreadyStartedException();
        }

        $enrollmentCollection = $this->getEnrolledStudents($lectureId);

        if ($enrollmentCollection->count() >= $lecture->getStudentLimit()) {
            throw new StudentLimitReachedException();
        }

        $alreadyEnrolled = $enrollmentCollection->filter(
            fn(LectureEnrollment $e) => $e->getStudentId()->equals(new StringId($studentId)),
        );
        if ($alreadyEnrolled->count() > 0) {
            return;
        }

        $this->databaseClient->upsert(
            'lecture_enrollments',
            [
                'lectureId' => $lectureId,
                'studentId' => $studentId,
            ],
            [
                '$set' => [
                    'lectureId' => $lectureId,
                    'studentId' => $studentId,
                ],
            ],
        );
    }

    public function removeStudent(string $lectureId, string $studentId, string $requesterId): void
    {
        $lecture = $this->getLectureById(new StringId($lectureId));
        if ($lecture === null) {
            throw new LectureNotFoundException();
        }

        $isOwningLecturer = $lecture->getLecturerId()->equals(new StringId($requesterId));
        $isSelfRemoval = $studentId === $requesterId;
        if (!$isOwningLecturer && !$isSelfRemoval) {
            throw new NotAuthorizedException();
        }

        $this->databaseClient->delete(
            'lecture_enrollments',
            [
                'lectureId' => $lectureId,
                'studentId' => $studentId,
            ],
        );
    }

    public function getEnrolledStudents(string $lectureId): LectureEnrollmentCollection
    {
        $enrollmentsData = $this->databaseClient->getByQuery('lecture_enrollments', ['lectureId' => $lectureId]);
        $enrollments = array_map(
            fn(array $e) => new LectureEnrollment(
                new StringId($e['lectureId']),
                new StringId($e['studentId']),
            ),
            $enrollmentsData,
        );
        return new LectureEnrollmentCollection($enrollments);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrateLecture(array $data): Lecture
    {
        return new Lecture(
            id: new StringId($data['id']),
            lecturerId: new StringId($data['lecturerId']),
            name: $data['name'],
            studentLimit: $data['studentLimit'],
            startDate: new \DateTimeImmutable($data['startDate']),
            endDate: new \DateTimeImmutable($data['endDate']),
        );
    }
}
