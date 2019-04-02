<?php
function doGet($url)
{//初始化

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

function doPost($url, $post_data, $header = '')
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

function return_err($msg)
{
    $data['code'] = 400;
    $data['error'] = array('message' => $msg);
    $data['data'] = '';
    echo json_encode($data);
    die();
}

function return_err_500($msg)
{
    $data['code'] = 500;
    $data['error'] = array('message' => $msg);
    $data['data'] = '';
    echo json_encode($data);
    die();
}

function return_err_401($msg)
{
    $data['code'] = 401;
    $data['error'] = array('message' => $msg);
    $data['data'] = '';
    echo json_encode($data);
    die();
}

function return_data($list)
{
    $data['code'] = 200;
    $data['error'] = null;
    $data['data'] = $list;
    echo json_encode($data);
    die();
}

function is_json()
{
    $datajson = file_get_contents('php://input');
    $dataarr = json_decode($datajson, true);
    if ($dataarr) {
        return $dataarr;
    } else {
        $data['code'] = 400;
        $data['error'] = '请求参数不是json格式';
        $data['data'] = '';
        echo json_encode($data);
        die();
    }
}

function is_cookie()
{
    $value = cookie('username');
    if (empty($value)) {
        $data['code'] = 403;
        $data['error'] = '请登录';
        $data['data'] = '';
        echo json_encode($data);
        die();
    } else {
        return $value;
    }
}

function json_patch($jsonPatchDocument)
{

    foreach ($jsonPatchDocument as $value => &$k) {
        $path = explode('/', $k['path']);
        $upper_path = ucfirst($path[1]);
        $return_data[$upper_path] = $k['value'];
    }
    unset($k);
    return $return_data;
}

function exportExcel($expTitle, $expCellName, $expTableData)
{
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
    $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    $objPHPExcel = new \PHPExcel();
    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    for ($i = 0; $i <= $cellNum; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
    }
    for ($i = 0; $i <= $dataNum; $i++) {
        for ($j = 0; $j <= $cellNum; $j++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 2), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xlsx"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

    $objWriter->save('php://output');
    return true;
}

function getDistance($lat1, $lng1, $lat2, $lng2)
{
        $earthRadius = 6367000; //approximate radius of earth in meters
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance =round( $earthRadius * $stepTwo);
    return $calculatedDistance;
}

function orderhistory($data)
{
    $orderhistory = M('orderhistory');
    $add = $orderhistory->add($data);
    if ($add == false) {
        return false;
    } else {
        return true;
    }
}

function field($data)
{
    $array = array_keys($data);
    $field = implode(",", $array);
    return $field;
}

// 调用该函数，传递长度默认参数$pw_length = 6
function create_password($pw_length = 16)
{
    $randpwd = '';
    for ($i = 0; $i < $pw_length; $i++) {
        $randpwd .= chr(mt_rand(33, 126));
    }
    return $randpwd;
}

function order_history($orderid, $usersinfo, $orderinfo, $return_data)
{
    $newtime = date('Y-m-d H:i:s');
    $data['RowId'] = $orderid;
    $data['TableName'] = 'orders';
    $data['Changed'] = '';
    $data['Kind'] = 3;
    $data['Created'] = $newtime;
    $data['EditBy'] = $usersinfo['name'];
    $data['Deleted'] = 0;
    $data['Before'] = json_encode($orderinfo, JSON_UNESCAPED_UNICODE);
    $data['After'] = json_encode($return_data, JSON_UNESCAPED_UNICODE);
    $add = orderhistory($data);
    if ($add == false) {
        return false;
    } else {
        return true;
    }
}

function where($datatrr)
{
    $map['orders.Deleted'] = 0;
    if (!empty($datatrr['importSequenceId'])) {
        $map['orders.ImportSequenceId'] = $datatrr['importSequenceId'];
    }
    if (!empty($datatrr['importSequenceStartTime']) || !empty($datatrr['importSequenceEndTime'])) {
        if (!empty($datatrr['importSequenceStartTime'])) {
            $map['importsequences.ImportTime'][] = array('EGT', $datatrr['importSequenceStartTime']);
        }
        if (!empty($datatrr['importSequenceEndTime'])) {
            $map['importsequences.ImportTime'][] = array('ELT',$datatrr['importSequenceEndTime']);
        }
    }
    if (!empty($datatrr['orderId'])) {
        if (strlen($datatrr['orderId']) >= 10) {
            $map['orders.OrderId'] = $datatrr['orderId'];
        } else {
            $map['orders.Id'] = $datatrr['orderId'];
        }
    }
    if (!empty($datatrr['phone'])) {
        $map['orders.Phone'] = $datatrr['phone'];
    }
    if (!empty($datatrr['userId'])) {
        $map['orders.UserId'] = $datatrr['userId'];
    }
    if (!empty($datatrr['pointId'])) {
        $map['orders.PointId'] = $datatrr['pointId'];
    }
    if (!empty($datatrr['businessId'])) {
        $map['orders.BusinessId'] = $datatrr['businessId'];
    }
    if (!empty($datatrr['callState'])) {
        $map['orders.LastCallState'] = $datatrr['callState'];
    }
    /*if (count($datatrr['state'])>0) {
      //  $datatrr['state']=array('完成','改约');
        $arr=array();
        for ($i=0;$i<count($datatrr['state']);$i++){
            $arr[] = array('eq', $datatrr['state'][$i]);
        }
        $where['orders.state']=$arr;
        $where['_logic'] = 'or';
        $map['_complex'][]= $where;
        $map=array(array('eq','完成'),array('eq','改约'),'or');
    }*/
if (count($datatrr['state'])>0) {
        $str=implode(',',$datatrr['state']);
            $map['orders.State'] = array('in',$str);
    }
    if (!empty($datatrr['startCallTime'])) {
        $map['orders.LastCallTime'][] = array('EGT', $datatrr['startCallTime']);
    }
    if (!empty($datatrr['endCallTime'])) {
        $map['orders.LastCallTime'][] = array('ELT', $datatrr['endCallTime']);
    }

    if (!empty($datatrr['startOrderTime'])) {
        $map['orders.OrderTime'][] = array('EGT', $datatrr['startOrderTime']);
    }
    if (!empty($datatrr['endOrderTime'])) {
        $map['orders.OrderTime'][] = array('ELT', $datatrr['endOrderTime']);
    }
    if (!empty($datatrr['startCompleteTime'])) {
        $map['orders.CompleteTime'][] = array('EGT', $datatrr['startCompleteTime']);
    }
    if (!empty($datatrr['endCompleteTime'])) {
        $map['orders.CompleteTime'][] = array('ELT', $datatrr['endCompleteTime']);
    }
    if (!empty($datatrr['visitTime'])) {
        $map['orders.VisitTime'][] = array('EGT', date('Y-m-d 00:00:00',strtotime($datatrr['visitTime'])));
        $map['orders.VisitTime'][] = array('ELT',  date('Y-m-d 23:59:59',strtotime($datatrr['visitTime'])));
    }
    if (!empty($datatrr['notConnectCount'])) {
        $map['orders.notConnectCount'] = $datatrr['notConnectCount'];
    }
    if (!empty($datatrr['district'])){
        $map['orders.District'] = $datatrr['district'];
    }
    return $map;
}

function data_import($filename, $exts = 'xls')
{
    //创建PHPExcel对象，注意，不能少了\
    new \PHPExcel();
    //如果excel文件后缀名为.xls，导入这个类
    if ($exts == 'xls') {
        import("Org.Util.PHPExcel.Reader.Excel5");
        $PHPReader = new \PHPExcel_Reader_Excel5();
    } else if ($exts == 'xlsx') {
        import("Org.Util.PHPExcel.Reader.Excel2007");
        $PHPReader = new \PHPExcel_Reader_Excel2007();
    } else if ($exts == 'csv') {
        import("Org.Util.PHPExcel.Reader.CSV");
        $PHPReader = new \PHPExcel_Reader_CSV();
    } else {
        return_err('不支持文件类型');
    }
    //载入文件
    $PHPExcel = $PHPReader->load($filename, $encode = 'utf-8');
    //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
    $currentSheet = $PHPExcel->getSheet(0);
    //获取总列数
    $allColumn = $currentSheet->getHighestColumn();
    //获取总行数
    $allRow = $currentSheet->getHighestRow();
    //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
    if ($allRow > 1000) {
        return_err('文件不能大于1000条');
    }
    $i = 0;
    for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
        //从哪列开始，A表示第一列
        for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
            //数据坐标
            $address = $currentColumn . $currentRow;
            if ($currentColumn == 'D') {//指定H列为时间所在列
                $data[$i][$currentColumn] = gmdate("Y-m-d H:i:s", PHPExcel_Shared_Date::ExcelToPHP($currentSheet->getCell($address)->getValue()));
            } else {
                //读取到的数据，保存到数组$arr中
                //  $data[$i][$currentColumn] = $currentSheet->getCell($address)->getValue();
                // 开始格式化
                // 获取excel C2的文本
                import("Org.Util.PHPExcel");   // 这里不能漏掉
                import("Org.Util.PHPExcel.IOFactory");
                $cell = $currentSheet->getCell($address)->getValue();
                // 开始格式化
                if (is_object($cell)) {
                    $cell = $cell->__toString();
                }
                $data[$i][$currentColumn] = $cell;
            }
        }
        $i++;
    }
    return $data;
}

function find($table, $where = null, $field = null)
{
    $mysql = M($table);
    if ($where == null) {
        $where = array('Delete' => 0);
    }
    if ($field == null) {
        $field = '*';
    }
    $info = $mysql->field($field)->where($where)->find();
    return $info;
}

function array_sort($arr, $keys, $type = 'asc')
{
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    $i = 0;
    foreach ($keysvalue as $k => $v) {
        $new_array[$i] = $arr[$k];
        $i++;
    }
    return $new_array;
}

