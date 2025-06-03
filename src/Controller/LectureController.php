<?php

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Service\LectureService;
use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
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
        private readonly DatabaseClient $databaseClient
    ) {}

    #[Route('', methods: ['GET'], name: 'lecture_list')]
    public function listLectures(): JsonResponse
    {
        $lectures = $this->lectureService->getAllLectures();
        return new JsonResponse($lectures, Response::HTTP_OK);
    }

    #[Route('', methods: ['POST'], name: 'lecture_create')]
    public function createLecture(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $lecturerId = $data['lecturerId'] ?? null;

        if (!$lecturerId) {
            return new JsonResponse(['error' => 'lecturerId required'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->lectureService->canCreateLecture($lecturerId)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->lectureService->createLecture($data);
        return new JsonResponse(['status' => 'created'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/enroll', methods: ['POST'], name: 'lecture_enroll')]
    public function enroll(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $studentId = $data['studentId'] ?? null;
        if ($studentId) {
            $this->lectureService->enrollStudent($id, $studentId);
            return new JsonResponse(['status' => 'enrolled'], Response::HTTP_OK);
        }
        return new JsonResponse(['error' => 'studentId required'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}/students/{studentId}', methods: ['DELETE'], name: 'lecture_remove_student')]
    public function removeStudent(string $id, string $studentId): JsonResponse
    {
        $this->lectureService->removeStudent($id, $studentId);
        return new JsonResponse(['status' => 'removed'], Response::HTTP_OK);
    }
}