<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';

// Récupérer l'utilisateur actuellement connecté

$user = currentUser();

// Si aucun utilisateur n'est connecté on retourne une réponse JSON avec authenticated = false.

if (!$user) {
    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'authenticated' => false,
        'user' => null
    ]);

    exit;
}

// si l'utilisateur est connecté on retourne ses informations principales.

echo json_encode([
    'status' => 'success',
    'authenticated' => true,
    'user' => $user
]);