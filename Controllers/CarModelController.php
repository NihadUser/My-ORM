<?php
require_once "Models/CarModel.php";
require_once "helpers/ResponseHelper.php";

class CarModelController{
    public static function models()
    {
        $carBrands = CarModel::models();

        if (!$carBrands['success']){
            sendResponse($carBrands['status'], $carBrands['message']);
        }
        sendResponse(200, [
            'data' => $carBrands['data'],
        ]);
    }

}