<?php
header('Content-Type: application/json');
require_once 'MediaManager.php';

$response = ['status' => 'error', 'data' => null, 'errors' => []];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;
    if (!$action) throw new Exception('Missing action');

    $manager = new MediaManager();

    if ($method === 'POST' && $action === 'upload') {
        if (!isset($_FILES['file'])) throw new Exception('No file');
        $prefix = $_GET['prefix'] ?? 'media';
        $response['data'] = $manager->upload($_FILES['file'], $prefix);
        $response['status'] = 'success';
        http_response_code(201);
    } elseif ($method === 'DELETE' && $action === 'delete') {
        $filePath = $_GET['filePath'] ?? null;
        if (!$filePath) throw new Exception('Missing filePath');
        $manager->delete($filePath);
        $response['data'] = ['deleted' => true];
        $response['status'] = 'success';
    } else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
