<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/3/1
 * Time: 14:14
 */

namespace Home\Controller;

use Common\Core\Method;
use Think\Controller;

Vendor('PHPExcel.PHPExcel', '', '.php');
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Credentials:true");
header('Access-Control-Allow-Origin:*');

class LuxwController extends Controller
{

    public function importCallhistoryFiles()
    {
        set_time_limit(0);
        $xls_file_url = "Public/excel/callhistory_file3.xlsx";

        vendor('PHPExcel.Classes.PHPExcel');

        vendor('PHPExcel.Classes.PHPExcel.IOFactory');
        vendor('PHPExcel.Classes.PHPExcel.Reader.Excel5');

        $objPHPExcel = \PHPExcel_IOFactory::load($xls_file_url);


        $sheet = $objPHPExcel->getSheet(0);

        $highestRow = $sheet->getHighestRow();

        $highestColumn = $sheet->getHighestColumn();


        $data = array();

        for ($j = 2; $j <= $highestRow; $j++) {
            for ($k = 'A'; $k <= $highestColumn; $k++) {
                $data[$k][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }

        $list = array();
        $names = array();

        $count = count($data['A']);

        // orderId
        for ($i = 0; $i < $count; $i++) {

            $sql = "SELECT RecordFile FROM `callhistories` INNER JOIN orders ON callhistories.OrderId=orders.Id  WHERE callhistories.Deleted = 0 AND `Name` <> 'Robot' AND `RecordFile` <> 'NULL' AND orders.OrderId = '" . $data['A'][$i] . "' ORDER BY callhistories.StartTime desc LIMIT 0,1";
            $info = M()->query($sql);
            $file = $info[0]['recordfile'];
            if (!empty($file)) {
                $list[] = $file;
                $names[] = $data['A'][$i];
//                $this->audio($file, $data['A'][$i]);
            }
        }

        $this->download_file($list, $names);


        echo '执行成功';

    }


//    public function importCallhistoryFiles_bak()
//    {
//        set_time_limit(0);
//        $xls_file_url = "Public/excel/callhistory_file.xlsx";
//
//        vendor('PHPExcel.Classes.PHPExcel');
//
//        vendor('PHPExcel.Classes.PHPExcel.IOFactory');
//        vendor('PHPExcel.Classes.PHPExcel.Reader.Excel5');
//
//        $objPHPExcel = \PHPExcel_IOFactory::load($xls_file_url);
//
//
//        $sheet = $objPHPExcel->getSheet(0);
//
//        $highestRow = $sheet->getHighestRow();
//
//        $highestColumn = $sheet->getHighestColumn();
//
//
//        $data = array();
//
//        for ($j = 2; $j <= $highestRow; $j++) {
//            for ($k = 'A'; $k <= $highestColumn; $k++) {
//                $data[$k][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
//            }
//        }
//
//
//        $exitArr = array();
//        $count = count($data['A']);
//
//        // orderId
//        for ($i = 0; $i < $count; $i++) {
//            if (!in_array($data['A'][$i], $exitArr)){
//                $sql = "SELECT RecordFile FROM `callhistories` INNER JOIN orders ON callhistories.OrderId=orders.Id  WHERE callhistories.Deleted = 0 AND `Name` <> 'Robot' AND `RecordFile` <> 'NULL' AND orders.OrderId = '" . $data['A'][$i] . "' ORDER BY callhistories.StartTime desc LIMIT 0,1";
//                $info = M()->query($sql);
//                $exitArr[] = $data['A'][$i];
//                $file = $info[0]['recordfile'];
//                if (!empty($file)) {
//                    $this->audio($file, $data['A'][$i]);
//                }
//            }
//
//        }
//
//
//        echo '执行成功';
//
//    }


    public function download_file(&$data, &$names){
        $basePath = 'D:/webSite/Orders/PC/API/REC/manREC/';

        $fileanme = 'recordfiles3.zip';
        if (file_exists($fileanme)){
            unlink($fileanme);
        }

        $zip = new \ZipArchive();
        if ($zip->open($fileanme, \ZipArchive::CREATE) !== TRUE){
            exit('无法打开文件，或者文件创建失败');
        }

        foreach ($data as $key => $val){
            if (strlen($val) < 8) return_err("无法找到目录");
            $dateDir = substr($val, 0, 8);

            $allPath = $basePath . $dateDir . '/' . $val;
//            if (!file_exists($allPath)) return_err("文件不存在");

            if (file_exists($allPath)){
                $zip->addFile($allPath, $names[$key].'.mp3');
            }

        }

        $zip->close();

        if (!file_exists($fileanme)){
            exit('无法找到文件');
        }

        return_data('success');
    }


//    public function audio($fileName, $name)
//    {
//        $basePath = 'D:/webSite/Orders/PC/API/REC/manREC/';
//
//        if (strlen($fileName) < 8) return_err("无法找到目录");
//        $dateDir = substr($fileName, 0, 8);
//
//        $allPath = $basePath . $dateDir . '/' . $fileName;
//        echo $allPath."\r\n";
//        if (!file_exists($allPath)) return_err("文件不存在");
//
//        $fileanme = $name.'.zip';
//        if (file_exists($fileanme)){
//            unlink($fileanme);
//        }
//
//        $zip = new \ZipArchive();
//        if ($zip->open($fileanme, \ZipArchive::CREATE) !== TRUE){
//            exit('无法打开文件，或者文件创建失败');
//        }
//
//        if (file_exists($allPath)){
//            $zip->addFile($allPath);
//        }
//
//        $zip->close();
//
//        if (!file_exists($fileanme)){
//            exit('无法找到文件');
//        }
//
//        return_data($name.'--->success');
//    }

    public function audio_bak($fileName, $name)
    {
        $basePath = 'D:/webSite/Orders/PC/API/REC/manREC/';

        if (strlen($fileName) < 8) return_err("无法找到目录");
        $dateDir = substr($fileName, 0, 8);

        $allPath = $basePath . $dateDir . '/' . $fileName;
        if (!file_exists($allPath)) return_err("文件不存在");

        $this->download($allPath, $name);

        return_data($name.'--->success');
    }

    //服务器端：
    function download($path, $name)
    {
        $file_size = filesize($path);
        header("Content-type:audio/mpeg");
        header("Accept-Ranges:bytes");
        header("Accept-Length:$file_size");
        header("Content-Disposition:attachment;filename=$name.mp3");
        readfile($path);
        exit();
    }


    public function test()
    {
        $data = array('name' => 'luxwer', 'age' => 25, 'birthday' => '1992-02-07');
        $params = array();
        foreach ($data as $key => $val) {
            $params[$key] = $val;
        }

        print_r($params);
    }


    public function exportToExcel($filename, $titleArray = array(), $dataArray = array())
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ob_end_clean();
        ob_start();
        header('Content-Type:text/csv');
        header('Content-Disposition:filename=' . $filename);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $titleArray);
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

    public function export()
    {
        $data_trr['importSequenceId'] = I('importSequenceId');
        $data_trr['importSequenceStartTime'] = I('importSequenceStartTime');
        $data_trr['importSequenceEndTime'] = I('importSequenceEndTime');
        $data_trr['orderId'] = I('orderId');
        $data_trr['phone'] = I('phone');
        $data_trr['userId'] = I('userId');
        $data_trr['pointId'] = I('pointId');
        $data_trr['businessId'] = I('businessId');
        $data_trr['callState'] = I('callState');
        $data_trr['state'] = I('state');
        $data_trr['startCallTime'] = I('startCallTime');
        $data_trr['endCallTime'] = I('endCallTime');
        $data_trr['notConnectCount'] = I('notConnectCount');
        $data_trr['district'] = I('district');
        $data_trr['visitTime'] = I('visitTime');
        $data_trr['orders.Deleted'] = 0;
        $map = where($data_trr);
        $orders = M('orders');
        $count = $orders->where($data_trr)->count();
        $limit = 1000;
        $ppp = ceil($count / $limit);
        $pp = range(1, $ppp);
        foreach ($pp as $kkk => $vvv) {
            $res[$kkk] = $orders->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
                ->join('left join users on orders.UserId=users.Id')
                ->join('left join points on orders.PointId=points.Id')->field('orders.*,importsequences.ImportTime as importsequencetime,users.name as username,points.address as pointaddress')->where($map)->limit($vvv, $limit)->select();

            $str[$kkk] = "订单编号,套餐,订购号码,下单时间,地址,收件人,电话号码,调度员备注,派送员备注,地区,订单纬度,经度,接单时纬度,接单时经度,接单地址,完结时纬度,完结时经度,完结地址,最后呼叫状态,订单状态,订单类型,最后呼叫时间,优先级,快递员编号,业务编号,导入序列编号,导入时间,配送点编号,上门时间,需要外呼,配送员,配送点地址,分配时间,接单时间,完结时间,下次呼叫时间,最后呼叫工号,最后操作调度员,调度员退单原因,快递员退单原因,调度员非当天上门原因,派送员非当天上门原因,副卡号码,额外信息";

            $exl11[$kkk] = explode(',', $str[$kkk]);
            foreach ($res[$kkk] as $k => $v) {
                $v['lastcallname'] = $this->getLastCallname($v['id']);
                $v['editbyname'] = $this->getLastEditbyname($v['id']);
                $exl[$kkk][] = array(
                    $v['orderid'], $v['planname'], $v['orderphone'], $v['ordertime'], $v['address'], $v['consignee'], $v['phone'], $v['scheduleremark'], $v['expressremark'], $v['district'], $v['lat'], $v['lng'], $v['startlat'], $v['startlng'], $v['startaddress'], $v['endlat'], $v['endlng'], $v['endaddress'], $v['lastcallstate'], $v['state'], $v['ordertype'], $v['lastcalltime'], $v['priority'], $v['userid'], $v['businessid'], $v['importsequenceid'], $v['importsequencetime'], $v['pointid'], $v['visittime'], $v['block'], $v['username'], $v['pointaddress'], $v['assigntime'], $v['accepttime'], $v['completetime'], $v['nextcalltime'], $v['lastcallname'], $v['editbyname'], $v['schedulecancelreason'], $v['expresscancelreason'], $v['scheduleanotherdayreason'], $v['expressanotherdayreason'], $v['secondarycard'], $v['extra']
                );
            }

            Method::exportToExcel('orders_' . time() . $vvv . '.csv', $exl11[$kkk], $exl[$kkk]);
        }
    }

    private function getLastCallname($orderid)
    {
        $name = "";
        $callhistories = M('callhistories');
        $map['Name'] = array('neq', 'Robot');
        $map['Deleted'] = 0;
        $map['OrderId'] = $orderid;
        $callhistoriesinfo = $callhistories->field('name')->where($map)->order('StartTime desc')->find();
        if ($callhistoriesinfo) {
            $name = $callhistoriesinfo['name'];
        }
        return $name;
    }

    private function getLastEditbyname($orderid)
    {
        $name = "";
        $sql = 'select EditBy FROM orderhistory as a LEFT JOIN users on a.EditBy=users.Name LEFT JOIN userroles as b on users.Id=b.UserId LEFT JOIN roles on b.RoleId=roles.Id where roles.RoleName=\'schedule\' and roles.Deleted=0 and a.Deleted=0 and users.Deleted=0 and a.RowId=' . $orderid . ' ORDER BY a.Created desc LIMIT 1';
        $list = M()->query($sql);
        if ($list) {
            $name = $list[0]['editby'];
        }
        return $name;
    }
}