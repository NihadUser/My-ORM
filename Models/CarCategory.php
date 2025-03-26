<?php
require_once 'config.php';
require_once 'DBquery.php';
require_once 'TokenValidation.php';

class CarCategory extends DBquery {
    use UserValidationTrait;
    public static string $table = 'cars_kat';

    public static function categories()
    {
        $validation = self::validateUser();
        if (!$validation['success']) return $validation;

        $id = json_decode(file_get_contents('php://input'), true);
        if (isset($id['id'])) {
            $data = self::get(['id', 'u_id', 'name'], ['l_id' => $id['id'], 's_id' => '1']);
            if (count($data) == 0){
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'status' => 404
                ];
            }
            return [
                'success' => true,
                'data' => $data,
                'status' => 200
            ];
        }
        $data = self::get(['id', 'u_id', 'name'], ['s_id' => '1']);

        return [
            'data' => $data,
            'success' => true,
            'status' => 200,
        ];
    }
}