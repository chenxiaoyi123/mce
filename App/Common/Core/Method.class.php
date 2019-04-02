<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/19
 * Time: 11:28
 */

namespace Common\Core;

class Method
{

    static function exportToExcel($filename, $titleArray = array(), $dataArray = array(),$i){
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ob_end_clean();
        ob_start();
        header('Content-Type:text/csv');
        header('Content-Disposition:filename=' . $filename);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        if ($i==0){
            fputcsv($fp, $titleArray);
        }
        $index = 0;
        foreach ($dataArray as $item) {
            if ($index == 1000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }

        ob_flush();
        flush();
        ob_end_clean();
    }

    //调试输出日志
    static function logOutput($data, $filename)
    {
        if (empty($filename)) {
            $filename = './log.txt';
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $date = date('Y-m-d H:i:s');
        $str = <<<eof
时间：$date
DATA: $data

eof;

        file_put_contents($filename, $str, LOCK_EX);
    }

    //固定长度随机券码
    static function makeCouponCard($length = 8)
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0, 25)] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        for ($a = md5($rand, true), $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV', $d = '', $f = 0; $f < 8; $g = ord($a[$f]), $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F], $f++) ;
        return $d;
    }

    static function getRandomString($len = 16, $keyword = '')
    {
        if (strlen($keyword) > $len) {//关键字不能比总长度长
            return false;
        }
        $str = '';
        $chars = 'abcdefghijkmnpqrstuvwxyz23456789ABCDEFGHIJKMNPQRSTUVWXYZ'; //去掉1跟字母l防混淆
        if ($len > strlen($chars)) {//位数过长重复字符串一定次数
            $chars = str_repeat($chars, ceil($len / strlen($chars)));
        }
        $chars = str_shuffle($chars); //打乱字符串
        $str = substr($chars, 0, $len);
        if (!empty($keyword)) {
            $start = $len - strlen($keyword);
            $str = substr_replace($str, $keyword, mt_rand(0, $start), strlen($keyword)); //从随机位置插入关键字
        }
        return $str;
    }

    static function encryptPassword($password, $salt = '')
    {
        return md5(sha1($salt . md5($password)));
    }

    static function getRandomOrderNo($id, $keyword = '')
    {
        $nonceStr = "";
        if (empty($keyword)) {
            $keyword = "HT";
        }
        if ($id > 0) {
            $uid = $id;
        } else {
            $uid = 0;
        }
        $num = 4;
        $str = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        for ($i = 0; $i <= $num; $i++) {
            $rand = rand(0, count($str));
            $nonceStr .= $str[$rand];
        }
        $ret_str = $keyword . "_" . $uid . "_" . time() . $nonceStr;
        return $ret_str;
    }

    //用户真实IP
    static function getIP()
    {
        //不允许就使用getenv获取
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }

        return $realip;
    }


    static function getWeekDays(){
        $firstday = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
        $lastday = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")));
        return array($firstday, $lastday);
    }
    static function getLastWeekDays(){
        $firstday = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y")));
        $lastday = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y")));
        return array($firstday, $lastday);
    }

    /*本月*/
    static function getMonthDays($date)
    {
        $firstday = date("Y-m-01", strtotime($date));
        $lastday = date("Y-m-d", strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }

    static function getMonthDaysTwoMethod(){
        $firstday = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
        $lastday = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")));
        return array($firstday, $lastday);
    }


    /*上月*/
    static function getLastMonthDays($date)
    {
        $timestamp = strtotime($date);
        $firstday = date('Y-m-01', strtotime(date('Y', $timestamp) . '-' . (date('m', $timestamp) - 1) . '-01'));
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }

    static function getLastMonthDaysTwoMethod(){
        $firstday = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")-1,1,date("Y")));
        $lastday = date("Y-m-d H:i:s",mktime(23,59,59,date("m") ,0,date("Y")));
        return array($firstday, $lastday);
    }

    /*下月*/
    static function getNextMonthDays($date)
    {
        $timestamp = strtotime($date);
        $arr = getdate($timestamp);
        if ($arr['mon'] == 12) {
            $year = $arr['year'] + 1;
            $month = $arr['mon'] - 11;
            $firstday = $year . '-0' . $month . '-01';
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        } else {
            $firstday = date('Y-m-01', strtotime(date('Y', $timestamp) . '-' . (date('m', $timestamp) + 1) . '-01'));
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        }
        return array($firstday, $lastday);
    }

    /*本季度*/
    static function getSeasonDays()
    {
        $season = ceil((date('n')) / 3);
        $firstday = date('Y-m-d H:i:s', mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y')));
        $lastday = date('Y-m-d H:i:s', mktime(23, 59, 59, $season * 3, date('t', mktime(0, 0, 0, $season * 3, 1, date("Y"))), date('Y')));
        return array($firstday, $lastday);
    }

    /*上季度*/
    static function getLastSeasonDays()
    {
        $season = ceil((date('n')) / 3) - 1;
        $firstday = date('Y-m-d H:i:s', mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y')));
        $lastday = date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));
        return array($firstday, $lastday);
    }

    //解密encryptedData加密串
    static function decryptData($appid, $sessionKey, $encryptedData, $iv, &$data)
    {
        if (strlen($sessionKey) != 24) {
            return '-41001';
        }
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            return '-41002';
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            return '-41003';
        }
        if ($dataObj->watermark->appid != $appid) {
            return '-41003';
        }
        $data = $result;
        return 0;
    }

    //随机验证码
    static function randNum($num = 4)
    {
        $str = mt_rand(1, 9);
        for ($i = 0; $i < $num - 1; $i++) {
            $str .= mt_rand(0, 9);
        }
        return $str;
    }

    //判断是否为手机号
    static function isMobile($mobile)
    {
        $flag = false;
        //$match = array();
        if (strlen($mobile) == "11") {
            //if(preg_match("/^((13[0-9])|(14([0-9]))|(15([0-9]))|(16([0-9]))|(17([0-9]))|(18[0-9]))\\d{8}$/",$mobile,$match)){
            $flag = true;
            //}
        }
        return $flag;
    }

    static function checkPassword($password, $minLength = 6, $maxLength = 20)
    {
        $array = array('status' => 0, 'msg' => '验证失败');
        if ($password == null) {
            $array['msg'] = '密码不能为空';
            return $array;
        }
        $password = trim($password);
        if (!(strlen($password) >= $minLength && strlen($password) <= $maxLength)) {//必须大于6个字符
            $array['msg'] = '密码必须大于6字符，小于20字符';
            return $array;
        }
        /*if (preg_match("/^[0-9]+$/", $password)) { //必须含有特殊字符
            $array['msg'] = '密码不能全是数字，请包含数字，字母大小写或者特殊字符';
            return $array;
        }
        if (preg_match("/^[a-zA-Z]+$/", $password)) {
            $array['msg'] = '密码不能全是字母，请包含数字，字母大小写或者特殊字符';
            return $array;
        }
        if (preg_match("/^[0-9A-Z]+$/", $password)) {
            $array['msg'] = '请包含数字，字母大小写或者特殊字符';
            return $array;
        }
        if (preg_match("/^[0-9a-z]+$/", $password)) {
            $array['msg'] = '请包含数字，字母大小写或者特殊字符';
            return $array;
        }*/
        $array = array('status' => 1, 'msg' => '验证成功');
        return $array;
    }

    //将分转换元
    static function getFloatYuanByFen($fen)
    {
        $yuan = self::getInt($fen) / 100;
        $yuan = sprintf("%.2f", $yuan);
        return $yuan;
    }

    //元转化为分，不四舍五入
    static function getIntFenByYuan($yuan)
    {
        $fen = self::getFloat($yuan) * 100;
        if (substr_count($fen, ".") > 0) $fen = floor($fen);
        return $fen;
    }

    //整形
    static function getInt($name, $def = 0)
    {
        $temp = $name;
        if ($temp == "") $temp = $def;
        $temp = intval($temp);
        if (!is_int($temp)) $temp = $def;
        return $temp;
    }

    static function zeroToNull($val){
        if ($val == 0){
            $val = null;
        }
        return $val;
    }

    //浮点数
    static function getFloat($name)
    {
        $temp = (float)$name;
        if (!is_float($temp)) $temp = 0.00;
        return $temp;
    }

    //ajax返回获取数据
    static function return_data($mix_data)
    {
        $data = array("code" => 200, "error" => "");
        if (is_string($mix_data)) {
            $data["error"] = $mix_data;
        } elseif (is_array($mix_data)) {
            $data = array_merge($data, $mix_data);
        }
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    //ajax返回错误信息
    static function return_err($mix_data)
    {
        $data = array("code" => 0, "error" => "error");
        if (is_string($mix_data)) {
            $data["error"] = $mix_data;
        } elseif (is_array($mix_data)) {
            $data = array_merge($data, $mix_data);
        }
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }


    //参数拼接url
    static function combine_url($baseUrl, $keysArr = array())
    {
        $combined = $baseUrl . "?";
        $valueArr = array();

        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&", $valueArr);

        $combined .= ($keyStr);

        return $combined;
    }


    //curl-get请求
    static function http_get($url)
    {
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

    //curl-post请求
    static function http_post($url, $post_data, $header = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        // 请求头，可以传数组
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HEADER, $header);
        }
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}