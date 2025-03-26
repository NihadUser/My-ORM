<?php
require_once 'config.php';
require_once 'DBquery.php';

class Driver extends DBquery{
    public static string $table = 'drivers';

    public static function driver($id)
    {
        return $driver = parent::get(['*'], ['id' => $id]);
    }
}