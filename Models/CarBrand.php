<?php
require_once 'config.php';
require_once 'DBquery.php';
require_once 'TokenValidation.php';

class CarBrand extends DBquery
{
    use FileUpload, UserValidationTrait;
    public static string $table = 'cars_brand';
    public static function brands()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }
        $brands = self::get(['id', 'name'], ['s_id', 1]);

        return [
            'data' => $brands,
            'success' => true,
        ];
    }

}