<?php
//echo 'hello world';

/**
 * Created by PhpStorm.
 * User: uranu
 * Date: 2018/6/30
 * Time: 13:48
 */

namespace DB\Driver;

use mysqli;

$db_host = 'sql196.main-hosting.eu';
//用户名
$db_user = 'u380972341_root';
//密码
$db_password = 'Jhd961213';
//数据库名
$db_name = 'u380972341_main';
//端口
$db_port = '3306';
//连接数据库
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);// or die('连接数据库失败！');

if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

interface iSQL
{
    function execute();

    function getSQL();

    function echoSql();
}

function __from(...$params)
{
    $tables = join(", ", $params);
    $sql = " FROM $tables";
    return $sql;
}

function __where(...$params)
{
    $predicates = join(") AND (", $params);
    $sql = " WHERE ($predicates)";
    return $sql;
}

function __order_by($param, $using_desc = false)
{
    $desc = $using_desc ? " DESC" : "";
    $sql = " ORDER BY {$param}{$desc}";
    return $sql;
}

function __group_by($param)
{
    $sql = " GROUP BY $param";
    return $sql;
}

function __values(array $params)
{
    $keys = join(", ", array_keys($params));
    $values = join(", ", array_values($params));
    $sql = " ($keys) VALUES ($values)";
    return $sql;
}

function __set(array $params)
{
    $kv_pairs = array_map(
        function ($key, $item) {
            return sprintf("%s=%s", $key, $item);
        },
        array_keys($params), array_values($params));
    $pairs_to_set = join(", ", $kv_pairs);
    $sql = " SET $pairs_to_set";
    return $sql;
}

class Query
{
    public static function select(...$columns)
    {
        global $conn;
        return new SqlSelect($conn, ...$columns);
    }

    public static function insert_into($table)
    {
        global $conn;
        return new SqlInsert($conn, $table);
    }

    public static function update($table)
    {
        global $conn;
        return new SqlUpdate($conn, $table);
    }

    public static function delete($table = '')
    {
        global $conn;
        return new SqlDelete($conn, $table);
    }

    public static function free_query($sql)
    {
        global $conn;
        return new FreeSql($conn, $sql);
    }
}

class SqlSelect implements iSQL
{
    private $sql;
    private $conn;

    public function __construct(\mysqli $conn, ...$params)
    {
        $this->conn = $conn;
        $columns = join(", ", $params);
        $this->sql = "SELECT $columns";
    }

    public function from(...$tables)
    {
        $this->sql .= __from(...$tables);
        return $this;
    }

    public function where(...$statements)
    {
        $this->sql .= __where(...$statements);
        return $this;
    }

    public function order_by($column, $desc = false)
    {
        $this->sql .= __order_by($column, $desc);
        return $this;
    }

    public function group_by($column)
    {
        $this->sql .= __group_by($column);
        return $this;
    }

    public function getSQL()
    {
        return $this->sql;
    }

    public function execute($on_succeed = null, $on_failed = null)
    {
        $this->sql .= ";";
        $query_result = $this->conn->query($this->sql);
        if ($query_result === false) {
            return $query_result;
        }
        $result = array();
        while ($row = $query_result->fetch_assoc()) {
            array_push($result, $row);
        }
        return $result;
    }

    public function get_iterator()
    {
        $this->sql .= ";";
        $query_result = $this->conn->query($this->sql);
        while ($row = $query_result->fetch_assoc()) {
            yield $row;
        }

    }

    public function echoSql()
    {
        echo $this->sql, '<br>';
        return $this;
    }
}

class SqlInsert implements iSQL
{
    private $sql;
    private $conn;

    public function __construct(\mysqli $conn, $param)
    {
        $this->conn = $conn;
        $this->sql = "INSERT INTO $param";
    }

    public function values(array $key_value_pairs)
    {
        $this->sql .= __values($key_value_pairs);
        return $this;
    }

    public function echoSql()
    {
        echo $this->sql, '<br>';
        return $this;
    }

    public function execute()
    {
        $this->sql .= ";";
        $query_result = $this->conn->query($this->sql);
        return $query_result;
    }

    public function getSQL()
    {
        return $this->sql;
    }
}

class SqlUpdate implements iSQL
{
    private $sql;
    private $conn;

    public function __construct(\mysqli $conn, $param)
    {
        $this->conn = $conn;
        $this->sql = "UPDATE $param";
    }

    public function set(array $key_value_pairs)
    {
        $this->sql .= __set($key_value_pairs);
        return $this;
    }

    public function where(...$statements)
    {
        $this->sql .= __where(...$statements);
        return $this;
    }

    public function echoSql()
    {
        echo $this->sql, '<br>';
        return $this;
    }

    public function execute($on_succeed = null, $on_failed = null)
    {
        $this->sql .= ";";
        $query_result = $this->conn->query($this->sql);
        return $query_result;
    }

    public function getSQL()
    {
        return $this->sql;
    }
}

class SqlDelete implements iSQL
{
    private $sql;
    private $conn;

    public function __construct(\mysqli $conn, $param)
    {
        $this->conn = $conn;
        $table = (!$param) ? ($param) : (" $param");  // 判断表名是否为空。是则返回空，否则返回空格+表名
        $this->sql = "DELETE$table";
    }

    public function from(...$tables)
    {
        $this->sql .= __from(...$tables);
        return $this;
    }

    public function where(...$statements)
    {
        $this->sql .= __where(...$statements);
        return $this;
    }

    public function echoSql()
    {
        echo $this->sql, '<br>';
        return $this;
    }

    public function execute($on_succeed = null, $on_failed = null)
    {
        $this->sql .= ";";
        $query_result = $this->conn->query($this->sql);
        return $query_result;
    }

    public function getSQL()
    {
        return $this->sql;
    }
}

class FreeSql implements iSQL
{
    private $sql;
    private $conn;

    public function __construct(\mysqli $conn, $param)
    {
        $this->conn = $conn;
        $this->sql = $param;
    }

    public function echoSql()
    {
        echo $this->sql, '<br>';
        return $this;
    }

    public function execute($on_succeed = null, $on_failed = null)
    {
        $this->sql .= ";";
        $query_result = $this->conn->query($this->sql);
        return $query_result;
    }

    public function getSQL()
    {
        return $this->sql;
    }
}

class Reply
{
    public static function good($content)
    {
        return self::custom($content, 'good', '');
    }

    public static function bad($info)
    {
        return self::custom('', 'bad', $info);
    }

    public static function custom($content, $status, $info)
    {
        return json_encode(['content' => $content, 'status' => $status, 'info' => $info]);
    }

    public static function auto(iSQL $query)
    {
        $result = $query->execute();
        if ($result === true) {
            return self::good('');
        } elseif ($result === false) {
            return self::bad('query failed');
        } else {
            return self::good($result);
        }
    }
}

class QueryParam
{
    public $param_name;
    public $col_name;
    public $type;
    public $not_null;
    public $range;

    public function __construct($param_name, $col_name, $type, $range, $not_null)
    {
        $this->param_name = $param_name;
        $this->col_name = $col_name;
        $this->type = $type;
        $this->not_null = $not_null;
        $this->range = $range;
    }
}

class Prepare
{
    private $source;
    private $params = [];
    public $missing_params = [];
    public $over_range_params = [];
    public $prepared_params = [];
    public $key_id = null;

    public function __construct($source)
    {
        $this->source = $source;
        return $this;
    }

    public function add($param_name, $col_name, $type, $range = null, $not_null = false)
    {
        array_push($this->params,
            new QueryParam($param_name, $col_name, $type, $range, $not_null));
        return $this;
    }

    public static function out_of_range($type, $range, $value)
    {
        $check_value = ($type == 'str') ? strlen($value) : $value;
        if ($range == null) {
            return false;
        } else {
            if (sizeof($range) == 1) {
                return ($check_value > $range[0]);
            } elseif (sizeof($range == 2)) {
                return ($check_value < $range[0]) or ($check_value > $range[1]);
            } else {
                return true;
            }
        }
    }

    public function end_prepare()
    {
        foreach ($this->params as $param) {
            if (array_key_exists($param->param_name, $this->source)) {
                $preparing = $this->source[$param->param_name];
                $prepared = call_user_func($param->type . 'val', $preparing);
                if (Prepare::out_of_range($param->type, $param->range, $prepared)) {
                    array_push($this->over_range_params, $param);
                    continue;
                }
                if ($param->type == 'str') {
                    $prepared = "'${prepared}'";
                }
                $this->prepared_params[$param->col_name] = $prepared;
            } elseif ($param->not_null) {
                array_push($this->missing_params, $param);
            }
        }
        return $this;
    }

    public function pop_key($key)
    {
        $temp = $this->prepared_params[$key];
        unset($this->prepared_params[$key]);
        return $temp;
    }
}

//
//Query::select('*')
//    ->from('table1')
//    ->where('x=1',
//        "y='2'")
//    ->group_by('col1')
//    ->order_by('col1', true)
//    ->echoSql();
//
//Query::insert_into('table2')
//    ->values(array(
//        'col1' => '1',
//        'col2' => "'2'",))
//    ->echoSql();
//
//Query::update('table3')
//    ->set(array(
//        'col1' => "'3.1'",
//        'col2' => "'test_value'"))
//    ->where("col3='cond1'",
//        "col4='cond2'")
//    ->echoSql();
//
//Query::delete_from('table4')
//    ->where("col1='cond1",
//        "col2='cond2'")
//    ->echoSql();
//
//Query::free_query("SELECT * FROM table1 WHERE col1='cond1'")
//    ->echoSql();
//
//$fruit = "banana";
//echo "I want to drink $fruit juice", '<br>';
