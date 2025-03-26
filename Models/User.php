<?php
require_once 'config.php';
require_once 'DBquery.php';
require_once 'TokenValidation.php';
require_once 'FileUpload.php';

class User extends DBquery
{
    use FileUpload, UserValidationTrait;
    public static string $table = 'drivers';
    public static function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = self::connectDB();

        $userName = $data['userName'] ?? null;
        $password = $data['password'] ?? null;
        if (empty($userName) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username or password required!',
                'status' => 400
            ];
        }

        $user = self::where('login', $userName);
        if (!$user){
            return [
                'success' => false,
                'status' => 404,
                'message' => 'User not found!',
            ];
        }
        if (!password_verify($password, $user[0]['pass'])){
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Password incorrect!',
            ];
        }
        if ($user[0]['s_id'] != 1){
            return [
                'success' => false,
                'status' => 403,
                'message' => 'Permission denied!',
            ];
        }
        $token = bin2hex(random_bytes(8));

        self::update([
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'expire_date' => date('Y-m-d H:i:s', strtotime(EXPIRE_DATE)),
        ], $user[0]['id']);

        $str = 'UPDATE token_log SET s_id = ?, updated_at = ? WHERE driver_id = ? and s_id = ?';
        $query = $db->prepare($str);
        $query->execute([0, date('Y-m-d H:i:s'), $user[0]['id'], 1]);

        $str = 'INSERT INTO token_log (token, expire_date, driver_id) VALUES(?, ?, ?)';
        $query = $db->prepare($str);
        $query->execute([
            $token,
            date('Y-m-d H:i:s', strtotime(EXPIRE_DATE)),
            $user[0]['id']
        ]);

        return [
            'success' => true,
            'status' => 200,
            'token' => $token,
            'id' => $user[0]['id'],
        ];
    }
    public static function signUp($data)
    {
        $db = self::connectDB();
        if (empty($data['contact1'])) {
            return [
                'success' => false,
                'message' => 'Contact required'
            ];
        }
        if (empty($data['user_name'])) {
            return [
                'success' => false,
                'message' => 'User name required'
            ];
        }
        if (empty($data['driver_name'])) {
            return [
                'success' => false,
                'message' => 'Name required'
            ];
        }
        if (empty($data['driver_surname'])) {
            return [
                'success' => false,
                'message' => 'Surname required'
            ];
        }
        if (empty($data['passportNo'])) {
            return [
                'success' => false,
                'message' => 'Passport required'
            ];
        }
        if (empty($data['licenceNumber'])) {
            return [
                'success' => false,
                'message' => 'Licence number required'
            ];
        }
        if (empty($data['licenceCat'])) {
            return [
                'success' => false,
                'message' => 'Licence category required'
            ];
        }

        if (empty($data['password1']) || empty($data['password2'])) {
            return [
                'success' => false,
                'message' => 'Password can not be empty'
            ];
        }
        if ($data['password1'] != $data['password2']) {
            return [
                'success' => false,
                'message' => 'Password does not match'
            ];
        }
        $user_name = $data['user_name'];
        $check = self::where('login', $user_name);
        if (count($check) > 0){
            return [
                'success' => false,
                'message' => 'User already exists!'
            ];
        }
        $string = "SELECT id FROM drivers WHERE contact1 = '{$data['contact1']}' OR contact2 = '{$data['contact1']}' OR contact3 = '{$data['contact1']}'";
        $query = $db->prepare($string);
        $query->execute();
        $checkContact1 = $query->fetchAll(PDO::FETCH_ASSOC);

        if (isset($data['contact2'])){
            $string = "SELECT id FROM drivers WHERE contact1 = '{$data['contact2']}' OR contact2 = '{$data['contact2']}' OR contact3 = '{$data['contact2']}'";
            $query = $db->prepare($string);
            $query->execute();
            $checkContact2 = $query->fetchAll(PDO::FETCH_ASSOC);
            if (count($checkContact2) > 0) {
                return [
                    'success' => false,
                    'message' => 'Contact already exists'
                ];
            }
        }

        if (isset($data['contact3'])){
            $string = "SELECT id FROM drivers WHERE contact1 = '{$data['contact3']}' OR contact2 = '{$data['contact3']}' OR contact3 = '{$data['contact3']}'";
            $query = $db->prepare($string);
            $query->execute();
            $checkContact3 = $query->fetchAll(PDO::FETCH_ASSOC);
            if ( count($checkContact3) > 0) {
                return [
                    'success' => false,
                    'message' => 'Contact already exists'
                ];
            }
        }

        if (count($checkContact1) > 0 || count($checkContact2 ?? []) > 0 || count($checkContact3 ?? []) > 0) {
            return [
                'success' => false,
                'message' => 'Contact already exists'
            ];
        }

        $checkPassport = self::where('passport_no', $data['passportNo']);
        if (count($checkPassport) > 0){
            return [
                'success' => false,
                'message' => 'Passport already exists'
            ];
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['user_name'])) {
            return [
                'success' => false,
                'message' => 'Username can only contain letters.',
            ];
        }

        $id = self::addDriver($data);
        return [
            'success' => true,
            'message' => 'Ok',
            'id' => $id
        ];

    }
    public static function signUpPhoto()
    {
        if (!isset($_FILES['userPhoto'])) {
            return [
                'success' => false,
                'message' => 'User photo required!'
            ];
        }
        if (!isset($_FILES['passportPhoto1'])) {
            return [
                'success' => false,
                'message' => 'Passport photo required!'
            ];
        }
        if (!isset($_FILES['passportPhoto2'])) {
            return [
                'success' => false,
                'message' => 'Passport photo required!'
            ];
        }
        if (!isset($_FILES['licencePhoto1'])) {
            return [
                'success' => false,
                'message' => 'Licence photo required!'
            ];
        }
        if (!isset($_FILES['licencePhoto2'])) {
            return [
                'success' => false,
                'message' => 'Licence photo required!'
            ];
        }
        $db = self::connectDB();

        $userPhoto = self::imageUpload($_FILES['userPhoto'], $_POST['id']);
        $passportPhoto1 = self::imageUpload($_FILES['passportPhoto1'], $_POST['id']);
        $passportPhoto2 = self::imageUpload($_FILES['passportPhoto2'], $_POST['id']);
        $passportPhoto3 = isset($_FILES['passportPhoto3']) ? self::imageUpload($_FILES['passportPhoto3'], $_POST['id']) : null;
        $licencePhoto1 = self::imageUpload($_FILES['licencePhoto1'], $_POST['id']);
        $licencePhoto2 = self::imageUpload($_FILES['licencePhoto2'], $_POST['id']);
        $certificate = isset($_FILES['certificate']) ? self::imageUpload($_FILES['certificate'], $_POST['id']) : null;
        $taxoCard = isset($_FILES['taxoCard']) ? self::imageUpload($_FILES['taxoCard'], $_POST['id']) : null;
        $token = bin2hex(random_bytes(8));
        $str = "INSERT INTO token_log (token, created_at, expire_date, driver_id) VALUES (?, ?, ?, ?)";
        $query = $db->prepare($str);
        $query->execute([
            $token,
            date("Y-m-d H:i:s"),
            date("Y-m-d H:i:s", strtotime("+1 year")),
            $_POST['id']
        ]);

        $update = self::update([
            'my_pic' => $userPhoto['image'],
            'passport_pic1' => $passportPhoto1['image'],
            'passport_pic2' => $passportPhoto2['image'],
            'passport_pic3' => $passportPhoto3['image'] ?? null,
            'dl_pic1' => $licencePhoto1['image'],
            'dl_pic2' => $licencePhoto2['image'],
            'cert_pic' => $certificate['image'] ?? null,
            'taxcard_pic' => $taxoCard['image'] ?? null,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'expire_date' => date('Y-m-d H:i:s', strtotime(EXPIRE_DATE)), //adding one year
        ], $_POST['id']);

        return ['success' => true, 'message' => 'Added', 'token' => $token];
    }
    public static function addDriver($data)
    {
        $insert = self::create([
            'name' => $data['driver_name'],
            'surname' => $data['driver_surname'],
            'login' => $data['user_name'],
            'contact1' => $data['contact1'],
            'email' => $data['email'] ??  null,
            'contact2' => $data['contact2'] ?? null,
            'contact3' => $data['contact3'] ?? null,
            'pass' => password_hash($data['password1'], PASSWORD_DEFAULT),
            'pass_real' => $data['password1'],
            'passport_no' => $data['passportNo'],
            'dl_no' => $data['licenceNumber'],
            'dl_cat' => $data['licenceCat'],
        ]);

        return $insert['id'];
    }
    public static function details()
    {
        $headers = getallheaders();
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $id = getallheaders()['user_id'];

        $driver = self::get([
            'name',
            'surname',
            'passport_no',
            'dl_no',
            'dl_pic1',
            'dl_pic2',
            'passport_pic1',
            'passport_pic2',
            'passport_pic3',
            'contact1',
            'contact2',
            'contact3',
            'my_pic',
            'dl_cat',
            'cert_pic',
            'taxcard_pic',
            'email',
            'login as userName',
            'pass'
        ],
            ['id' => $id]);
        return[
            'success' => true,
            'driver' => $driver[0],
            'status' => 200,
            'message' => 'Ok',
        ];

    }
    public static function edit()
    {
        $headers = getallheaders();
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }
        $validate = self::validateUserInfo($data, $headers['user_id']);
        if (!$validate['success']){
            return [
                'success' => false,
                'message' => $validate['message'],
                'status' => 400
            ];
        }

        $update = self::update([
            'name' => $data['driver_name'],
            'surname' => $data['driver_surname'],
            'contact1' => $data['contact1'],
            'email' => $data['email'] ??  null,
            'contact2' => $data['contact2'] ?? null,
            'contact3' => $data['contact3'] ?? null,
            'passport_no' => $data['passportNo'],
            'dl_no' => $data['licenceNumber'],
            'dl_cat' => $data['licenceCat'],
        ], $headers['user_id']);

        if ($update['status']){
            return [
                'success' => true,
                'message' => 'Updated',
                'status' => 200,
            ];
        }

    }
    private static function validateUserInfo($data, $id){
        $db = self::connectDB();

        $requiredFields = [
            'driver_name' => 'Driver name is required',
            'driver_surname' => 'Driver surname is required',
            'contact1' => 'Contact 1 is required',
            'passportNo' => 'Passport no is required',
            'licenceNumber' => 'Licence number is required',
            'licenceCat' => 'Licence category is required',
        ];

        foreach ($requiredFields as $key => $value) {
            if (!isset($data[$key])){
                return [
                    'success' => false,
                    'message' => $value,
                    'status' => 400
                ];
            }
        }

        $string = "SELECT id FROM drivers WHERE (contact1 = '{$data['contact1']}' OR contact2 = '{$data['contact1']}' OR contact3 = '{$data['contact1']}') and id != $id";
        $query = $db->prepare($string);
        $query->execute();
        $checkContact1 = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($checkContact1) > 0) {
            return [
                'success' => false,
                'message' => 'Contact1 already exists'
            ];
        }

        if (isset($data['contact2'])){
            $string = "SELECT id FROM drivers WHERE (contact1 = '{$data['contact2']}' OR contact2 = '{$data['contact2']}' OR contact3 = '{$data['contact2']}') and id != $id";
            $query = $db->prepare($string);
            $query->execute();
            $checkContact2 = $query->fetchAll(PDO::FETCH_ASSOC);
            if (count($checkContact2) > 0) {
                return [
                    'success' => false,
                    'message' => 'Contact already exists'
                ];
            }
        }

        if (isset($data['contact3'])){
            $string = "SELECT id FROM drivers WHERE (contact1 = '{$data['contact3']}' OR contact2 = '{$data['contact3']}' OR contact3 = '{$data['contact3']}') and id != $id";
            $query = $db->prepare($string);
            $query->execute();
            $checkContact3 = $query->fetchAll(PDO::FETCH_ASSOC);
            if ( count($checkContact3) > 0) {
                return [
                    'success' => false,
                    'message' => 'Contact already exists'
                ];
            }
        }

        $string = "SELECT id FROM drivers WHERE passport_no = '{$data['passportNo']}' and id != $id";
        $query = $db->prepare($string);
        $query->execute();
        $checkPassport = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($checkPassport) > 0){
            return [
                'success' => false,
                'message' => 'Passport already exists'
            ];
        }

        return [
            'success' => true,
            'message' => 'Ok'
        ];
    }
    public static function editPhoto()
    {
        $headers = getallheaders();
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        if (!isset($_FILES['userPhoto'])) {
            return [
                'success' => false,
                'message' => 'User photo required!',
                'status' => 400
            ];
        }
        if (!isset($_FILES['passportPhoto1'])) {
            return [
                'success' => false,
                'message' => 'Passport photo required!',
                'status' => 400
            ];
        }
        if (!isset($_FILES['passportPhoto2'])) {
            return [
                'success' => false,
                'message' => 'Passport photo required!',
                'status' => 400
            ];
        }
        if (!isset($_FILES['licencePhoto1'])) {
            return [
                'success' => false,
                'message' => 'Licence photo required!',
                'status' => 400
            ];
        }
        if (!isset($_FILES['licencePhoto2'])) {
            return [
                'success' => false,
                'message' => 'Licence photo required!',
                'status' => 400
            ];
        }

        $userPhoto = self::imageUpload($_FILES['userPhoto'], $headers['user_id']);
        $passportPhoto1 = self::imageUpload($_FILES['passportPhoto1'], $headers['user_id']);
        $passportPhoto2 = self::imageUpload($_FILES['passportPhoto2'], $headers['user_id']);
        $passportPhoto3 = isset($_FILES['passportPhoto3']) ? self::imageUpload($_FILES['passportPhoto3'], $headers['user_id']) : null;
        $licencePhoto1 = self::imageUpload($_FILES['licencePhoto1'], $headers['user_id']);
        $licencePhoto2 = self::imageUpload($_FILES['licencePhoto2'], $headers['user_id']);
        $certificate = isset($_FILES['certificate']) ? self::imageUpload($_FILES['certificate'], $headers['user_id']) : null;
        $taxoCard = isset($_FILES['taxoCard']) ? self::imageUpload($_FILES['taxoCard'], $headers['user_id']) : null;

        $update = self::update([
            'my_pic' => $userPhoto['image'],
            'passport_pic1' => $passportPhoto1['image'],
            'passport_pic2' => $passportPhoto2['image'],
            'passport_pic3' => $passportPhoto3['image'] ?? null,
            'dl_pic1' => $licencePhoto1['image'],
            'dl_pic2' => $licencePhoto2['image'],
            'cert_pic' => $certificate['image'] ?? null,
            'taxcard_pic' => $taxoCard['image'] ?? null,
        ], $headers['user_id']);

        if ($update ['status']){
            return[
                'success' => true,
                'message' => 'Updated',
                'status' => 200,
            ];
        }
    }
    public static function editLogin()
    {
        $headers = getallheaders();
        $data = json_decode(file_get_contents('php://input'), true);
        $db = self::connectDB();
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $userName = $data['user_name'];
        $id = $headers['user_id'];
        $password1 = $data['password1'];
        $password2 = $data['password2'];
        $pastPassword = $data['pastPassword'];

        if ($password1 != $password2){
            return [
                'success' => false,
                'message' => 'Password does not match',
                'status' => 400
            ];
        }

        if (strlen($password1) < 6 || strlen($password2) < 6){
            return [
                'success' => false,
                'message' => 'Short password',
                'status' => 400
            ];
        }

        $check = self::select(['id', 'pass_real'])->whereOrm('login', '=', $userName)->whereOrm('id', '!=', $id)->ormGet();
        if (count($check) > 0){
            return [
                'success' => false,
                'message' => 'User name already exists',
                'status' => 400
            ];
        }

        $check2 = self::select(['id', 'pass_real'])->whereOrm('id', '=', $id)->ormGet();
        if ($check2[0]['pass_real'] != $pastPassword){
            return [
                'success' => false,
                'message' => 'Past password is not correct',
                'status' => 400
            ];
        }

        $update = self::update([
            'login' => $userName,
            'pass' => password_hash($password1, PASSWORD_DEFAULT),
            'pass_real' => $password2,
        ], $id);

        if ($update ['status']){
            return [
                'success' => true,
                'message' => 'Updated',
                'status' => 200,
            ];
        }
    }
    public static function car()
    {
        self::$table = 'cars';
        $headers = getallheaders();
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $id = $headers['user_id'];

        $car = self::select([
                    'cars.id as carId',
                    'cat_id',
                    'volume',
                    'cars.passport_pic2',
                    'cars.passport_pic',
                    'cars.passport_no',
                    'cars.cert_pic as certificateImage',
                    'cars.year_made',
                    'cars.b_id',
                    'cars.m_id',
                    'cars.dqn',
                    'cars.dqn2',
                    "CONCAT(max_weight, ' ', 'kg') as max_weight",
                    "CONCAT(trailer_weight, ' ', 'kg') as trailer_weight",
                    "CONCAT(car_weight, ' ', 'kg') as car_weight",
                    'trailer_pas_pic1 as trailerPassportImage1',
                    'trailer_pas_pic2 as trailerPassportImage2',
                    'drivers.id as driver_id',
                    'drivers.name as driverName',
                    'cars_model.name as modelName',
                    'cars_brand.name as brand_name',
                    "CONCAT(cars_brand.name ,' ', cars_model.name) as carModel"
                ])
                ->join('drivers', 'id', 'driver_id')
                ->join('cars_model', 'id', 'cars.m_id')
                ->join('cars_brand', 'id', 'cars.b_id')
                ->join('cars_kat', 'u_id', 'cars.cat_id')
                ->whereOrm('cars_kat.l_id', '=', 1)
                ->whereOrm('drivers.id', '=', $id)
                ->ormGet();

        return [
            'success' => true,
            'message' => 'Ok.',
            'data' => $car[0],
            'status' => 200
        ];
    }

    public static function orders()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $user_id = getallheaders()['user_id'];
        $data = json_decode(file_get_contents('php://input'));
        $date2 = $data->date2 ?? date('Y-m-d');
        $date1 = $data->date1 ?? date('Y-m-d', strtotime("-3 months")); ;

        self::$table = 'order_drivers';
        $finishOrders = self::select([
                "CONCAT(ok1.name, ' ', ok2.name, ' ', o.export_address) as exportAddress",
                "CONCAT(ok3.name, ' ', ok4.name, ' ', o.import_address) as importAddress",
                "
                    case 
                        when status = 4 then 'finished'
                        when status = 3 then 'in progress'
                        when status = 2 then 'in driver'
                        when status = 5 then 'cancelled'
                        else 'Passive'
                    end as status
                ",
                'driver_accepted_date as acceptedDate',
                'start_date as startDate',
                'finish_date as finishedDate',
            ])
            ->join('orders as o', 'o.id', 'order_drivers.order_id')
            ->join('olkeler as ok1', 'ok1.u_id', 'o.export_country_id')
            ->join('olkeler as ok2', 'ok2.u_id', 'o.export_city_id')
            ->join('olkeler as ok3', 'ok3.u_id', 'o.import_country_id')
            ->join('olkeler as ok4', 'ok4.u_id', 'o.import_city_id')
            ->whereOrm('order_drivers.driver_id', '=', $user_id)
            ->whereOrm('order_drivers.s_id', '=', 2)
            ->whereOrm('ok1.l_id', '=', 1)
            ->whereOrm('ok2.l_id', '=', 1)
            ->whereOrm('ok3.l_id', '=', 1)
            ->whereOrm('ok4.l_id', '=', 1)
            ->whereOrm('o.driver_accepted_date', '>', "$date1")
            ->whereOrm('o.driver_accepted_date', '<', "$date2")
            ->ormGet();


        return [
            'success' => true,
            'message' => 'Ok.',
            'orders' => $finishOrders,
            'status' => 200
        ];

    }
}