<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/2/27
 * Time: 16:59
 */

namespace Home\Controller;

use Think\Controller;
use \ZipArchive;

class TestController extends Controller
{


    public function demo(){
        $str = "__GetZoneResult_ = {
            mts:'1352558',
            province:'河南',
            catName:'中国移动',
            telString:'13525588695',
            areaVid:'30500',
            ispVid:'3236139',
            carrier:'河南移动'
        }";

        preg_match_all("/(\w+):'(.*?)'/", $str, $match);
        $keys = array_keys($match[1]);
        $values = array_values($match[2]);

        var_dump($values);

//        $str1 = '{  name:"fdipzone",  sex:"男"}';
//        preg_match('/__GetZoneResult_ = {mts:(.*?),province:(.*?),catName:(.*?),telString:(.*?),areaVid:(.*?),ispVid:(.*?),carrier:(.*?)}/', $str, $match);
//        preg_match('/{(.*?)}/', $str, $match);
//        var_dump($match);

//        $res = $this->ext_json_decode($str,true);
//        var_dump($res);
    }


    function ext_json_decode($str, $mode=false){
        if(preg_match('/\w:/', $str)){
            $str = preg_replace('/(\w+):/is', '"$1":', $str);
        }
        return json_decode($str, $mode);
    }
    //字段对应的标题
    private $title = array(
        'orderid' => '订单编号',
        'planname' => '套餐',
        'orderphone' => '订购号码',
        'ordertime' => '下单时间',
        'address' => '地址',
        'consignee' => '收件人',
        'phone' => '电话号码',
        'scheduleremark' => '调度员备注',
        'expressremark' => '派送员备注',
        'district' => '地区',
        'lat' => '订单纬度',
        'lng' => '订单经度',
        'startlat' => '接单时纬度',
        'startlng' => '接单时经度',
        'startaddress' => '接单地址',
        'endlat' => '完结时纬度',
        'endlng' => '完结时经度',
        'endaddress' => '完结地址',
        'lastcallstate' => '最后呼叫状态',
        'state' => '订单状态',
        'ordertype' => '订单类型',
        'lastcalltime' => '最后呼叫时间',
        'priority' => '优先级',
        'userid' => '快递员编号',
        'businessid' => '业务编号',
        'importsequenceid' => '导入序列编号',
        'importsequencetime' => '导入时间',
        'pointid' => '配送点编号',
        'visittime' => '上门时间',
        'block' => '需要外呼',
        'username' => '配送员',
        'pointaddress' => '配送点地址',
        'assigntime' => '分配时间',
        'accepttime' => '接单时间',
        'completetime' => '完结时间',
        'nextcalltime' => '下次呼叫时间',
        'lastcallname' => '最后呼叫工号',
        'editbyname' => '最后操作调度员',
        'schedulecancelreason' => '调度员退单原因',
        'expresscancelreason' => '快递员退单原因',
        'scheduleanotherdayreason' => '调度员非当天上门原因',
        'expressanotherdayreason' => '派送员非当天上门原因',
        'secondarycard' => '副卡号码',
        'extra' => '额外信息'
    );

    //文件名
    private $filename = 'orders';

    //字段值过滤器
    private $filter = array();

    //存储文件的临时目录
    private $stodir = './Public/excel/';
public function test(){
    $url='https://shbs10014.shwo10016.cn/orderschedule/index.php/Home/orders/outcall';
    $data=doGet($url);
    print_r($data);die();
}
    public function index()
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
        $orders = M('orders');
        $total = 20000;
        $limit = 5000;
        $this->filename($this->filename);
        $this->title($this->title);
        $map = where($data_trr);
        for ($i = 0; $i < ceil($total / $limit); $i++) {
            $offset = $i * $limit;
            $orderslist = $orders
                ->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
                ->join('left join users on orders.UserId=users.Id')
                ->join('left join points on orders.PointId=points.Id')
                //,t.name as lastcallname,k.EditBy as editbyname
                ->field('orders.*,importsequences.ImportTime as importsequencetime,users.name as username,points.address as pointaddress')
                ->where($map)->limit($offset, $limit)->select();
            foreach ($orderslist as $item => &$value) {
                $value['lastcallname'] = $this->getLastCallname($value['id']);
                $value['editbyname'] = $this->getLastEditbyname($value['id']);
            }
            unset($value);
            $this->excel($orderslist, $i + 1); //生成多个文件时的文件名后面会标注'（$i+1）'
        }
        $this->fileload();
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
        $sql='select EditBy FROM orderhistory as a LEFT JOIN users on a.EditBy=users.Name LEFT JOIN userroles as b on users.Id=b.UserId LEFT JOIN roles on b.RoleId=roles.Id where roles.RoleName=\'schedule\' and roles.Deleted=0 and a.Deleted=0 and users.Deleted=0 and a.RowId='.$orderid.' ORDER BY a.Created desc LIMIT 1';
        $list = M()->query($sql);
        if ($list) {
            $name = $list[0]['editby'];
        }
        return $name;
    }

    /**
     * 生成 excel 数据表文件
     * @param  array $data 要导出的数据
     * @return bool
     */
    public function excel($data = array(), $i = 1)
    {
        set_time_limit(0);
        header("Content-type: text/html; charset=utf-8");
        if ($data && is_array($data)) {
            $filename = empty($this->filename) ? $this->filename : date('Y_m_d');
            $filter = $this->filter;
            $current = current($data);
            if (is_array($current)) {
                $filePath = $this->stodir . $filename . "($i)" . '.csv';
                $fp = fopen($filePath, 'a');
                $t = fputcsv($fp, $this->titleColumn(array_keys($current)));
                foreach ($data as &$row) {
                    foreach ($row as $k => &$v) {
                        if (isset($filter[$k])) {
                            if ($filter[$k] == 'datetime') {
                                $v = date("Y-m-d H:i:s", $v);
                            }
                            if ($filter[$k] == 'date') {
                                $v = date("Y-m-d", $v);
                            }
                            if (is_array($filter[$k])) {
                                $v = isset($filter[$k][$v]) ? $filter[$k][$v] : $v;
                            }
                        }
                    }
                    fputcsv($fp, $row);
                }
                fclose($fp);
                unset($data);
                return true;
            }
        }
        return false;
    }

    /**
     * 打包好zip文件并导出
     * @param  [type] $filename [description]
     * @return [type]           [description]
     */
    public function fileload()
    {
        $zipname = "../" . $this->filename . '.zip';
        $zipObj = new ZipArchive();
        if ($zipObj->open($zipname, ZipArchive::CREATE) === true) {
            $res = false;
            foreach (glob($this->stodir . "*") as $file) {
                $res = $zipObj->addFile($file);
            }
            $zipObj->close();
            if ($res) {
                header("Content-type: text/html; charset=utf-8");
                header("Cache-Control: max-age=0");
                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment;filename =" . $zipname);
                header('Content-Type: application/zip');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($zipname));
                @readfile($zipname);//输出文件;
                //清理临时目录和文件
                $this->deldir($this->stodir);
                @unlink($zipname);
                ob_flush();
                flush();
            } else {
                $this->deldir($this->stodir);
                ob_flush();
                flush();
                die('暂无文件可下载！');
            }
        } else {
            $this->deldir($this->stodir);
            ob_flush();
            flush();
            die('文件压缩失败！');
        }
    }

    /**
     * 清理目录，删除指定目录下所有内容及自身文件夹
     * @param  [type] $dir [description]
     * @return [type]       [description]
     */
    private function deldir($dir)
    {
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_dir($file)) {
                    deldir($file);
                    @rmdir($file);
                } else {
                    @unlink($file);
                }
            }
            @rmdir($dir);
        }
    }

    /**
     * 设置标题
     * @param array $title 标题参数为字段名对应标题名称的键值对数组
     * @return obj this
     */
    public function title($title)
    {
        if ($title && is_array($title)) {
            $this->title = $title;
        }
        return $this;
    }

    /**
     * 设置导出的文件名
     * @param string $filename 文件名
     * @return obj this
     */
    public function filename($filename)
    {
        $this->filename = date('Y_m_d') . (string)$filename;
        if (!is_dir("../" . $this->filename)) {
            mkdir($this->convertEncoding("../" . $this->filename));
        }
        $this->stodir = "../" . $this->filename . "/";
        return $this;
    }

    public function convertEncoding($string)
    {
        //根据系统进行配置
        $encode = stristr(PHP_OS, 'WIN') ? 'GBK' : 'UTF-8';
        $string = iconv('UTF-8', $encode, $string);
        //$string = mb_convert_encoding($string, $encode, 'UTF-8');
        return $string;
    }

    /**
     * 设置字段过滤器
     * @param array $filter 文件名
     * @return obj this
     */
    public function filter($filter)
    {
        $this->filter = (array)$filter;
        return $this;
    }

    /**
     * 确保标题字段名和数据字段名一致,并且排序也一致
     * @param  array $keys 要显示的字段名数组
     * @return array 包含所有要显示的字段名的标题数组
     */
    protected function titleColumn($keys)
    {
        $title = $this->title;
        if ($title && is_array($title)) {
            $titleData = array();
            foreach ($keys as $v) {
                $titleData[$v] = isset($title[$v]) ? $title[$v] : $v;
            }
            return $titleData;
        }
        return $keys;
    }

}