<?php
declare(strict_types=1);


function startAppSession(): void
{
    // Si la session est déjà démarrée, on ne fait rien
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    /*
    Configuration du cookie de session.
    httponly : empêche JavaScript de lire le cookie.
    samesite :réduit les risques CSRF.
    secure : cookie envoyé seulement en HTTPS si HTTPS est actif.
    */
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();
}

//Vérifier si un utilisateur est connecté

function isLoggedIn(): bool
{
    startAppSession();

    return !empty($_SESSION['user_id']);
}

//Récupérer les informations de l'utilisateur connecté

function currentUser(): ?array
{
    startAppSession();
    if (!isLoggedIn()) {
        return null;
    }

    // Retourne les informations principales de l'utilisateur connecté
    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
    ];
}

//Cette fonction est utilisée dans les fichiers qui retournent du JSON :events.php, clubs.php, media.php...

function requireLoginJson(): void
{
    startAppSession();

    if (!isLoggedIn()) {
        http_response_code(401);

        echo json_encode([
            'status' => 'error',
            'errors' => ['Authentication required']
        ]);

        exit;
    }
}

//Bloquer une requête API si l'utilisateur n'a pas le bon rôle

function requireRoleJson(array $allowedRoles): void
{
    // Vérifie d'abord que l'utilisateur est connecté
    requireLoginJson();

    // Récupère le rôle de l'utilisateur connecté
    $role = $_SESSION['role'] ?? 'student';

    // Si le rôle n'est pas autorisé, on refuse l'accès
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);

        echo json_encode([
            'status' => 'error',
            'errors' => ['Access denied']
        ]);

        exit;
    }
}