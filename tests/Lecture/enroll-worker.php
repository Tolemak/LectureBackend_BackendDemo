<?php

declare(strict_types=1);

use App\Kernel;
use App\Lecture\Exception\StudentLimitReachedException;
use App\Service\LectureService;
use Psr\Container\ContainerInterface;

require dirname(__DIR__) . '/bootstrap.php';

[, $lectureId, $studentId, $startAt] = array_map('strval', $_SERVER['argv']) + ['', '', '', '0'];

$kernel = new Kernel('test', true);
$kernel->boot();

$container = $kernel->getContainer()->get('test.service_container');
$lectureService = $container instanceof ContainerInterface ? $container->get(LectureService::class) : null;
if (!$lectureService instanceof LectureService) {
    fwrite(STDERR, 'LectureService is not available.');
    exit(1);
}

time_sleep_until(max((float)$startAt, microtime(true) + 0.001));

try {
    $lectureService->enrollStudent($lectureId, $studentId);
    echo 'enrolled';
} catch (StudentLimitReachedException) {
    echo 'full';
}

$kernel->shutdown();
