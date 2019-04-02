<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/3/7
 * Time: 9:54
 */

namespace Home\Controller;

use Think\Controller;
use Think\Log;
use Common\Core\Method;

header("Content-Type: application/json; charset=utf-8");

class DistanceController extends Controller
{
    public function test1()
    {
        $Lng1 = '121.557403';
        $Lat1 = '31.232669';
        $orders = M('orders');
        $map['orders.UserId'] = 90;
        $map['orders.Deleted'] = 0;
        $endpoints = '';
        $map['orders.State'] = array(array('EQ', '派送'), array('EQ', '派送中'), array('EQ', '改约'), 'OR');
        $orderlist = $orders->join('RIGHT JOIN businesses ON orders.BusinessId = businesses.Id')
            ->field('orders.orderId,orders.userId,DATE_FORMAT(orders.VisitTime,\'%Y-%m-%d %H\') as format,orders.VisitTime,orders.acceptTime,
            orders.assignTime,orders.planName,orders.orderTime,orders.scheduleRemark,orders.lat,orders.lng,orders.BusinessId,orders.Id,
            CASE orders.State WHEN \'派送中\' THEN 1 WHEN \'改约\' THEN 2 ELSE 3 END as testCol,businesses.name,businesses.type,businesses.description')
            ->order('format asc,testCol asc')->where($map)->select();
        if (count($orderlist) > 0) {
         /*   foreach ($orderlist as $value => &$k) {
                $Lat2 = $k['lat'] > 0 ? $k['lat'] : 0;
                $Lng2 = $k['lng'] > 0 ? $k['lng'] : 0;
                $k['distance'] = intval($this->getDistance($Lat1, $Lng1, $Lat2, $Lng2) / 1000);
            }
            unset($k);*/
            foreach ($orderlist as $value => &$item) {
                $Lat = $item['lat'] > 0 ? $item['lat'] : 0;
                $Lng = $item['lng'] > 0 ? $item['lng'] : 0;
                $ends .= empty($ends) ? $Lng . ',' . $Lat : '|' . $Lng . ',' . $Lat;
            }
            unset($item);
            $start = $Lng1 . ',' . $Lat1;
            $distancearr = $this->getDistance($start, $ends);
            print_r($distancearr);die();
            $j = 0;
            foreach ($orderlist as $value => &$k) {
                // $total = $distancearr['results'][$j]['distance'] / 1000;
                $total = intval($distancearr['results'][$j]['distance'] / 1000);
                $k['distance'] = $total;
                $j++;
            }
            unset($k);
            $list = array();
            foreach ($orderlist as $k => &$v) {//分组排序
                $list[$v['format']][$v['testcol']][$v['distance']][] = $v;
                ksort($list[$v['format']]);
                ksort($list[$v['format']][$v['testcol']]);
            }
            unset($v);
            print_r($orderlist);die();
            $listtime = array_keys($list);
            $list2 = array();
            foreach ($listtime as $value => $k) {//通过时间拆分数组
                $testcoltrr[] = array_keys($list[$k]);
                $list2[] = $list[$k];
            }
            $list3 = array();
            foreach ($list2 as $value => $k) {//通过状态键名拆分数组
                for ($i = 0; $i < count($testcoltrr[$value]); $i++) {
                    $list3[] = $k[$testcoltrr[$value][$i]];
                    $distancetrr[] = array_keys($k[$testcoltrr[$value][$i]]);
                }
            }
            $list4 = array();
            foreach ($list3 as $value => $k) {//通过距离键名拆分数组
                for ($i = 0; $i < count($distancetrr[$value]); $i++) {
                    $list4[] = $k[$distancetrr[$value][$i]];
                }
            }
            $list = array();
            foreach ($list4 as $value => $k) {//整理成为二维数组
                for ($i = 0; $i < count($list4[$value]); $i++) {
                    $list[] = $list4[$value][$i];
                }
            }

            foreach ($list as $value => &$k) {
                $return_data['orders'][]['order'] = $k;
                foreach ($return_data['orders'] as $v => &$k2) {
                    $k2['order']['orderId'] = $k2['order']['orderid'];
                    $k2['order']['userId'] = Method::getInt($k2['order']['userid']);
                    $k2['order']['visitTime'] = $k2['order']['visittime'];
                    $k2['order']['acceptTime'] = $k2['order']['accepttime'];
                    $k2['order']['assignTime'] = $k2['order']['assigntime'];
                    $k2['order']['planName'] = $k2['order']['planname'];
                    $k2['order']['orderTime'] = $k2['order']['ordertime'];
                    $k2['order']['scheduleRemark'] = $k2['order']['scheduleremark'];
                    $k2['order']['expressRemark'] = $k2['order']['expressremark'];
                    $k2['distance'] = $k2['order']['distance'];
                    $k2['order']['id'] = Method::getInt($k2['order']['id']);
                    $k2['id'] = Method::getInt($k2['order']['id']);
                    $k2['order']['business'] = array(
                        'name' => $k2['order']['name'],
                        'description' => $k2['order']['description'],
                        'type' => $k2['order']['type'],
                    );
                }
            }
            unset($k);
        }
        print_r($return_data);
        die();
    }

    public function getDistance($start, $end)
    {
        $key = '4a9acf4ec92ff9a5f4c99bb6006503b7';
        $real_url = 'https://restapi.amap.com/v3/distance?type=1&origins=' . $end . '&destination=' . $start . '&key=' . $key;
        $data = doGet($real_url);
        $arr = json_decode($data, true);
        return $arr;
    }

    public function baidu()
    {
        $lng1 = '121.557403';
        $lat1 = '31.232669';
        $lng2 = '121.568625';
        $lat2 = '31.252689';
        $start = $lat1 . ',' . $lng1;//上海邮通机械制造有限公司
        $end = $lat2 . ',' . $lng2;//上海市浦东新区金桥镇博山东路444号
        $key = 'QQcNGNNZsuXxKU5qC9uVhd72kxa7Orna';
        $url = 'https://api.map.baidu.com/routematrix/v2/riding?';  //GET请求
        $real_url = $url . 'origins=' . $start . '&destinations=' . $end . '&ak=' . $key;
        $data = doGet($real_url);
        print_r($data);
        die();
    }

    public function pachong()
    {
        $orderid=1090;
        $userid=125;
        $order=M('orders');
     //   $map['District']='奉贤区';
        $map['Id']=$orderid;
        $map['Deleted']=0;
        $orderinfo=$order->field('OrderId,District')->where('Id='.$orderid)->find();
        if ($orderinfo==false){
            Log::record('插入爬虫数据失败，未查询到订单');
            return false;
        }
        if ($orderinfo['district']!='奉贤区'){
            Log::record('插入爬虫数据失败，未查询到奉贤区订单');
            return false;
        }
        $users=M('users');
         $usersinfo=$users->field('Phone')->where('Id='.$userid)->find();
        if ($usersinfo==false){
            Log::record('插入爬虫数据失败，未查询到派送员');
            return false;
        }
        $data=array(
            'ORDER_ID'=>$orderinfo['orderid'],
            'PHONE'=>$usersinfo['phone'],
            'ADDTIME'=>date("Y-m-d H:i:s"),
            'UPDATETIME'=>date("Y-m-d H:i:s"),
        );
        $table = 'cy_order_update';
        $_conn = mysqli_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'), C('CRAWLER_DB_NAME'), C('DB_PORT'));
        if (!$_conn) {
            die('数据库连接失败');
        }
        $sql ='insert into '.$table.' (ORDER_ID,PHONE,ADDTIME,UPDATETIME) Values ("'.$data['ORDER_ID'].'","'.$data['PHONE'].'","'.$data['ADDTIME'].'","'.$data['ADDTIME'].'")';
        $result = mysqli_query($_conn, $sql, MYSQLI_USE_RESULT);
        if ($result==false){
            Log::record('插入爬虫数据失败，插入失败');
            return false;
        }
        return true;
    }
    public function test(){
        $str='/API//REC/manREC/20190314/20190314154225_Out_18939938181.mp3';
        $arr = explode('/', $str);
        $count = count($arr)-1;
        $recordFile = $arr[$count];
    }
}