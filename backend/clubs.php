<?php
header('Content-Type: application/json');
require_once 'ClubManager.php';

$response = ['status' => 'error', 'data' => null, 'errors' => []];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;

    if (!$action && !in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        http_response_code(400);
        $response['errors'][] = "Missing 'action' parameter";
        echo json_encode($response);
        exit;
    }

    $manager = new ClubManager();

    $parseJsonBody = function () {
        $body = file_get_contents('php://input');
        if ($body === false || trim($body) === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new Exception('Invalid JSON body');
        }
        return $decoded;
    };

    switch ($method) {
        case 'GET':
            if ($action === 'get') {
                $id = $_GET['id'] ?? null;
                if (!$id) throw new Exception("Missing club ID");
                $response['data'] = $manager->getClubById($id);
                $response['status'] = 'success';
            } elseif ($action === 'getAll') {
                $response['data'] = $manager->getAllClubs();
                $response['status'] = 'success';
            } elseif ($action === 'getByCategory') {
                $category = $_GET['category'] ?? null;
                if (!$category) throw new Exception("Missing category");
                $response['data'] = $manager->getClubsByCategory($category);
                $response['status'] = 'success';
            } elseif ($action === 'getCategories') {
                $response['data'] = $manager->getAllCategories();
                $response['status'] = 'success';
            } else {
                throw new Exception("Unsupported GET action");
            }
            break;

        case 'POST':
            $payload = $parseJsonBody();
            $response['data'] = $manager->createClub($payload);
            $response['status'] = 'success';
            http_response_code(201);
            break;

        case 'PUT':
        case 'PATCH':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Missing club ID");
            $payload = $parseJsonBody();
            $response['data'] = $manager->updateClub($id, $payload);
            $response['status'] = 'success';
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Missing club ID");
            $manager->deleteClub($id);
            $response['data'] = ['id' => $id, 'deleted' => true];
            $response['status'] = 'success';
            break;

        default:
            throw new Exception("Unsupported HTTP method");
    }

} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
