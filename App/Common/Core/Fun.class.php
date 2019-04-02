<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2018/11/5
 * Time: 14:09
 */
namespace Common\Core;

class Fun {
    /**
     * @param $name
     * @return string
     */
    public static function request($name){
        $temp = "";
        if ($_SERVER["REQUEST_METHOD"] == "POST") $temp=$_POST[$name];
        if ($_SERVER["REQUEST_METHOD"] == "GET")  $temp=iconv('gb2312', 'UTF-8', $_GET[$name]);
        $temp=self::CheckReplace(trim($temp));

        return $temp;
    }

    public static function requestInt($name,$def=0){
        $temp = $def;
        if ($_SERVER["REQUEST_METHOD"] == "POST") $temp=$_POST[$name];
        if ($_SERVER["REQUEST_METHOD"] == "GET")  $temp=$_GET[$name];
        $temp=self::CheckReplace(trim($temp));
        $temp=intval($temp);
        if(!is_int($temp))$temp=$def;
        return $temp;
    }

    public static function requestFloat($name){
        $temp = 0.00;
        if ($_SERVER["REQUEST_METHOD"] == "POST") $temp=$_POST[$name];
        if ($_SERVER["REQUEST_METHOD"] == "GET")  $temp=$_GET[$name];
        $temp=self::CheckReplace(trim($temp));
        if(is_float($temp)) return $temp;
        if(is_int($temp))return $temp;
        return $temp;
    }

    public static function getUUID(){
        $uuid=uniqid(mt_rand(), true);
        $uuid=str_replace(".","",$uuid);
        return $uuid;
    }

    /**
     * @param $str
     * @return mixed|string
     */
    public static function CheckReplace($str){
        $str = stripslashes($str);
        $str = htmlspecialchars($str);
//        $str = str_replace("and","",$str);
//        $str = str_replace("execute","",$str);
//        $str = str_replace("update","",$str);
//        $str = str_replace("count","",$str);
//        $str = str_replace("chr","",$str);
//        $str = str_replace("mid","",$str);
//        $str = str_replace("master","",$str);
//        $str = str_replace("truncate","",$str);
//        $str = str_replace("char","",$str);
//        $str = str_replace("declare","",$str);
//        $str = str_replace("select","",$str);
//        $str = str_replace("create","",$str);
//        $str = str_replace("delete","",$str);
//        $str = str_replace("insert","",$str);
        //$str = str_replace("or","",$str);
        //$str = str_replace("=","",$str);

        $str = str_replace("%20","",$str);
        $str = str_replace("'","",$str);
        $str = str_replace("\"","",$str);
//        $str = str_replace(" ","",$str);
        $str = str_replace(">","",$str);
        $str = str_replace("<","",$str);
        $str = str_replace("&","",$str);
        return $str;
    }
}