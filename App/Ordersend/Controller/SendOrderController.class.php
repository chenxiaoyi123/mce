<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/28
 * Time: 10:46
 */

namespace Ordersend\Controller;

use Think\Controller;
use Think\Log;

//header("Content-Type: application/json; charset=utf-8");

class SendOrderController extends Controller
{
    public function test()
    {
        print_r('eqeqefdsqsdew');die();
        $servern="(local)";
        $coninfo=array("Database"=>"texl","UID"=>"sa","PWD"=>"q2326673");
        $conn=sqlsrv_connect($servern,$coninfo) or die ("连接失败!");
    }
}