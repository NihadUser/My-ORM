<?php
require_once "Models/Driver.php";
require_once "helpers/ResponseHelper.php";

class DriverController {
    public static function driver($id)
    {
        $driver = Driver::driver($id);
        sendResponse(200, ['driver' => $driver]);
    }
}