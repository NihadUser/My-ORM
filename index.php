<?php

header('Content-Type: application/json');

// Include required files
require_once 'config.php';
require_once 'routes.php';
// Checking for existing secret key
function checkSecretKey(){
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Invalid or missing secret key"]);
        exit;
    }else if($headers['Authorization'] !==  SECRET_KEY){
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Invalid or missing secret key"]);
        exit;
    }
}
checkSecretKey();

// Get HTTP method and requested endpoint
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['state'] . '/' . $_GET['page'] ?? '';

if ($method === 'POST'){
    handlePostRequest($method, $endpoint);
}else if ($method === 'GET'){
    handleGetRequest($method, $endpoint);
}else {
    sendResponse(405, ['error' => 'Method not allowed']);
}


?>