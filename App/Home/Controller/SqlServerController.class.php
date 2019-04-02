<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/2/26
 * Time: 9:18
 */

namespace Home\Controller;

use Common\Core\Method;
use Think\Controller;
use Think\Log;
class SqlServerController extends Controller
{

    public function test(){
        $utc_time = "2018-12-15T10:48:59.293652+08:00";
        $t = date_format(date_create($utc_time), 'Y-m-d h:i:s');
        var_dump($t);
    }

    public function index(){
        $this->batchInsert('Users','users');
    }


    public function clearTable($table){
        $sql = "truncate ".$table;
        $result = M()->execute($sql);
        if ($result){
            echo '清空成功';
        }else{
            echo '清空成功';
        }
    }

    public function batchInsert($sqlserver_table,$table)
    {
        ini_set('max_execution_time', '0');//设置永不超时，无限执行下去直到结束
        $total = 100;
        $Model = M($table);
        $info = $Model->count();
        $max_id = !empty($info) ? $info : 0;
        echo $max_id."\r\n";
        $count = $this->sqlServerQuery("select count(*) count from [dbo].[$sqlserver_table]");
        echo $count[0]['count']."\r\n";
        for ($i = $max_id / $total; $i < ceil($count[0]['count'] / $total); $i++) {
            if ($i == 0) {
                $sql = "select top $total * from [dbo].[$sqlserver_table] ORDER BY Id";
            } else {
                $sql = "SELECT TOP $total * FROM [dbo].[$sqlserver_table] WHERE (Id > (SELECT MAX(Id) FROM (SELECT TOP ($total*$i) Id FROM [dbo].[$sqlserver_table]  ORDER BY Id) AS T)) ORDER BY Id";
            }
            echo $sql."\r\n";
            $result = $this->importData($table, $sql);
            if (!$result) {
                echo $i . "次插入失败\n";
            } else {
                echo $i . "次插入成功\n";
            }
        }
//
//        if (!$result) Method::return_err('插入失败');
//        Method::return_data('插入成功');
    }


    public function sqlServerQuery($select_sql)
    {
        $list = array();
        $conn = new \PDO("sqlsrv:server=localhost;database=orderSchedule", "sa", "abc123456!@#");
        if (!$conn) Method::return_err('pdo连接sqlserver失败');
        $res = $conn->query($select_sql);
        while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    public function combine_sql($table, $keys, $values)
    {
        $key = implode(',', $keys);
        $value = implode(',', $values);
        $sql = "insert into " . $table . "(" . $key . ") values " . $value;
        return $sql;
    }


    public function importList($table, $select_sql)
    {
        $sql_array = array();
        $mysql_array = array(); // mysql数据库已经存在id
        $list = $this->sqlServerQuery($select_sql);
        //mysql 数据库是否存在该记录
        $mysql_list = M()->query("select * from " . $table);
        foreach ($mysql_list as $val) {
            $mysql_array[] = $val['id'];
        }

        foreach ($list as $row) {
            if (!in_array($row['Id'], $mysql_array)) {
                $keys = array_keys($row);
                $values = array_values($row);
                $value = implode("','", $values);
                $sql_array[] = "('" . $value . "')";
            }
        }

        $full_sql = $this->combine_sql($table, $keys, $sql_array);
        $result = M()->execute($full_sql);
        return $result;
    }

    public function importData($table, $select_sql)
    {
        $sql_array = array();
//        $mysql_array = array(); // mysql数据库已经存在id
        $list = $this->sqlServerQuery($select_sql);

        //mysql 数据库是否存在该记录
//        $mysql_list = M()->query("select * from ".$table);
//        foreach ($mysql_list as $val){
//            $mysql_array[] = $val['id'];
//        }
        if ($table=='orderhistoryTest'){
            foreach ($list as $value=>&$item){
                $time=date("Y-m-d H:i:s",strtotime($item['Created'])+8*60*60);
                $item['Created']=$time;
                $sql_array[] = $item;
            }
            unset($item);
        }
        else{
            foreach ($list as $row) {
                $sql_array[] = $row;
//            if (!in_array($row['Id'], $mysql_array)){
//                $sql_array[] = $row;
//            }
            }
        }
        $Model = M($table);
        $result = $Model->addAll($sql_array);
        return $result;
    }

}