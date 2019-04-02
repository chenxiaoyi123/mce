<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/9
 * Time: 15:05
 */

namespace Home\Controller;

use Common\Core\Method;
use Think\Controller;
use Think\Log;

header("Content-Type: application/json; charset=utf-8");

class ExpressesController extends Controller
{
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Expresses/waitingOrder",
     *   tags={"Expresses"},
     *   summary="获取未完成订单 派送, 派送中, 改约",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="Lat",
     *     in="query",
     *     description="经度",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="Lng",
     *     in="query",
     *     description="纬度",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="successful operation",
     *     @SWG\Schema(type="string"),
     *     @SWG\Header(
     *       header="X-Rate-Limit",
     *       type="integer",
     *       format="int32",
     *       description="calls per hour allowed by the user"
     *     ),
     *     @SWG\Header(
     *       header="X-Expires-After",
     *       type="string",
     *       format="date-time",
     *       description="date in UTC when token expires"
     *     )
     *   ),
     *   @SWG\Response(response=400, description="Invalid username/password supplied")
     * )
     */
    public function waitingOrder()
    {
        $username = is_cookie();
        $Lat1 = I('lat');
        $Lng1 = I('lng');
        $Lat1 = $Lat1 > 0 ? $Lat1 : 0;
        $Lng1 = $Lng1 > 0 ? $Lng1 : 0;
        $orders = M('orders');
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $map['orders.UserId'] = $usersinfo['id'];
        Log::record($usersinfo['id'] . 'lat' . $Lat1 . 'lng' . $Lng1);
        $map['orders.Deleted'] = 0;
        $map['orders.State'] = array(array('EQ', '派送'), array('EQ', '派送中'), array('EQ', '改约'), 'OR');
        $orderlist = $orders->join('RIGHT JOIN businesses ON orders.BusinessId = businesses.Id')
            ->field('orders.orderId,orders.userId,DATE_FORMAT(orders.VisitTime,\'%Y-%m-%d %H\') as format,orders.VisitTime,orders.acceptTime,
            orders.assignTime,orders.planName,orders.orderTime,orders.scheduleRemark,orders.expressRemark,orders.lat,orders.lng,orders.BusinessId,orders.Phone,orders.Address,orders.Consignee,orders.State,orders.Id,
           CASE orders.State WHEN \'派送中\' THEN 1 WHEN \'改约\' THEN 2 ELSE 3 END as testCol,businesses.name,businesses.type,businesses.description')
            //->field('orders.*,CASE orders.State WHEN \'派送中\' THEN 1 WHEN \'改约\' THEN 2 ELSE 3 END as testCol,businesses.name,businesses.type,businesses.description')
            ->order('format asc')->where($map)->select();
        if (count($orderlist) > 0) {
//真实距离
          foreach ($orderlist as $value => &$item) {
                $Lat = $item['lat'] > 0 ? $item['lat'] : 0;
                $Lng = $item['lng'] > 0 ? $item['lng'] : 0;
                $ends .= empty($ends) ? $Lng . ',' . $Lat : '|' . $Lng . ',' . $Lat;
            }
            unset($item);
            $start = $Lng1 . ',' . $Lat1;
            $distancearr = $this->getDistance($start, $ends);
            $j = 0;
            foreach ($orderlist as $value => &$k) {
              $total = $distancearr['results'][$j]['distance'] / 1000;
                //$total = intval($distancearr['results'][$j]['distance'] / 1000);
                $k['distance'] = $total;
                $j++;
            }
            unset($k);
//直线距离
           /* foreach ($orderlist as $value => &$k) {
                    $Lat2 = $k['lat'] > 0 ? $k['lat'] : 0;
                    $Lng2 = $k['lng'] > 0 ? $k['lng'] : 0;
                    $k['distance'] = intval(getDistance($Lat1, $Lng1, $Lat2, $Lng2) / 1000);
                }

           unset($k);*/

            $list = array();
            foreach ($orderlist as $k => &$v) {//分组排序
                $list[$v['format']][$v['testcol']][$v['distance']][] = $v;
                ksort($list[$v['format']]);
                ksort($list[$v['format']][$v['testcol']]);
            }
            unset($v);
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
            return_data($return_data);
        } else {
            return_err('没有未完成订单');
        }
    }

    public function getDistance($start, $end)
    {
        $key = '4a9acf4ec92ff9a5f4c99bb6006503b7';
        $real_url = 'https://restapi.amap.com/v3/distance?type=1&origins=' . $end . '&destination=' . $start . '&key=' . $key;
        $data = doGet($real_url);
        $arr = json_decode($data, true);
        return $arr;
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Expresses/finalStateOrders",
     *   tags={"Expresses"},
     *   summary="获取已完结订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="PageIndex",
     *     in="query",
     *     description="页数",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="PageSize",
     *     in="query",
     *     description="数据条数",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="successful operation",
     *     @SWG\Schema(type="string"),
     *     @SWG\Header(
     *       header="X-Rate-Limit",
     *       type="integer",
     *       format="int32",
     *       description="calls per hour allowed by the user"
     *     ),
     *     @SWG\Header(
     *       header="X-Expires-After",
     *       type="string",
     *       format="date-time",
     *       description="date in UTC when token expires"
     *     )
     *   ),
     *   @SWG\Response(response=400, description="Invalid username/password supplied")
     * )
     */
    public function finalStateOrders()
    {
        $username = is_cookie();
        $PageIndex = I('pageIndex');
        $PageSize = I('pageSize');
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;
        $limit = $offset . ", " . $PageSize;
        $orders = M('orders');
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }

//        list($cur_start_month_day,$cur_end_month_day) = Method::getMonthDaysTwoMethod();

        $map['UserId'] = $usersinfo['id'];
        $map['Deleted'] = 0;
//        $map['CompleteTime'][] = array('EGT', $cur_start_month_day);
//        $map['CompleteTime'][] = array('ELT', $cur_end_month_day);
        $map['State'] = array(array('EQ', '成功'), array('EQ', '转物流'), array('EQ', '退单'), array('EQ', '已激活'), array('EQ', '拒访'), array('EQ', '转区域'), 'OR');
        $list = $orders->where($map)->order('CompleteTime desc')->limit($limit)->select();
//        echo $orders->fetchSql(true)->where($map)->order('CompleteTime desc')->limit($limit)->select();
        foreach ($list as &$row) {
            $row['orderId'] = $row['orderid'];
            $row['completeTime'] = $row['completetime'];
            $row['scheduleRemark'] = $row['scheduleremark'];
            $row['expressRemark'] = $row['expressremark'];
            unset($row['completetime'], $row['scheduleremark'], $row['expressremark'], $row['orderid']);
        }
        unset($row);
        $total = $orders->where($map)->count();
        if ($PageIndex > 1 && $total > 0) {
            $hasPreviousPage = true;
        } else {
            $hasPreviousPage = false;
        }
        if (($total / $PageSize) > $PageIndex) {
            $hasNextPage = true;
        } else {
            $hasNextPage = false;
        }
        $return_data['pageInfo'] = array(
            'entities' => $list,
            'total' => intval($total),
            'pageIndex' => $PageIndex,
            'pageSize' => $PageSize,
            'hasPreviousPage' => $hasPreviousPage,
            'hasNextPage' => $hasNextPage,
        );
        return_data($return_data);
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Expresses/accept?id={id}",
     *   tags={"Expresses"},
     *   summary="接受订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="lat",
     *     in="query",
     *     description="经度",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="lng",
     *     in="query",
     *     description="纬度",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     description="位置",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="successful operation",
     *     @SWG\Schema(type="string"),
     *     @SWG\Header(
     *       header="X-Rate-Limit",
     *       type="integer",
     *       format="int32",
     *       description="calls per hour allowed by the user"
     *     ),
     *     @SWG\Header(
     *       header="X-Expires-After",
     *       type="string",
     *       format="date-time",
     *       description="date in UTC when token expires"
     *     )
     *   ),
     *   @SWG\Response(response=400, description="Invalid username/password supplied")
     * )
     */
    public function accept()
    {
        $username = is_cookie();
        $datatrr = is_json();
        $orderid = $datatrr['orderId'];
        if (!$orderid > 0) {
            return_err('参数错误');
            Log::record('参数错误' . $orderid);
        }
        $lat = $datatrr['lat'];
        $lng = $datatrr['lng'];
        $address = $datatrr['address'];
        $lat = empty($lat) ? 0 : $lat;
        $lng = empty($lng) ? 0 : $lng;
        $orders = M('orders');
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $map['UserId'] = $usersinfo['id'];
        $map['Id'] = $orderid;
        $map['Deleted'] = 0;
        $map['State'] = array(array('EQ', '改约'), array('EQ', '派送'), 'OR');
        $count = $orders->where(array('UserId' => $usersinfo['id'], 'State' => '派送中'))->count();
        if ($count > 0) {
            return_err('你有派送中订单未完成');
            Log::record('接单失败用户：' . $username . '接单id：' . $orderid);
        }
        $orderinfo = $orders->field('StartLat,StartLng,State,StartAddress,AcceptTime')->where(array('Id' => $orderid, 'Delete' => 0))->find();
        // Log::record($orderinfo);die();
        if ($orderinfo == false) {
            Log::record('派送员接单失败-订单不存在');
            return_err('请重试~');
        }
        $orders->startTrans();
        $cue_time = date('Y-m-d H:i:s', time());
        $save = array('StartLat' => $lat, 'StartLng' => $lng, 'State' => '派送中', 'StartAddress' => $address, 'AcceptTime' => $cue_time);
        $newtime = date('Y-m-d H:i:s');
        $data['RowId'] = $orderid;
        $data['TableName'] = 'orders';
        $data['Changed'] = '';
        $data['Kind'] = 3;
        $data['Created'] = $newtime;
        $data['EditBy'] = $usersinfo['name'];
        $data['Deleted'] = 0;
        $data['Before'] = json_encode(array('StartLat' => $orderinfo['startlat'], 'StartLng' => $orderinfo['startlat'], 'State' => $orderinfo['state'], 'StartAddress' => $orderinfo['StartAddress']), JSON_UNESCAPED_UNICODE);
        $data['After'] = json_encode($save, JSON_UNESCAPED_UNICODE);
        $add = orderhistory($data);
        if ($add == false) {
            $orders->rollback();
            return_err('订单或改派记录不存在');
        }
        $update = $orders->where($map)->save($save);
        if ($update == false) {
            $orders->rollback();
            return_err('订单或改派记录不存在');
        }
        $orders->commit();

        return_data('success');
    }

    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/Expresses/complete?id={id},
     *   tags={"Expresses"},
     *   summary="完成订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="lat",
     *     in="query",
     *     description="经度",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="lng",
     *     in="query",
     *     description="纬度",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     description="位置",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="successful operation",
     *     @SWG\Schema(type="string"),
     *     @SWG\Header(
     *       header="X-Rate-Limit",
     *       type="integer",
     *       format="int32",
     *       description="calls per hour allowed by the user"
     *     ),
     *     @SWG\Header(
     *       header="X-Expires-After",
     *       type="string",
     *       format="date-time",
     *       description="date in UTC when token expires"
     *     )
     *   ),
     *   @SWG\Response(response=400, description="Invalid username/password supplied")
     * )
     */
    public function complete()
    {
        $username = is_cookie();
        $datatrr = is_json();
        $orderid = $datatrr['orderId'];
        if (!$orderid > 0) {
            return_err('参数错误');
        }
        $lat = $datatrr['lat'];
        $lng = $datatrr['lng'];
        $address = $datatrr['address'];
        $lat = empty($lat) ? 0 : $lat;
        $lng = empty($lng) ? 0 : $lng;
        $jsonPatchDocument = $datatrr['jsonPatchDocument'];//接收jsonPatch里面数据
        $data1 = array();
        foreach ($jsonPatchDocument as $key => $val) {
            $pathArr = explode('/', $val['path']);
            if ($val['op'] == 'add') {
                $keyName = ucfirst($pathArr[1]);
                $data1[$keyName] = $val['value'];
            } elseif ($val['op'] == 'remove') {
                $keyName = ucfirst($pathArr[1]);
                $data1[$keyName] = $val['value'];
            } elseif ($val['op'] == 'replace') {
                $keyName = ucfirst($pathArr[1]);
                $data1[$keyName] = $val['value'];
            }
        }
        $data2 = array('EndLat' => $lat, 'EndLng' => $lng, 'EndAddress' => $address);
        $return_data = array_merge($data1, $data2);//整合数据
        $return_data['CompleteTime'] = date('Y-m-d H:i:s');

        Log::record(json_encode($return_data));
        $field = field($return_data);//数据转换成字符串
        $orders = M('orders');
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $orderinfo = $orders->field($field)->where(array('Id' => $orderid, 'Deleted' => 0))->find();
        if ($orderinfo == false) {
            return_err('订单不存在');
        }
        $orders->startTrans();
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
            $orders->rollback();
            return_err('订单或改派记录不存在');
        }
        $map['UserId'] = $usersinfo['id'];
        $map['Id'] = $orderid;
        $update = $orders->where($map)->save($return_data);

        if ($update == false) {
            $orders->rollback();
            return_err('订单或改派记录不存在');
        }
        $orders->commit();
        return_data('success');
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Expresses/expressing",
     *   tags={"Expresses"},
     *   summary="获取当前派送中的订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Response(
     *     response=200,
     *     description="successful operation",
     *     @SWG\Schema(type="string"),
     *     @SWG\Header(
     *       header="X-Rate-Limit",
     *       type="integer",
     *       format="int32",
     *       description="calls per hour allowed by the user"
     *     ),
     *     @SWG\Header(
     *       header="X-Expires-After",
     *       type="string",
     *       format="date-time",
     *       description="date in UTC when token expires"
     *     )
     *   ),
     *   @SWG\Response(response=400, description="Invalid username/password supplied")
     * )
     */
    public function expressing()
    {
        $username = is_cookie();
        $orders = M('orders');
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $map['UserId'] = $usersinfo['id'];
        $map['State'] = '派送中';
        $ordersinfo = $orders->where($map)->find();
        if ($ordersinfo == false) {
            return_err('用户不存在派送中订单');
        }
        $data['order'] = $ordersinfo;
        return_data($data);
    }
}