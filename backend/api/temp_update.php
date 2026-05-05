<?php
require_once __DIR__ . '/../config/Database.php';
try {
    $db = Database::connect();
    $stmt = $db->prepare('UPDATE public.events SET is_approved = TRUE');
    $stmt->execute();
    echo 'Successfully updated all events to is_approved = TRUE';
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage();
}
