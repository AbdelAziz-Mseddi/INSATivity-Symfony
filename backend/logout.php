<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

//demarre la session pour pouvoir la détruire.

startAppSession();

//Vide toutes les données stockées dans $_SESSION.

$_SESSION = [];

//Supprime le cookie de session côté navigateur.

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'] ?? '',
        $params['secure'],
        $params['httponly']
    );
}

//détruit la session côté serveur.

session_destroy();

header('Location: ../pages/login.html?success=logout');
exit;