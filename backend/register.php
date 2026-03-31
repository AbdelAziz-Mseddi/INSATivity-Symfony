<?php

require_once 'AuthManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<h3>Method Not Allowed</h3>';
    echo "<a href='../pages/register.html'>Go Back</a>";
    exit;
}

$fullName = trim($_POST['fullName'] ?? '');
$username = trim($_POST['username'] ?? '');
$emailLocalPart = trim($_POST['email'] ?? '');
$major = trim($_POST['major'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$acceptTerms = isset($_POST['acceptTerms']) ? $_POST['acceptTerms'] : '';

$errors = [];

if ($fullName === '') {
    $errors[] = 'Full Name is required.';
}

if ($username === '') {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
    $errors[] = 'Username must be 3-50 chars and contain only letters, numbers, _, ., -';
}

if ($emailLocalPart === '') {
    $errors[] = 'University email is required.';
} elseif (!preg_match('/^[a-zA-Z0-9._%+-]+$/', $emailLocalPart)) {
    $errors[] = 'Invalid university email format.';
}

if ($major === '') {
    $errors[] = 'Major is required.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if ($confirmPassword === '') {
    $errors[] = 'Password confirmation is required.';
} elseif ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if ($acceptTerms === '') {
    $errors[] = 'You must accept the terms & data privacy policy.';
}

if (count($errors) > 0) {
    echo '<h3>Errors:</h3>';
    foreach ($errors as $error) {
        echo "<p style='color:red;'>{$error}</p>";
    }
    echo "<a href='../pages/register.html'>Go Back</a>";
    exit;
}

try {
    $authManager = new AuthManager();
    $user = $authManager->registerUser($fullName, $username, $emailLocalPart, $major, $password);

    session_start();
    $_SESSION['user'] = [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'email' => $user['email'],
        'major' => $user['major']
    ];

    echo "<h3 style='color:green;'>Registration successful!</h3>";
    echo "<p>Welcome, {$user['full_name']}.</p>";
    echo "<p>Account created for {$user['email']}.</p>";
    echo "<a href='../pages/login.html'>Continue to Login</a>";
} catch (Exception $e) {
    http_response_code(400);
    echo '<h3>Registration failed:</h3>';
    echo "<p style='color:red;'>{$e->getMessage()}</p>";
    echo "<a href='../pages/register.html'>Go Back</a>";
}

?>
