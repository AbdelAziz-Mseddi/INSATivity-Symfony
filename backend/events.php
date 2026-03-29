<?php
#the big guy, the router
header('Content-Type: application/json');

require_once 'EventManager.php';

$response = ['status' => 'error', 'message' => '', 'data' => null, 'errors' => []];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;

    if (!$action) {
        http_response_code(400);
        $response['errors'][] = "Missing 'action' parameter";
        echo json_encode($response);
        exit;
    }

    $manager = new EventManager();

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
                if (!$id) throw new Exception("Missing event ID");
                $response['data'] = $manager->getEventById($id);
                $response['status'] = 'success';
            } elseif ($action === 'getAll') {
                $response['data'] = $manager->getAllEvents();
                $response['status'] = 'success';
            } elseif ($action === 'getByClub') {
                $club = $_GET['club'] ?? null;
                if (!$club) throw new Exception("Missing club name");
                $response['data'] = $manager->getEventsByClub($club);
                $response['status'] = 'success';
            } elseif ($action === 'getByClubAndStatus') {
                $club = $_GET['club'] ?? null;
                $status = $_GET['status'] ?? null;
                if (!$club || !$status) throw new Exception("Missing club or status");
                $response['data'] = $manager->getEventsByClubAndStatus($club, $status);
                $response['status'] = 'success';
            } elseif ($action === 'getByStatus') {
                $status = $_GET['status'] ?? null;
                if (!$status) throw new Exception("Missing status");
                $response['data'] = $manager->getEventsByStatus($status);
                $response['status'] = 'success';
            } elseif ($action === 'getFeatured') {
                $response['data'] = $manager->getFeaturedEvents();
                $response['status'] = 'success';
            } else {
                throw new Exception("Unsupported GET action");
            }
            break;

        case 'POST':
            if ($action !== null && $action !== 'create') {
                throw new Exception("Unsupported POST action");
            }

            $payload = $parseJsonBody();
            $response['data'] = $manager->createEvent($payload);
            $response['status'] = 'success';
            $response['message'] = 'Event created successfully';
            http_response_code(201);
            break;

        case 'PUT':
        case 'PATCH':
            if ($action !== null && $action !== 'update') {
                throw new Exception("Unsupported update action");
            }

            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Missing event ID");

            $payload = $parseJsonBody();
            $response['data'] = $manager->updateEvent($id, $payload);
            $response['status'] = 'success';
            $response['message'] = 'Event updated successfully';
            break;

        case 'DELETE':
            if ($action !== null && $action !== 'delete') {
                throw new Exception("Unsupported DELETE action");
            }

            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Missing event ID");

            $manager->deleteEvent($id);
            $response['data'] = ['id' => (int)$id, 'deleted' => true];
            $response['status'] = 'success';
            $response['message'] = 'Event deleted successfully';
            break;

        default:
            throw new Exception("Unsupported HTTP method");
    }

} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);