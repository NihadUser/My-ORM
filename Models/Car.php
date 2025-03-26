<?php
require_once 'config.php';
require_once 'DBquery.php';
require_once 'User.php';
require_once 'FileUpload.php';
require_once 'TokenValidation.php';

class Car extends DBquery
{
    use FileUpload, UserValidationTrait;
    private $user;
    public function __construct()
    {
        $this->user = new User();
    }

    public static string $table = 'cars';
    public static function signup()
    {
        $db = self::connectDB();
        $data = json_decode(file_get_contents("php://input"), true);

        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $requiredFields = [
            'badge1' => 'Badge 1 is required',
            'brand_id' => 'Brand ID is required',
            'model_id' => 'Model ID is required',
            'made_year' => 'Made Year is required',
            'passport_no' => 'Passport No is required',
            'volume' => 'Volume is required',
            'vehicle_weight' => 'Vehicle Weight is required',
            'category' => 'Category is required'
        ];

        foreach ($requiredFields as $field => $message) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'status' => 400,
                    'message' => $message
                ];
            }
        }
        if (!empty($data['trailer_weight']) && empty($data['badge2'])){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Badge 2 can not be empty"
            ];
        }

        $badge1 = $data['badge1'];

        $query = "SELECT id FROM cars where dqn = '$badge1' or dqn2 = '$badge1'";
        $query = $db->prepare($query);
        $query->execute();
        $checkDQN = $query->fetchALL(PDO::FETCH_ASSOC);

        if (isset($data['badge2'])){
            $badge2 = $data['badge2'];
            $query = "SELECT id FROM cars where dqn = '$badge2' or dqn2 = '$badge2'";
            $query = $db->prepare($query);
            $query->execute();
            $checkDQN = $query->fetchALL(PDO::FETCH_ASSOC);

            if (!isset($data['trailer_weight'])){
                return [
                    'success' => false,
                    'status' => 400,
                    'message' => "Trailer Weight is required"
                ];
            }
        }

        if (count($checkDQN) > 0 || count($checkDQN2 ?? []) > 0) {
            return [
                'success' => false,
                'status' => 400,
                'message' => "DQN already exists"
            ];
        }

        $maxWeight = isset($data['trailer_weight']) ? 40000.00 - ((float)$data['vehicle_weight'] + (float)$data['trailer_weight']) : 40000.00 - (float)$data['vehicle_weight'];
        $insert = self::create([
            'b_id' => $data['brand_id'],
            'm_id' => $data['model_id'],
            'year_made' => $data['made_year'],
            'passport_no' => $data['passport_no'],
            'volume' => $data['volume'],
            'car_weight' => $data['vehicle_weight'],
            'trailer_weight' => $data['trailer_weight'] ?? null,
            'max_weight' => $maxWeight,
            'dqn' => $data['badge1'],
            'dqn2' => $data['badge2'] ?? null,
            'cat_id' => $data['category'],
            's_id' => 0
        ]);

        return [
            'success' => true,
            'status' => 201,
            'id' => $insert['id'],
            'message' => "Car created successfully "
        ];

    }
    public static function signUpFiles(){
        $db = self::connectDB();
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        if (!isset($_FILES['passportImage1']) || !isset($_FILES['passportImage2'])){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Two Passport image is required"
            ];
        }
        if (!isset($_POST['id'])){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Id is required"
            ];
        }

        $query = "SELECT id FROM cars WHERE id = {$_POST['id']} and dqn2 is null";
        $query = $db->prepare($query);
        $query->execute();
        $checkDqn2 = $query->fetchALL(PDO::FETCH_ASSOC);

//        print_r(count($checkDqn2));exit();
        if (count($checkDqn2) > 0 && (isset($_FILES['trailerPassImage']) || isset($_FILES['trailerPassImage2']))){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Car does not have trailer!"
            ];
        }else if (count($checkDqn2) == 0 && (!isset($_FILES['trailerPassImage']) || !isset($_FILES['trailerPassImage2']))){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Trailer Passport image is required"
            ];
        }

        $check = self::where('driver_id', $_POST['driver_id']);
        if (count($check) > 0){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Driver already has a car"
            ];
        }

        $passportImage1 = self::imageUpload($_FILES['passportImage1'], $_POST['id']);
        $passportImage2 = self::imageUpload($_FILES['passportImage2'], $_POST['id']);
        $certImage = isset($_FILES['certImage']) ? self::imageUpload($_FILES['certImage'], $_POST['id']) : null;
        $trailerPassportImage = isset($_FILES['trailerPassImage']) ? self::imageUpload($_FILES['trailerPassImage'], $_POST['id']) : null;
        $trailerPassportImage2 = isset($_FILES['trailerPassImage2']) ? self::imageUpload($_FILES['trailerPassImage2'], $_POST['id']) : null;

        self::update([
            'passport_pic' => $passportImage1['image'],
            'passport_pic2' => $passportImage2['image'],
            'trailer_pas_pic1' => $trailerPassportImage['image'] ?? null,
            'trailer_pas_pic2' => $trailerPassportImage2['image'] ?? null,
            'cert_pic' => $certImage['image'] ?? null,
            's_id' => 1,
            'driver_id' => $_POST['driver_id']
        ], $_POST['id']);
        return [
            'success' => true,
            'status' => 201,
            'message' => "Car created successfully"
        ];
    }

    public static function carLocation()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }
        $data = json_decode(file_get_contents("php://input"));
        $lng = $data->lng;
        $lat = $data->lat;
        $id = getallheaders()['user_id'];

        self::$table = 'drivers';
        $car = self::select(['cars.id'])
            ->join('cars', 'driver_id', 'drivers.id')
            ->whereOrm('drivers.id', '=', $id)
            ->ormGet();
        if (count($car) == 0){
            return [
                'success' => false,
                'status' => 400,
                'message' => "Driver does not have a car"
            ];
        }

        self::$table = 'cars';
        $creationTime = self::select(['cars.coordinate_created_at'])
                        ->whereOrm('id', '=', $car[0]['id'])
                        ->ormGet();

        if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($creationTime[0]['coordinate_created_at'] . ' +30 minutes')))
        {
            self::update([
                'lng' => $lng,
                'lat' => $lat,
                'coordinate_created_at' => date("Y-m-d H:i:s"),
            ], $car[0]['id']);

            self::$table = 'car_locations';
            self::create([
                'car_id' => $car[0]['id'],
                'lng' => $lng,
                'lat' => $lat,
                'created_at' => date("Y-m-d H:i:s"),
            ]);
            return [
                'success' => true,
                'status' => 201,
                'message' => "Location created successfully.",
            ];
        }
        return [
            'success' => false,
            'status' => 400,
            'message' => "Too many attempt to insert location."
        ];

    }
}