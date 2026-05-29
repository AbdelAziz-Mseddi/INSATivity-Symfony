<?php

namespace App\Security;

use App\Api\ApiResponse;
use App\Api\Serializer;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/** Returns the authenticated user in the standard JSON envelope after json_login. */
class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        $data = $user instanceof User
            ? Serializer::user($user)
            : ['username' => $token->getUserIdentifier()];

        return ApiResponse::success(['user' => $data], 200, 'Login successful');
    }
}
