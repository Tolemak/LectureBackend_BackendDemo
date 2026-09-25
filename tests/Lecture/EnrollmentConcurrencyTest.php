<?php

declare(strict_types=1);

namespace App\Tests\Lecture;

use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class EnrollmentConcurrencyTest extends ApiTestCase
{
    private const int WORKERS = 12;

    private const float START_DELAY_SECONDS = 3.0;

    #[Test]
    public function concurrentEnrollmentsNeverExceedTheStudentLimit(): void
    {
        $this->databaseClient()->upsert('lectures', ['id' => 'lecture-1'], ['$set' => ['studentLimit' => 3]]);

        $outcomes = $this->enrollConcurrently('lecture-1', array_map(
            static fn(int $i) => 'racer-' . $i,
            range(1, self::WORKERS),
        ));

        $this->assertCount(3, array_keys($outcomes, 'enrolled', true));
        $this->assertCount(self::WORKERS - 3, array_keys($outcomes, 'full', true));
        $this->assertCount(3, $this->enrolledStudentIds('lecture-1'));
    }

    #[Test]
    public function concurrentEnrollmentsOfOneStudentAreStoredOnce(): void
    {
        $outcomes = $this->enrollConcurrently('lecture-1', array_fill(0, self::WORKERS, 'student-1'));

        $this->assertSame(array_fill(0, self::WORKERS, 'enrolled'), $outcomes);
        $this->assertSame(['student-1'], $this->enrolledStudentIds('lecture-1'));
    }

    /**
     * @param list<string> $studentIds
     * @return list<string>
     */
    private function enrollConcurrently(string $lectureId, array $studentIds): array
    {
        $startAt = sprintf('%.6F', microtime(true) + self::START_DELAY_SECONDS);

        $workers = [];
        foreach ($studentIds as $studentId) {
            $output = (string)tempnam(sys_get_temp_dir(), 'enroll');
            $process = proc_open(
                [PHP_BINARY, __DIR__ . '/enroll-worker.php', $lectureId, $studentId, $startAt],
                [1 => ['file', $output, 'w'], 2 => ['file', $output . '.err', 'w']],
                $pipes,
            );
            self::assertIsResource($process);
            $workers[] = [$process, $output];
        }

        $outcomes = [];
        foreach ($workers as [$process, $output]) {
            $exitCode = proc_close($process);
            $outcomes[] = (string)file_get_contents($output);
            $errors = (string)file_get_contents($output . '.err');
            unlink($output);
            unlink($output . '.err');

            self::assertSame(0, $exitCode, $errors);
        }

        return $outcomes;
    }
}
