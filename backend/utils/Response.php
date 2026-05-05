<?php
class Response {
    public static function success($data, $status = 200, $message = null) {
        http_response_code($status);
        header('Content-Type: application/json');
        $response = ['success' => true, 'data' => $data];
        if ($message) $response['message'] = $message;
        echo json_encode($response);
        exit;
    }

    public static function error($message, $code = 400, $status = 400) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message, 'code' => $code]);
        exit;
    }
}
