<?php
require_once 'config.php';
require_once 'DBquery.php';
require_once 'TokenValidation.php';

class CarModel extends DBquery
{
    use FileUpload, UserValidationTrait;
    public static string $table = 'cars_model';
    public static function models()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id'])){
            $data = self::get(['id', 'name'], ['b_id' => $data['id'], 's_id' => 1]);
            if (count($data) === 0){
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'Car model not found'
                ];
            }
            return [
                'success' => true,
                'data' => $data,
            ];
        }
        return [
            'data' => self::get(['id', 'name'], ['s_id' => 1]),
            'success' => true,
        ];
    }

}