<?php
require_once 'Controllers/UserController.php';
require_once 'Controllers/OrderController.php';
require_once 'Controllers/DriverController.php';
require_once 'Controllers/CarController.php';
require_once 'Controllers/CarBrandController.php';
require_once 'Controllers/CarCategoryController.php';
require_once 'Controllers/CarModelController.php';

function handleGetRequest($method, $endpoint) {
    switch ($endpoint) {
        case 'user/details':
            UserController::details();
            break;
        case 'user/car':
            UserController::car();
            break;
        case 'driver/list':
            DriverController::driver($_GET['val']);
            break;
        default:
            sendResponse(404, ['error' => 'Endpoint not found']);
    }
}

function handlePostRequest($method, $endpoint) {
    switch ($endpoint) {
        case 'user/login':
            UserController::login();
        break;
        case 'user/signup':
            UserController::signUp();
            break;
        case 'user/signUpPhotos':
            UserController::signUpPhoto();
            break;
        case 'user/edit':
            UserController::edit();
            break;
        case 'user/editPhoto':
            UserController::editPhoto();
            break;
        case 'user/editLogin':
            UserController::editLogin();
            break;
        case 'user/orders':
            UserController::orders();
            break;
        case 'car/signup':
            CarController::signUpCar();
            break;
        case 'car/signUpPhotos':
            CarController::signUpFiles();
            break;
        case 'car/brands':
            CarBrandController::brands();
            break;
        case 'car/models':
            CarModelController::models();
            break;
        case 'car/categories':
            CarCategoryController::categories();
            break;
        case 'order/update':
            OrderController::updateStatus();
            break;
        case 'order/pool':
            OrderController::poolOrders();
            break;
        case 'order/pool-details':
            OrderController::poolOrderDetails();
            break;
        case 'order/pool-request':
            OrderController::poolOrderRequest();
            break;
        case 'car/location':
            CarController::carLocation();
            break;
        default:
            sendResponse(404, ['error' => 'Endpoint not found']);
    }
}

?>
