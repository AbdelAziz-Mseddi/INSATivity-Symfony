<?php

require_once 'AuthManager.php';

# Blocks anyone who tries to access this script directly in their browser (which would be a GET request).
# Only accepts POST — the method used when submitting a form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<h3>Method Not Allowed</h3>';
    echo "<a href='../pages/login.html'>GO BACK, PEASANT!</a>";
    exit;
}

$identifier = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if ($identifier === '') {
    $errors[] = 'Username or university email is required.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
}

if (count($errors) > 0) {
    echo '<h3>Errors:</h3>';
    foreach ($errors as $error) {
        echo "<p style='color:red;'>{$error}</p>";
    }
    echo "<a href='../pages/login.html'>Go Back</a>";
    exit;
}

if (!str_contains($identifier, '@')) {
    $identifier = strtolower($identifier);
}

try {
    $authManager = new AuthManager();
    $user = $authManager->loginUser($identifier, $password);

    session_start();
    $_SESSION['user'] = [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'email' => $user['email'],
        'major' => $user['major']
    ];

    header('Location: ../pages/index.html');
    exit;
} catch (Exception $e) {
    http_response_code(401);
    echo '<h3>Login failed:</h3>';
    echo '<p style="color:red;">' . $e->getMessage() . '</p>';
    echo "<a href='../pages/login.html'>Go Back</a>";
}

?>