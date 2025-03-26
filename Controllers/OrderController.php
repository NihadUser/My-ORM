<?php
require_once "Models/Order.php";
require_once "helpers/ResponseHelper.php";

class OrderController {
    public static function poolOrders()
    {
        $orders = Order::poolOrders();

        if (!$orders['success']){
            sendResponse($orders['status'], ['status' => $orders['status'], 'message' => $orders['message']]);
        }
        sendResponse($orders['status'], ['data' => $orders['data'], 'status' => $orders['status'], 'message' => $orders['message']]);
    }

    public static function poolOrderDetails()
    {
        $orders = Order::poolOrderDetails();

        if (!$orders['success']){
            sendResponse($orders['status'], ['status' => $orders['status'], 'message' => $orders['message']]);
        }
        sendResponse($orders['status'], ['data' => $orders['data'], 'status' => $orders['status'], 'message' => $orders['message']]);
    }
    public static function updateStatus()
    {
        $update = Order::updateOrder();
        sendResponse(201, ['data' => $update]);

    }

    public static function poolOrderRequest()
    {
        $orders = Order::poolOrderRequest();

        sendResponse($orders['status'], ['status' => $orders['status'], 'message' => $orders['message']]);
    }
}