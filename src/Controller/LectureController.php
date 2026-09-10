<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lecture\CreateLectureRequest;
use App\Lecture\Exception\LectureAlreadyStartedException;
use App\Lecture\Exception\LectureNotFoundException;
use App\Lecture\Exception\NotAuthorizedException;
use App\Lecture\Exception\StudentLimitReachedException;
use App\Security\ApiUser;
use App\Service\LectureService;
use App\Util\StringId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lectures')]
class LectureController extends AbstractController
{
    public function __construct(
        private readonly LectureService $lectureService,
    ) {
    }

    #[Route('', methods: ['GET'], name: 'lecture_list')]
    public function listLectures(): JsonResponse
    {
        $lectures = $this->lectureService->getAllLectures();
        return new JsonResponse($lectures->toArray(), Response::HTTP_OK);
    }

    #[Route('/mine', methods: ['GET'], name: 'lecture_list_mine')]
    public function listMyLectures(): JsonResponse
    {
        $lectures = $this->lectureService->getLecturesForStudent(new StringId($this->currentUserId()));
        return new JsonResponse($lectures->toArray(), Response::HTTP_OK);
    }

    #[Route('', methods: ['POST'], name: 'lecture_create')]
    #[IsGranted('ROLE_LECTURER')]
    public function createLecture(#[MapRequestPayload] CreateLectureRequest $payload): JsonResponse
    {
        $lecture = $this->lectureService->createLecture(
            new StringId($this->currentUserId()),
            $payload->name,
            $payload->studentLimit,
            $payload->startDate,
            $payload->endDate,
        );

        return new JsonResponse(['status' => 'created', 'id' => (string)$lecture->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}/enroll', methods: ['POST'], name: 'lecture_enroll')]
    #[IsGranted('ROLE_STUDENT')]
    public function enroll(string $id): JsonResponse
    {
        try {
            $this->lectureService->enrollStudent($id, $this->currentUserId());
        } catch (StudentLimitReachedException | LectureAlreadyStartedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (LectureNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'enrolled'], Response::HTTP_OK);
    }

    #[Route('/{id}/students/{studentId}', methods: ['DELETE'], name: 'lecture_remove_student')]
    public function removeStudent(string $id, string $studentId): JsonResponse
    {
        try {
            $this->lectureService->removeStudent($id, $studentId, $this->currentUserId());
        } catch (LectureNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotAuthorizedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['status' => 'removed'], Response::HTTP_OK);
    }

    private function currentUserId(): string
    {
        $user = $this->getUser();
        if (!$user instanceof ApiUser) {
            throw $this->createAccessDeniedException();
        }

        return $user->getUserIdentifier();
    }
}
