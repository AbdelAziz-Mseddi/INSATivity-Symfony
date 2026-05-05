<?php
declare(strict_types=1);

// Connexion BDD
require_once __DIR__ . '/config/database.php';

// Fonctions de session
require_once __DIR__ . '/auth.php';

startAppSession();

// Vérifier que la requête vient bien du formulaire login

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.html');
    exit;
}


$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validation  des champs

if ($username === '' || $password === '') {
    header('Location: ../pages/login.html?error=empty');
    exit;
}

try {
    //Connexion à la base de données.

    $pdo = getPDO();

    //Recherche de l'utilisateur dans la table users.

    $stmt = $pdo->prepare(
        "SELECT id, full_name, username, email, password_hash, role
         FROM users
         WHERE username = :login OR email = :login
         LIMIT 1"
    );

    $stmt->execute(['login' => $username ]);

    // Récupère l'utilisateur trouvé
    $user = $stmt->fetch();

    
   // Si aucun utilisateur n'est trouvé,on retourne vers login avec un message d'inexistence.
    
    if (!$user) {
        header('Location: ../pages/login.html?error=not_found');
        exit;
    }

    //Vérification du mot de passe.

    if (!password_verify($password, $user['password_hash'])) {
        header('Location: ../pages/login.html?error=wrong_password');
        exit;
    }

    /*
    Sécurité :
    on régénère l'identifiant de session après un login réussi.
    Cela limite les attaques de fixation de session.
    */
    session_regenerate_id(true);

    //Stockage des informations utiles dans la session.
   
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    //Login réussi :on redirige vers la page principale.
    
    header('Location: ../pages/index.html');
    exit;
} catch (Throwable $e) {
    
    error_log($e->getMessage());
    header('Location: ../pages/login.html?error=server');
    exit;
}

