<?php

require_once "Models/Car.php";
require_once "helpers/ResponseHelper.php";

class CarController{
    public static function signUpCar()
    {
        $car = Car::signup();

        if (!$car['success']){
            sendResponse($car['status'], $car['message']);
        }
        sendResponse($car['status'], ['message' => $car['message'], 'id' => $car['id'], 'status' => $car['status']]);

    }
    public static function signUpFiles()
    {
        $car = Car::signUpFiles();
        if(!$car['success']){
            sendResponse($car['status'], [$car['message'], 'status' => $car['status']]);
        }
        sendResponse($car['status'], ['message' => $car['message'], 'status' => $car['status']]);
    }
    public static function carLocation()
    {
        $car = Car::carLocation();
        if(!$car['success']){
            sendResponse($car['status'], $car['message']);
        }
        sendResponse($car['status'], ['message' => $car['message'], 'status' => $car['status']]);
    }
}