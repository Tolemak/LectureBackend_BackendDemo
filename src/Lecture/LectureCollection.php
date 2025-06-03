<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\Collection\Collection;

final class LectureCollection extends Collection
{
    public function toArray(): array
    {
        return array_map(
            fn(Lecture $lecture) => [
                'id' => (string)$lecture->getId(),
                'lecturerId' => (string)$lecture->getLecturerId(),
                'name' => $lecture->getName(),
                'studentLimit' => $lecture->getStudentLimit(),
                'startDate' => $lecture->getStartDate()->format(DATE_ATOM),
                'endDate' => $lecture->getEndDate()->format(DATE_ATOM)
            ],
            $this->getItems()
        );
    }
}