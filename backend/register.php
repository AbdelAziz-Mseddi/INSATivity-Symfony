<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.html');
    exit;
}

//Récupération des données du formulaire

$fullName = trim($_POST['fullName'] ?? '');
$username = trim($_POST['username'] ?? '');
$emailInput = trim($_POST['email'] ?? '');
$major = trim($_POST['major'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$acceptTerms = isset($_POST['acceptTerms']);

$errors = [];

//Normalisation email

$emailInput = strtolower($emailInput);

if ($emailInput !== '' && str_contains($emailInput, '@')) {
    $email = $emailInput;
} else {
    $email = $emailInput . '@insat.ucar.tn';
}

//Validation des champs

if ($fullName === '') {
    $errors[] = 'full_name_required';
}

if ($username === '') {
    $errors[] = 'username_required';
} elseif (strlen($username) < 3) {
    $errors[] = 'username_too_short';
}

if ($emailInput === '') {
    $errors[] = 'email_required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email_invalid';
} elseif (!str_ends_with($email, '@insat.ucar.tn')) {
    $errors[] = 'email_domain_invalid';
}

$allowedMajors = ['MPI', 'CBA', 'GL', 'RT', 'IIA', 'IMI', 'BIO', 'CH'];

if ($major === '') {
    $errors[] = 'major_required';
} elseif (!in_array($major, $allowedMajors, true)) {
    $errors[] = 'major_invalid';
}

if ($password === '') {
    $errors[] = 'password_required';
} elseif (strlen($password) < 6) {
    $errors[] = 'password_too_short';
}

if ($confirmPassword === '') {
    $errors[] = 'confirm_password_required';
} elseif ($password !== $confirmPassword) {
    $errors[] = 'passwords_not_match';
}

if (!$acceptTerms) {
    $errors[] = 'terms_required';
}

//Si erreur de validation, retour vers register.html

if (!empty($errors)) {
    $errorCode = $errors[0];
    header('Location: ../pages/register.html?error=' . urlencode($errorCode));
    exit;
}

try {
    $pdo = Database::connect();

    //Vérifier si username ou email existe déjà

    $stmt = $pdo->prepare(
        "SELECT id, username, email
         FROM users
         WHERE username = :username OR email = :email
         LIMIT 1"
    );

    $stmt->execute([
        'username' => $username,
        'email' => $email,
    ]);

    $existingUser = $stmt->fetch();

    if ($existingUser) {
        if ($existingUser['username'] === $username) {
            header('Location: ../pages/register.html?error=username_exists');
            exit;
        }

        if ($existingUser['email'] === $email) {
            header('Location: ../pages/register.html?error=email_exists');
            exit;
        }
    }

    //Hasher le mot de passe
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    //Insertion réelle dans la table users
  
    $insert = $pdo->prepare(
        "INSERT INTO users (
            full_name,
            username,
            email,
            major,
            password_hash,
            role
        )
        VALUES (
            :full_name,
            :username,
            :email,
            :major,
            :password_hash,
            :role
        )"
    );

    $insert->execute([
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'major' => $major,
        'password_hash' => $passwordHash,
        'role' => 'student',
    ]);

    //Inscription réussie
   
    header('Location: ../pages/login.html?success=registered');
    exit;

} catch (PDOException $e) {
    //Erreur BDD
    error_log($e->getMessage());

    if ($e->getCode() === '23505') {
        header('Location: ../pages/register.html?error=already_exists');
        exit;
    }

    header('Location: ../pages/register.html?error=server');
    exit;
}
