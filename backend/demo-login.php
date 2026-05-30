<?php
// Temporary demo login - for demo video purposes only
require_once __DIR__ . '/middleware/AuthMiddleware.php';

AuthMiddleware::startSession();

// Set a demo user session
$_SESSION['user'] = [
    'id' => 'demo-user',
    'username' => 'demo',
    'email' => 'demo@insativity.test',
    'club_id' => 'acm',
    'role' => 'admin'
];

header('Location: ../pages/club-dashboard.html?club=acm');
exit;
?>
