<?php
require_once 'config.php';
require_once 'DBquery.php';
class Order extends DBquery
{
    use UserValidationTrait;
    public static string $table = 'orders';
    public static function poolOrders()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }
        $id = getallheaders()['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        $car_lat = $data['lat'];
        $car_lng = $data['lng'];

        self::$table = 'drivers';

        $driver = self::select(['dt.name', 'dt.deploy_interval'])
            ->join('driver_types as dt', 'dt.id', 't_id')
            ->whereOrm('id', '=', $id)
            ->ormGet();

        $minutes = $driver[0]['deploy_interval'];
        $now = date('Y-m-d H:i:s');

        self::$table = 'orders';
        $orders = self::select([
                        "CONCAT(ok1.name, ' ', ok2.name, ' ', orders.export_address) as exportAddress",
                        "CONCAT(ok3.name, ' ', ok4.name, ' ', orders.import_address) as importAddress",
                        'orders.release_date',
                    ])
                    ->join('olkeler as ok1', 'ok1.u_id', 'export_country_id')
                    ->join('olkeler as ok2', 'ok2.u_id', 'export_city_id')
                    ->join('olkeler as ok3', 'ok3.u_id', 'import_country_id')
                    ->join('olkeler as ok4', 'ok4.u_id', 'import_city_id')
                    ->whereOrm('orders.status', '=', 1)
                    ->whereOrm('ok1.l_id', '=', 1)
                    ->whereOrm('ok2.l_id', '=', 1)
                    ->whereOrm('ok3.l_id', '=', 1)
                    ->whereOrm('ok4.l_id', '=', 1)
                    ->whereOrm("
                        6371 * 2 * ASIN(SQRT(
                            POWER(SIN((RADIANS(orders.export_lat - $car_lat)) / 2), 2) +
                            COS(RADIANS($car_lat)) * COS(RADIANS(orders.export_lat)) *
                            POWER(SIN((RADIANS(orders.export_lng - $car_lng)) / 2), 2)
                        ))
                    ", '<=', '60')
                    ->whereOrm('NOW()', '>', "DATE_ADD(orders.pool_date, INTERVAL $minutes MINUTE)", true)
                    ->ormGet();

        return [
            'success' => true,
            'data' => $orders,
            'message' => 'Ok.',
            'status' => 200
        ];
    }

    public static function poolOrderDetails()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }
        $data = json_decode(file_get_contents('php://input'));
        $user_id = getallheaders()['user_id'];
        $car_lat = $data->lat;
        $car_lng = $data->lng;
        if (!isset($data->id)){
            return [
                'success' => false,
                'message' => 'Id is required',
                'status' => 400
            ];
        }

        self::$table = 'drivers';
        $driver = self::select(['deploy_interval'])
            ->join('driver_types', 'id', 't_id')
            ->whereOrm('drivers.id', '=', $user_id)
            ->ormGet();
        $minutes = $driver[0]['deploy_interval'];

        self::$table = 'orders';
        $orders = self::select([
            "CONCAT(ok1.name, ' ', ok2.name, ' ', orders.export_address) as exportAddress",
            "CONCAT(ok3.name, ' ', ok4.name, ' ', orders.import_address) as importAddress",
            'orders.release_date',
            "CASE
                when
                    6371 * 2 * ASIN(SQRT(
                        POWER(SIN((RADIANS(orders.export_lat - $car_lat)) / 2), 2) +
                        COS(RADIANS($car_lat)) * COS(RADIANS(orders.export_lat)) *
                        POWER(SIN((RADIANS(orders.export_lng - $car_lng)) / 2), 2)
                    )) > 60    
                then '1'
                else '0'
            END
            AS distanceCheck",
            "
            case 
                when NOW() > DATE_ADD(orders.pool_date, INTERVAL $minutes MINUTE) then '0'
                else '1' 
            end as deployIntervalCheck
            ",
            'cc.name as companyName',
            "CONCAT(cp.name, ' ', cp.surname) as relatedPerson",
            'lt.name as loadingForm',
            'lt2.name as loadType',
            'ft.name as frightType',
            'pt.name as paymentType',
            "CONCAT(total_amount, ' AZN') as totalAmount",
            "CONCAT(driver_price, ' AZN') as driverPrice",
            "CONCAT(add_prices, ' AZN') as additionalPrice",
            'orders.notes'
        ])
            ->join('olkeler as ok1', 'ok1.u_id', 'export_country_id')
            ->join('olkeler as ok2', 'ok2.u_id', 'export_city_id')
            ->join('olkeler as ok3', 'ok3.u_id', 'import_country_id')
            ->join('olkeler as ok4', 'ok4.u_id', 'import_city_id')
            ->join('customer_company as cc', 'cc.id', 'customer_company')
            ->join('company_persons as cp', 'cp.id', 'company_person')
            ->join('loading_type as lt', 'lt.id', 'loading_form')
            ->join('load_type as lt2', 'lt2.id', 'load_type')
            ->join('freight_type as ft', 'ft.id', 'freight_type')
            ->join('payment_type as pt', 'pt.id', 'payment_form')
            ->whereOrm('orders.status', '=', 1)
            ->whereOrm('ok1.l_id', '=', 1)
            ->whereOrm('ok2.l_id', '=', 1)
            ->whereOrm('ok3.l_id', '=', 1)
            ->whereOrm('ok4.l_id', '=', 1)
            ->whereOrm('orders.id', '=', $data->id)
            ->ormGet();

        return [
            'success' => true,
            'data' => $orders,
            'message' => 'Ok.',
            'status' => 200
        ];

    }

    public static function poolOrderRequest()
    {
        $validation = self::validateUser();
        if (!$validation['success']){
            return $validation;
        }

        $driver_id = getallheaders()['user_id'];
        $order_id = json_decode(file_get_contents('php://input'))->order_id;

        self::$table = 'cars';
        $carCheck = self::select([
                'id'
            ])->whereOrm('driver_id', '=', $driver_id)->ormGet();
        if (count($carCheck) == 0){
            return [
                'success' => false,
                'message' => 'Car not found.',
                'status' => 400
            ];
        }

        self::$table = 'order_drivers';
        $check = self::select(['id'])
            ->whereOrm('driver_id', '=', $driver_id)
            ->whereOrm('order_id', '=', $order_id)
            ->whereOrm('s_id', '=', 1)
            ->ormGet();
        if (count($check) > 0){
            return [
                'success' => false,
                'message' => 'Driver already request car.',
                'status' => 400
            ];
        }

        $insert = self::create([
            'driver_id' => $driver_id,
            'order_id' => $order_id,
            's_id' => 1,
        ]);

        self::$table = 'order_log';
        $insertLog = self::create([
            'order_id' => $order_id,
            'log_type' => 5,
            'driver_id' => $driver_id,
        ]);

        if ($insert['status'] && $insertLog['status']){
            return [
                'success' => true,
                'message' => 'Request added successfully.',
                'status' => 201
            ];
        }
    }
    public static function updateOrder()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'];
        $status = $data['status'];

        $update = parent::update(
            [
                'status' => $status
            ], $order_id);

        if($update['status']){
            return [
                'message' => 'Updated!'
            ];
        }
    }
}