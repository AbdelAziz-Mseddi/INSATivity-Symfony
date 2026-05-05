<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/JWT.php';

class AuthMiddleware {
    public static function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { 
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public static function authenticate() {
        $authHeader = self::getAuthorizationHeader();

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::error('Authentication required. Missing Bearer token.', 'AUTH_MISSING', 401);
        }

        $token = $matches[1];
        $decoded = JWT::decode($token);

        if (!$decoded) {
            Response::error('Invalid or expired token.', 'AUTH_INVALID', 401);
        }

        return $decoded; 
    }

    public static function requireRole($allowedRoles) {
        $user = self::authenticate();
        $role = $user['role'] ?? 'student';

        if (!in_array($role, $allowedRoles, true)) {
            Response::error('Access denied. Insufficient permissions.', 'FORBIDDEN', 403);
        }

        return $user;
    }
}
