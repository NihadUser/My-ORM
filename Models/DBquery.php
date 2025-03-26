<?php

class DBquery
{
echo "Salam";
    public static string $table = '';

    public static $conn = null;

    public static string $hostName = 'localhost';

    public static string $dbName = 'legoscho_logistic';

    public static string $username = 'legoscho_logisticu';

    public static string $psw = '1q2w3e4r5t!QA';

    public const FETCH_ALL = 1;

    public const FETCH = 2;

    protected static string $selectedColumns = '*';

    protected static array $bindings = [];

    protected static array $join = [];

    protected static array $whereClauses = [];

    protected static array $whereOrClauses = [];

    public static function connectDB()
    {

        if (self::$conn == null) {

            try {

                return self::$conn = new PDO('mysql:host=' . self::$hostName . ';dbname=' . self::$dbName, self::$username, self::$psw);

            } catch (PDOException $e) {

                return $e->getMessage();

            }

        }

        return self::$conn;
    }

    /**

     * @param $columns

     * @return static

     */

    public static function select($columns = '*')
    {

        $columnStr = is_array($columns) ? implode(',', $columns) : $columns;

        self::$selectedColumns = $columnStr;

        return new static();

    }
    public static function join($joinTable, $joinTableColumn, $currentTableColumn, $type = 'INNER')
    {

        $currentTable = static::$table;

        if (strpos($joinTable, ' as ') !== false) {

            [$joinTableName, $alias] = explode(' as ', $joinTable);

            $joinClause = " $type JOIN $joinTableName AS $alias ON $joinTableColumn = $currentTableColumn ";

        } else {

            $joinClause = " $type JOIN $joinTable ON $joinTable.$joinTableColumn = $currentTableColumn ";

        }

        self::$join[] = $joinClause;

        return new static();

    }
    public static function whereNotNull($column)
    {
        if (strpos($column, '.') !== false) {

            self::$whereClauses[] = " $column IS NOT NULL ";

        } else {

            $table = static::$table;

            self::$whereClauses[] = " $table.$column IS NOT NULL ";

        }

        return new static();
    }

    public static function whereNull($column)
    {
        if (strpos($column, '.') !== false) {

            self::$whereClauses[] = " $column IS NULL ";

        } else {

            $table = static::$table;

            self::$whereClauses[] = " $table.$column IS NULL ";

        }

        return new static();
    }

    public static function where($column, $operator, $value, $isRaw = false)
    {

        if ($isRaw) {

            self::$whereClauses[] = " $column $operator $value ";

        } else {

            if (strpos($column, '.') !== false) {

                self::$whereClauses[] = " $column $operator ? ";

            } else {

                $table = static::$table;

                self::$whereClauses[] = " $table.$column $operator ? ";

            }

            self::$bindings[] = $value;

        }

        return new static();

    }

    public static function get()
    {

        $table = static::$table;

        $db = self::connectDB();

        $query = "SELECT " . self::$selectedColumns . " FROM $table ";



        if (!empty(self::$join)) {

            $query .= ' ' . implode(' ', self::$join);

        }

        if (!empty(self::$whereClauses)) {

            $query .= ' WHERE ' . implode(' AND ', self::$whereClauses);

        }

        $stmt = $db->prepare($query);

        $stmt->execute(self::$bindings);



        self::resetQueryState();



        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    protected static function resetQueryState()
    {

        self::$selectedColumns = '*';

        self::$join = [];

        self::$whereClauses = [];

        self::$bindings = [];

        //        self::$limit = 0;

        //        self::$offset = 0;

    }

    public static function all()
    {

        $db = self::connectDB();



        $query = $db->prepare("SELECT * FROM " . static::$table);

        $query->execute([]);

        $data = $query->fetchAll(PDO::FETCH_ASSOC);



        return $data;

    }

    public static function first($columnArr = [], $where = [])
    {

        return self::get(self::FETCH, $columnArr, $where);

    }

    public static function find($id)
    {

        $db = self::connectDB();

        $table = static::$table;



        $query = $db->prepare("SELECT * FROM $table WHERE id = ?");

        $query->execute([$id]);

        $data = $query->fetch(PDO::FETCH_ASSOC);

        return $data;

    }

    public static function delete($id)
    {

        $db = self::connectDB();

        $table = static::$table;



        $query = $db->prepare("DELETE FROM $table WHERE id = ?");

        $delete = $query->execute([

            $id

        ]);



        if ($delete) {

            return [

                'status' => true,

                'message' => "deleted"

            ];

        }

    }
    public static function create($arr)
    {

        $db = self::connectDB();

        $table = static::$table;
        try {

            $questionQuery = '';

            $sqlColumnNames = '';

            $j = 0;

            $executeArr = [];

            foreach ($arr as $key => $value) {

                $questionQuery .= $j == 0 ? " ?" : ", ? ";

                $sqlColumnNames .= $j == 0 ? $key : ", " . $key;

                $executeArr[] = $value;

                $j++;

            }



            $slqQuery = "INSERT INTO $table ($sqlColumnNames) VALUES ($questionQuery)";

            $query = $db->prepare($slqQuery);

            $query->execute($executeArr);

            $id = $db->lastInsertId();

            return [

                'status' => true,

                "message" => "Ok.",

                "id" => $id

            ];

        } catch (Exception $e) {

            return $e->getMessage();

        }

    }

    public static function update($arr, $id)
    {

        $db = self::connectDB();

        $table = static::$table;



        try {

            $i = 0;

            $updateQuery = '';

            $executeArr = [];
            foreach ($arr as $key => $value) {

                $updateQuery .= $i == 0 ? $key . " = ?" : "," . $key . " = ?";

                $executeArr[] = $value;

                $i++;

            }



            $executeArr[] = $id;

            $query = $db->prepare("UPDATE $table SET $updateQuery WHERE id = ?");

            $query->execute($executeArr);



            return [

                'status' => true,

                'message' => "Updated!"

            ];

        } catch (Exception $e) {

            return $e->getMessage();

        }

    }

    public static function paginate($columnArr = [], $currentPage = 1, array $where = [])
    {

        $db = self::connectDB();

        $table = static::$table;



        $limit = 10;

        $offset = ($currentPage - 1) * $limit;





        $whereQuery = $where == [] ? '' : ' WHERE ';

        $executeArr = [];



        $columns = $columnArr == [] ? "*" : implode(",", $columnArr);



        $i = 0;

        foreach ($where as $key => $value) {

            $whereQuery .= $i == 0 ? $key . "= ?" : " and $key" . "= ?";

            $executeArr[] = $value;



            $i++;

        }



        $getQuery = "SELECT $columns FROM $table $whereQuery LIMIT $limit OFFSET $offset";



        $query = $db->prepare($getQuery);

        $query->execute($executeArr);

        $data = $query->fetchAll(PDO::FETCH_ASSOC);



        $allCategories = self::get(self::FETCH_ALL, $columnArr, $where);

        $paginationCount = ceil(count($allCategories) / $limit);



        return [

            'data' => $data,

            'paginationCount' => $paginationCount

        ];

    }



    //    public static function join(array $tableArr, array $selectedColumnArr = ['*'])

    //    {

    //        $table = static::$table;

    //        $db = self::connectDB();

    //        $columns = '';

    //        $checkColumn = true;

    //        foreach ($selectedColumnArr as $item){

    //            if($checkColumn){

    //                $columns .= $item;

    //            }else{

    //                $columns .="," . $item;

    //            }

    //            $checkColumn = false;

    //        }

    //        $sqlQuery = "SELECT $columns ";

    //        $check = true;

    //        $str = '';

    //        foreach ($tableArr as $key => $value) {

    //            if ($check){

    //                $sqlQuery .= 'FROM ' . $key;

    //                $str = "$key.$value";

    //            }else{

    //                $sqlQuery .= " JOIN ON $key.$value = " . $str;

    //            }

    //            $check = false;

    //        }

    //        $query = $db->prepare($sqlQuery);

    //        $query->execute();

    //        return $query->fetchAll(PDO::FETCH_ASSOC);

    //     }

}



?>
