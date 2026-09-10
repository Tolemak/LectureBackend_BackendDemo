<?php

declare(strict_types=1);

namespace App\Lecture;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class CreateLectureRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name = '',
        #[Assert\Positive]
        public int $studentLimit = 0,
        #[Assert\NotNull]
        public ?\DateTimeImmutable $startDate = null,
        #[Assert\NotNull]
        public ?\DateTimeImmutable $endDate = null,
    ) {
    }

    #[Assert\Callback]
    public function validateDateOrder(ExecutionContextInterface $context): void
    {
        if ($this->startDate === null || $this->endDate === null) {
            return;
        }

        if ($this->endDate <= $this->startDate) {
            $context->buildViolation('The end date must be later than the start date.')
                ->atPath('endDate')
                ->addViolation();
        }
    }
}
