<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lecture\Exception\InvalidLecturePayloadException;
use App\Lecture\Exception\LectureAlreadyStartedException;
use App\Lecture\Exception\LectureNotFoundException;
use App\Lecture\Exception\NotAuthorizedException;
use App\Lecture\Exception\StudentLimitReachedException;
use App\Service\LectureService;
use App\Util\StringId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

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

    #[Route('', methods: ['POST'], name: 'lecture_create')]
    public function createLecture(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $lecturerId = $data['lecturerId'] ?? null;

        if (!$this->lectureService->canCreateLecture($lecturerId === null ? null : new StringId($lecturerId))) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $lecture = $this->lectureService->createLecture($data);
        } catch (InvalidLecturePayloadException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'created', 'id' => (string)$lecture->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}/enroll', methods: ['POST'], name: 'lecture_enroll')]
    public function enroll(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $studentId = $data['studentId'] ?? null;

        try {
            $this->lectureService->enrollStudent($id, $studentId);
        } catch (StudentLimitReachedException | LectureAlreadyStartedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (LectureNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'enrolled'], Response::HTTP_OK);
    }

    #[Route('/{id}/students/{studentId}', methods: ['DELETE'], name: 'lecture_remove_student')]
    public function removeStudent(string $id, string $studentId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $requesterId = $data['requesterId'] ?? null;

        if ($requesterId === null) {
            return new JsonResponse(['error' => 'Missing requesterId'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->lectureService->removeStudent($id, $studentId, $requesterId);
        } catch (LectureNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotAuthorizedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
        return new JsonResponse(['status' => 'removed'], Response::HTTP_OK);
    }
}
