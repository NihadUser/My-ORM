<?php
require_once "Models/CarCategory.php";
require_once "helpers/ResponseHelper.php";

class CarCategoryController
{
    public static function categories()
    {
        $data = CarCategory::categories();

        if (!$data['success']){
            sendResponse($data['status'], $data['message']);
        }
        sendResponse($data['status'], [
            'data' => $data['data'],
        ]);
    }
}