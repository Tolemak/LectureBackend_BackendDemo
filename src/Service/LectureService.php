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
    private const string LECTURES = 'lectures';

    public function __construct(
        private readonly DatabaseClient $databaseClient,
    ) {
    }

    public function getAllLectures(): LectureCollection
    {
        return $this->findLectures([]);
    }

    public function getLectureById(StringId $lectureId): ?Lecture
    {
        return $this->findLectures(['id' => (string)$lectureId])->getItems()[0] ?? null;
    }

    public function getLecturesForStudent(StringId $studentId): LectureCollection
    {
        return $this->findLectures(['studentIds' => (string)$studentId]);
    }

    public function createLecture(
        StringId $lecturerId,
        string $name,
        int $studentLimit,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): Lecture {
        $lecture = new Lecture(
            id: StringId::new(),
            lecturerId: $lecturerId,
            name: $name,
            studentLimit: $studentLimit,
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->databaseClient->upsert(
            self::LECTURES,
            ['id' => (string)$lecture->getId()],
            [
                '$set' => [
                    'id' => (string)$lecture->getId(),
                    'lecturerId' => (string)$lecture->getLecturerId(),
                    'name' => $lecture->getName(),
                    'studentLimit' => $lecture->getStudentLimit(),
                    'startDate' => $lecture->getStartDate()->format(DATE_ATOM),
                    'endDate' => $lecture->getEndDate()->format(DATE_ATOM),
                    'studentIds' => [],
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

        $enrolled = $this->databaseClient->updateOne(
            self::LECTURES,
            [
                'id' => $lectureId,
                'studentIds' => ['$ne' => $studentId],
                '$expr' => ['$lt' => [['$size' => ['$ifNull' => ['$studentIds', []]]], '$studentLimit']],
            ],
            ['$push' => ['studentIds' => $studentId]],
        );

        if (!$enrolled && !$this->isEnrolled($lectureId, $studentId)) {
            throw new StudentLimitReachedException();
        }
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

        $this->databaseClient->updateOne(
            self::LECTURES,
            ['id' => $lectureId],
            ['$pull' => ['studentIds' => $studentId]],
        );
    }

    public function getEnrolledStudents(string $lectureId): LectureEnrollmentCollection
    {
        $lectures = $this->databaseClient->getByQuery(
            self::LECTURES,
            ['id' => $lectureId],
            ['projection' => ['studentIds' => 1]],
        );

        $studentIds = $lectures[0]['studentIds'] ?? [];
        $enrollments = array_map(
            static fn(string $studentId) => new LectureEnrollment(new StringId($lectureId), new StringId($studentId)),
            is_array($studentIds) ? array_values($studentIds) : [],
        );
        return new LectureEnrollmentCollection($enrollments);
    }

    private function isEnrolled(string $lectureId, string $studentId): bool
    {
        $query = ['id' => $lectureId, 'studentIds' => $studentId];

        return $this->databaseClient->getByQuery(self::LECTURES, $query) !== [];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function findLectures(array $query): LectureCollection
    {
        return new LectureCollection(array_map(
            fn(array $data) => $this->hydrateLecture($data),
            $this->databaseClient->getByQuery(self::LECTURES, $query),
        ));
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
