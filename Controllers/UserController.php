<?php

require_once "Models/User.php";
require_once "helpers/ResponseHelper.php";

class UserController{
    public static function login()
    {
        $user = User::login();

        if (!$user['success']){
            sendResponse($user['status'], ['message' => $user['message']]);
        }
        sendResponse($user['status'], ['token' => $user['token'], 'id' => $user['id']]);

    }
    public static function signUp(){
        $data = json_decode(file_get_contents('php://input'), true);
        $user = User::signUp($data);
        if ($user['success']){
            sendResponse(201, [ 'message' => 'User inserted', 'id' => $user['id']]);
        }
        sendResponse(400, ['message' => $user['message']]);
    }
    public static function signUpPhoto()
    {
        $message = User::signUpPhoto();

        if (!$message['success']){
            sendResponse(400, ['success' => false, 'message' => $message['message']]);
        }
        sendResponse(201, ['message' => $message['message'], 'token' => $message['token']]);
    }
    public static function details()
    {
        $user = User::details();

        if (!$user['success']){
            sendResponse($user['status'], ['status' => $user['status'], 'message' => $user['message']]);
        }
        sendResponse($user['status'], ['status' => $user['status'], 'message' => $user['message'], 'data' => $user['driver']]);
    }
    public static function edit()
    {
        $result = User::edit();
        if (!$result['success']){
            sendResponse($result['status'], [$result['status'], 'message' => $result['message']]);
        }
        sendResponse($result['status'], [$result['status'], 'message' => $result['message']]);
    }

    public static function editPhoto()
    {
        $result = User::editPhoto();
        if (!$result['success']){
            sendResponse($result['status'], [$result['status'], 'message' => $result['message']]);
        }
        sendResponse($result['status'], [$result['status'], 'message' => $result['message']]);
    }

    public static function editLogin()
    {
        $result = User::editLogin();
        if (!$result['success']){
            sendResponse($result['status'], ['status' => $result['status'], 'message' => $result['message']]);
        }
        sendResponse($result['status'], ['status' => $result['status'], 'message' => $result['message']]);
    }
    public static function car()
    {
        $data = User::car();
        if (!$data['success']){
            sendResponse($data['status'], ['status' => $data['status'], 'message' => $data['message']]);
        }
        sendResponse($data['status'], [
            'message' => $data['message'],
            'status' => $data['status'],
            'data' => $data['data']
        ]);
    }

    public static function orders()
    {
        $data = User::orders();
        if (!$data['success']){
            sendResponse($data['status'], ['status' => $data['status'], 'message' => $data['message']]);
        }
        sendResponse($data['status'], [
            'message' => $data['message'],
            'status' => $data['status'],
            'orders' => $data['orders'],
        ]);
    }
}