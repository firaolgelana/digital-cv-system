<?php
require_once __DIR__ . '/../api/controllers/ProfileController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$profileController = new ProfileController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $profileController->getProfile();
} elseif ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['action'])) {
        if ($data['action'] == 'update_profile') {
            $profileController->updateProfile();
        } elseif ($data['action'] == 'change_password') {
            $profileController->changePassword();
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid action.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Action not specified.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed.']);
}
