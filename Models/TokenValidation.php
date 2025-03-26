<?php
require_once 'config.php';
require_once 'DBquery.php';

trait UserValidationTrait
{
    public static function validateUser()
    {
        $headers = getallheaders();
        $token = $headers['token'] ?? null;
        $user_id = $headers['user_id'] ?? null;

        if (empty($token) || empty($user_id)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Token or user_id is missing',
            ];
        }
        if (!is_numeric($user_id)){
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Unauthorized user!',
            ];
        }
        $db = DBquery::connectDB();
        $str = 'SELECT id FROM drivers WHERE id = ? AND token = ? AND expire_date > ? AND s_id = ?';
        $query = $db->prepare($str);
        $query->execute([$user_id, $token, date("Y-m-d H:i:s"), 1]);
        $user = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Unauthorized user!',
            ];
        }

        return [
            'success' => true,
            'user_id' => $user_id,
        ];
    }
}
