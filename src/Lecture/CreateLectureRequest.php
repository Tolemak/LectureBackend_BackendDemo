<?php

declare(strict_types=1);

namespace App\Lecture;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateLectureRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\Positive]
        public int $studentLimit,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
    ) {
    }

    #[Assert\Callback]
    public function validateDateOrder(ExecutionContextInterface $context): void
    {
        if ($this->endDate <= $this->startDate) {
            $context->buildViolation('The end date must be later than the start date.')
                ->atPath('endDate')
                ->addViolation();
        }
    }
}
