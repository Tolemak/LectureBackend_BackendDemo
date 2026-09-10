<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuthController
{
    /**
     * The json_login listener on the `login` firewall handles this request and
     * returns a token, so this body only runs if that firewall is misconfigured.
     */
    #[Route('/auth/login', methods: ['POST'], name: 'auth_login')]
    public function login(): JsonResponse
    {
        return new JsonResponse(['error' => 'Authentication is not configured.'], 500);
    }
}
