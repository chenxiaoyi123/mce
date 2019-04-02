<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/7
 * Time: 11:45
 */

namespace Home\Controller;

use Common\Core\Method;
use Think\Controller;
use Think\Log;
use Common\Core\Excel;

Vendor('PHPExcel.PHPExcel', '', '.php');

class CallHistoriesController extends Controller
{
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/CallHistories/obtainAll",
     *   tags={"CallHistories"},
     *   summary="分页获取通话记录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="PageIndex",
     *     in="必传",
     *     description="第几页",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="PageSize",
     *     in="必传",
     *     description="显示多少条",
     *     required=true,
     *     type="string"
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
    public function obtainAll()
    {
        is_cookie();//验证是否登录
        $PageIndex = I('PageIndex');//第几页
        $PageSize = I('PageSize');//数据条数
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageIndex;
        $limit = $offset . ", " . $PageSize;
        $callhistories = M('callhistories');
        $map['Delete'] = 0;
        $map['Name'] = array('NEQ', 'Robot');
        $map['RecordFile'] = array('NEQ', 'NULL');
        $list = $callhistories->where($map)->order('Id desc')->limit($limit)->select();
        $total = $callhistories->where($map)->count();
        if ($PageIndex > 1 && count($list) > 0) {
            $hasPreviousPage = true;
        } else {
            $hasPreviousPage = false;
        }
        if (($total / $PageSize) > $PageIndex) {
            $hasNextPage = true;
        } else {
            $hasNextPage = false;
        }
        $return_data['callHistories'] = array(
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
     * @SWG\Post(path="/orderschedule/index.php/Home/CallHistories/establish",
     *   tags={"CallHistories"},
     *   summary="新增通话记录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="userId",
     *     in="必传",
     *     description="用户id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="name",
     *     in="必传",
     *     description="显示多少条",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="必传",
     *     description="订单id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="phone",
     *     in="必传",
     *     description="手机号",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="recordFile",
     *     in="必传",
     *     description="录音文件路径",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="callState",
     *     in="必传",
     *     description="通话状态",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="reason",
     *     in="非必传",
     *     description="未接通原因",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="startTime",
     *     in="必传",
     *     description="startTime",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="endTime",
     *     in="必传",
     *     description="endTime",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="robotName",
     *     in="非必传",
     *     description="机器人编号",
     *     required=true,
     *     type="string"
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
    public function establish()
    {
        //is_cookie();//验证是否登录
        $datatrr = is_json();
        $users = M('users');
        $username = $datatrr['loginName'];
        $usermap['UserName'] = $username;
        $usermap['Password'] = $datatrr['password'];
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户密码错误');
        }
        $orderid = $datatrr['orderId'];
        $name = $datatrr['name'];
        $userId = $datatrr['userId'];
        $phone = $datatrr['phone'];
        $recordFile = $datatrr['recordFile'];
        $callState = $datatrr['callState'];
        $reason = $datatrr['reason'];
        $startTime = $datatrr['startTime'];
        $endTime = $datatrr['endTime'];
        $robotName = $datatrr['robotName'];
        $name = str_replace(array("\r\n","\r","\r"), "", $name);
        if (empty($name) || empty($orderid) || empty($userId) || empty($phone) || empty($recordFile) || empty($callState) || empty($startTime) || empty($endTime)) {
            return_err('参数错误');
            Log::record('错误数据' . json_encode($datatrr));
        }
        $arr = explode('/', $recordFile);
        Log::record('通话记录：'.json_encode($datatrr));
        $count = count($arr)-1;
        $recordFile = $arr[$count];
        $orders = M('orders');
        $ordermap['Id'] = $orderid;
        $ordersinfo = $orders->field('BusinessId,LastCallTime,LastCallState')->where($ordermap)->find();
        if ($ordersinfo == false) {
            return_err('订单不存在');
            Log::record('不存在订单id：' . $orderid);
        }
        $orderinfo = array(
            'LastCallTime' => $ordersinfo['lastcalltime'],
            'LastCallState' => $ordersinfo['lastcallstate']
        );
        $return_data = array(
            'LastCallTime' => $startTime,
            'LastCallState' => $callState
        );
        $add = order_history($orderid, $usersinfo, $orderinfo, $return_data);
        if ($add == false) {
            $orders->rollback();
            return_err('订单或改派记录不存在');
        }
        $order_update = $orders->where($ordermap)->save(array('LastCallTime' => $startTime, 'LastCallState' => $callState));
        if ($order_update == false) {
            return_err('订单更改失败');
        }
        $callhistories = M('callhistories');
        $data['OrderId'] = $orderid;
        $data['Phone'] = $phone;
        $data['RecordFile'] = $recordFile;
        $data['CallState'] = $callState;
        $data['StartTime'] = $startTime;
        $data['EndTime'] = $endTime;
        $data['RobotName'] = $robotName;
        $data['Reason'] = $reason;
        $data['UserId'] = $userId;
        $data['Name'] = $name;
        $data['BusinessId'] = $ordersinfo['businessid'];
        $add = $callhistories->add($data);
        if ($add == false) {
            return_err('插入通话记录失败');
        }
        return_data('success');
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/CallHistories/obtainOne",
     *   tags={"CallHistories"},
     *   summary="获取单个通话记录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="Id",
     *     in="必传",
     *     description="通话记录ID",
     *     required=true,
     *     type="string"
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
    public function obtainOne()
    {
        is_cookie();//验证是否登录
        $id = I('id');
        if (!$id > 0) {
            return_err('参数错误');
        }
        $map['Id'] = $id;
        $map['Delete'] = 0;
        $map['Name'] = array('NEQ', 'Robot');
        $map['RecordFile'] = array('NEQ', 'NULL');
        $callhistories = M('callhistories');
        $info = $callhistories->where($map)->find();
        if ($info == false) {
            return_err('通话记录不存在');
            Log::record('通话记录不存在：id' . $id);
        }
        return_data($info);
    }

    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/CallHistories/delete",
     *   tags={"CallHistories"},
     *   summary="删除通话记录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="业务id",
     *     required=true,
     *     type="string"
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
    public function delete()
    {
        is_cookie();//验证是否登录
        $id = I('id');
        if (!$id > 0) {
            return_err('参数错误');
        }
        $callhistories = M('callhistories');
        $map['Id'] = $id;
        $data['Deleted'] = $id;
        $delete = $callhistories->where($map)->save($data);
        if ($delete == false) {
            return_err('删除失败，请重试');
        }
        return_data('success');
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/CallHistories/search",
     *   tags={"CallHistories"},
     *   summary="搜索",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="通话记录",
     *     description="通话记录id",
     *     required=true,
     *     type="string"
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
    public function search()
    {
        //   is_cookie();//验证是否登录
        $datatrr = is_json();
        $PageIndex = $datatrr['pageIndex'];//第几页
        $PageSize = $datatrr['pageSize'];//数据条数
        $phone = $datatrr['phone'];
        $orderId = $datatrr['orderId'];
        $businessId = $datatrr['businessId'];
        $startTime = $datatrr['startTime'];
        $endTime = $datatrr['endTime'];
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $startTime = empty($startTime) ? '' : $startTime;
        $endTime = empty($endTime) ? '' : $endTime;
        $offset = ($PageIndex - 1) * $PageSize;
        $limit = $offset . ", " . $PageSize;
        $callhistories = M('callhistories');
        $map['callhistories.Deleted'] = 0;
        $map['Name'] = array('NEQ', 'Robot');
        $map['RecordFile'] = array('NEQ', 'NULL');
        if (!empty($orderId)){
            $map['orders.OrderId'] = array('eq', $orderId);
        }
        if ($endTime != '') {
            $map['EndTime'] = array('elt', $endTime);
        }
        if ($startTime != '') {
            $map['StartTime'] = array('egt', $startTime);
        }
        if ($businessId > 0) {
            $map['callhistories.BusinessId'] = $businessId;
        }
        if (!empty($phone)) {
            $map['Phone'] = array('like', "$phone%");
        }
        $list = $callhistories
            ->join('orders ON callhistories.OrderId=orders.Id')
            ->where($map)->order('callhistories.Id desc')->limit($limit)->select();
        Log::record('----callhistory search----');
        Log::record($callhistories
            ->fetchSql(true)
            ->join('orders ON callhistories.OrderId=orders.Id')
            ->where($map)->order('callhistories.Id desc')->limit($limit)->select());
        foreach ($list as &$row) {
            $row['businessId'] = Method::getInt($row['businessid']);
            $row['orderId'] = $row['orderid'];
            $row['callState'] = $row['callstate'];
            $row['startTime'] = $row['starttime'];
            $row['endTime'] = $row['endtime'];
            $row['recordFile'] = $row['recordfile'];
            $row['userId'] = Method::getInt($row['userid']);
            $row['robotName'] = $row['robotname'];
            $row['duration'] = strtotime($row['endtime']) - strtotime($row['starttime']);
            unset($row['businessid'], $row['orderid'], $row['callstate'], $row['starttime'], $row['endtime'], $row['userid'], $row['recordfile'], $row['robotname']);
        }
        unset($row);
        $total = $callhistories
            ->join('orders ON callhistories.OrderId=orders.Id')
            ->where($map)->count();
        if ($PageIndex > 1 && count($list) > 0) {
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
     * @SWG\Get(path="/orderschedule/index.php/Home/CallHistories/export",
     *   tags={"CallHistories"},
     *   summary="导出通话记录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="Phone",
     *     in="",
     *     description="手机号",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="BusinessId",
     *     in="",
     *     description="业务id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="StartTime",
     *     in="",
     *     description="开始时间",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="EndTime",
     *     in="",
     *     description="结束时间",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="PageIndex",
     *     in="",
     *     description="第几页",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="PageSize",
     *     in="",
     *     description="几条数据",
     *     required=true,
     *     type="string"
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
    function export()
    {//导出Excel
        $phone = I('phone');
        $businessId = I('businessId');
        $startTime = I('startTime');
        $endTime = I('endTime');
        $callhistories = M('callhistories');
        $map['callhistories.Deleted'] = 0;
        $map['callhistories.Name'] = array('NEQ', 'Robot');
        $map['callhistories.RecordFile'] = array('NEQ', 'NULL');
        if ($endTime != '') {
            $map['callhistories.EndTime'] = array('elt', $endTime);
        }
        if ($startTime != '') {
            $map['callhistories.StartTime'] = array('egt', $startTime);
        }
        if ($businessId > 0) {
            $map['callhistories.BusinessId'] = $businessId;
        }
        if (!empty($phone)) {
            $map['callhistories.Phone'] = array('like', "$phone%");
        }
        $count = $callhistories
            ->join('left join orders on callhistories.orderid=orders.Id')
            ->where($map)->count();
        $i = 0;
        $limit = 1000;
        $ppp = ceil($count / $limit);
        $pp = range(1, $ppp);
        foreach ($pp as $kkk => $vvv) {
            $offset = ($vvv - 1) * $limit;
            $res[$kkk] = $callhistories
                ->join('left join orders on callhistories.orderid=orders.Id')
                ->field('callhistories.*,orders.orderid as order_no')
                ->where($map)->limit($offset, $limit)->order('callhistories.Id desc')->select();
            $str[$kkk] = "订单编号,号码,文件名,通话状态,开始通话时间,通话时长,结束通话时间,机器人,未接通原因,订单号,姓名";
            $exl11[$kkk] = explode(',', $str[$kkk]);
            foreach ($res[$kkk] as $k => &$v) {
                $v['sumtime'] = strtotime($v['endtime']) - strtotime($v['starttime']);
                $exl[$kkk][] = array(
                    $v['orderid'], $v['phone'], $v['recordfile'], $v['callstate'], $v['starttime'], $v['sumtime'], $v['endtime'], $v['robotname'], $v['reason'], $v['order_no'], $v['name']);
            }
            unset($v);
            Method::exportToExcel('CallHistories_' . time() . $vvv . '.csv', $exl11[$kkk], $exl[$kkk], $i);
            $i++;
        }
    }
}