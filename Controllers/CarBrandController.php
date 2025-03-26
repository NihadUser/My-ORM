<?php

require_once "Models/CarBrand.php";
require_once "helpers/ResponseHelper.php";

class CarBrandController{
    public static function brands()
    {
        $carBrands = CarBrand::brands();

        if (!$carBrands['success']){
            sendResponse($carBrands['status'], $carBrands['message']);
        }
        sendResponse(200, [
            'data' => $carBrands['data'],
        ]);
    }

}