<?php

namespace App\Security;

use App\Api\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/** Returns a 401 JSON error when json_login authentication fails. */
class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return ApiResponse::error('Invalid credentials', 'INVALID_CREDENTIALS', 401);
    }
}
