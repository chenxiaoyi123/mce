<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/16
 * Time: 8:53
 */
namespace Home\Controller;

use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;

class ReassignController extends Controller{
    protected $reassignState = array('待确认','接受','拒绝','取消');

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Reassign/preAccept",
     *   tags={"Reassign"},
     *   summary="获取待接受改派",
     *   description="",
     *   operationId="",
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
    public function preAccept(){
        $username = is_cookie();
        $user = $this->checkUserState($username);

        $user_id = $user['id'];

        $sql = "select o.* from reassigns r LEFT JOIN orders o ON r.OrderId=o.Id and o.UserId=r.SourceUserId where o.State='改派中' and o.Deleted=0 and r.State=0 and r.TargetUserId=".$user_id;
        $orders = M()->query($sql);
        foreach ($orders as &$row){
            $row['user'] = $this->userWrap($row['userid']);
            $row['reassigns'] = null;
        }
        unset($row);

        return_data(array('orders'=>$orders));
    }
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Reassign/Reassigning",
     *   tags={"Reassign"},
     *   summary="获取改派订单",
     *   description="",
     *   operationId="",
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
    public function Reassigning(){
        $username = is_cookie();
        $user = $this->checkUserState($username);

        $user_id = $user['id'];

        $sql = "select r.* from reassigns r LEFT JOIN orders o on r.OrderId=o.Id and o.UserId=r.SourceUserId where o.State='改派中' and r.Deleted=0 and r.State=0 and r.SourceUserId=".$user_id;
        $reassigns = M()->query($sql);
        foreach ($reassigns as &$row){
            $row['orders'] = $this->orderWrap($row['orderid']);
            $row['sourceUser'] = null;
            $row['targetUser'] = $this->userWrap($row['targetuserid']);
        }
        unset($row);

        return_data(array('reassigns'=>$reassigns));
    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Reassign/accept",
     *   tags={"Reassign"},
     *   summary="接受派送",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="必传",
     *     description="订单id",
     *     required=true,
     *     type="int"
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
    public function accept(){
        $username = is_cookie();

        $orderId = Fun::requestInt('orderId', 0);
        if (empty($orderId)) return_err("参数错误：订单id为空");

        $user = $this->checkUserState($username);
        $user_id = $user['id'];

        $sql = "update reassigns r LEFT JOIN orders o ON o.UserId= r.SourceUserId and o.Id=r.OrderId SET r.State=1, o.UserId=r.TargetUserId, o.State='派送' where o.State='改派中' and r.State=0 and r.Deleted=0 and r.TargetUserId=".$user_id." and r.OrderId=".$orderId;
        $res = M()->execute($sql);

        if (empty($res)) return_err('接受失败');

        return_data('success');
    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Reassign/reject",
     *   tags={"Reassign"},
     *   summary="拒绝派送",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="必传",
     *     description="订单id",
     *     required=true,
     *     type="int"
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
    public function reject(){
        $username = is_cookie();

        $orderId = Fun::requestInt('orderId', 0);
        if (empty($orderId)) return_err("参数错误：订单id为空");

        $user = $this->checkUserState($username);
        $user_id = $user['id'];

        $sql = "update reassigns r LEFT JOIN orders o ON o.UserId= r.SourceUserId and o.Id=r.OrderId SET r.State=2, o.State='派送' where o.State='改派中' and r.State=0 and r.Deleted=0 and r.TargetUserId=".$user_id." and r.OrderId=".$orderId;
        $res = M()->execute($sql);

        if (empty($res)) return_err('拒绝失败');

        return_data('success');
    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Reassign/cancel",
     *   tags={"Reassign"},
     *   summary="取消派送",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="必传",
     *     description="订单id",
     *     required=true,
     *     type="int"
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
    public function cancel(){
        $username = is_cookie();

        $orderId = Fun::requestInt('orderId', 0);
        if (empty($orderId)) return_err("参数错误：订单id为空");

        $user = $this->checkUserState($username);
        $user_id = $user['id'];

        $sql = "update reassigns r LEFT JOIN orders o ON o.UserId= r.SourceUserId and o.Id=r.OrderId SET r.State=3, o.State='派送' where o.State='改派中' and r.State=0 and r.Deleted=0 and r.TargetUserId=".$user_id." and r.OrderId=".$orderId;
        $res = M()->execute($sql);

        if (empty($res)) return_err('取消失败');

        return_data('success');
    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Reassign/Reassign",
     *   tags={"Reassign"},
     *   summary="创建改派",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="必传",
     *     description="订单id",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="targetId",
     *     in="必传",
     *     description="目标派送员id",
     *     required=true,
     *     type="int"
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
    public function Reassign(){
        $username = is_cookie();

        $arr = is_json();
        $orderId = $arr['orderId'];
        $targetId = $arr['targetId'];
        if (empty($orderId)) return_err("参数错误：订单id为空");
        if (empty($targetId)) return_err("参数错误：改派用户id为空");

        $user = $this->checkUserState($username);
        $user_id = $user['id'];

        $OrdersModel = M('Orders');
        $ReassignsModel = M('Reassigns');
        $where = "state='派送' and UserId=".$user_id." and Id=".$orderId." and Deleted=0";
        $isExist = $OrdersModel->where($where)->find();
        if (empty($isExist)) return_err("订单或目标派送员不存在");

        M()->startTrans();

        try{
            //插入数据
            $data = array();
            $data['OrderId'] = $orderId;
            $data['SourceUserId'] = $user_id;
            $data['TargetUserId'] = $targetId;
            $data['Deleted'] = 0;
            $data['State'] = 0;
            $res = $ReassignsModel->add($data);
            //更新orders
            $params = array();
            $params['State'] = '改派中';
            $ret = $OrdersModel->where("Id=".$orderId." and Deleted=0")->save($params);
            if ($res && $ret){
                M()->commit();
                return_data('success');
            }else{
                M()->rollback();
                return_err("改派失败");
            }
        }catch (\Exception $e){
            return_err($e->getMessage());
        }

    }


    public function checkUserState($username){
        $UsersModel = M('Users');
        $where = "Deleted=0 and UserName='".$username."'";
        $user = $UsersModel->where($where)->find();
        if (empty($user)) return_err("用户已被禁用");
        return $user;
    }

    public function orderWrap($order_id){
        $where = "Id=".$order_id." and Deleted=0";
        $OrdersModel = M('Orders');
        $orders = $OrdersModel->where($where)->find();
        $orders['reassigns'] = null;
        return $orders;
    }

    public function userWrap($user_id){
        $UsersModel = M('Users');
        $user = $UsersModel->where("id=".$user_id." and deleted=0")->find();
        $user['id'] = Method::getInt($user['id']);
        $user['deleted'] = Method::getInt($user['deleted']);
        $user['userName'] = $user['username'];
        $user['resetTime'] = $user['resettime'];
        $user['pointId'] = !empty($user['pointid']) ? Method::getInt($user['pointid']) : null;
        $user['lat'] = Method::zeroToNull($user['lat']);
        $user['lng'] = Method::zeroToNull($user['lng']);
        $user['review'] = !empty($user['review']) ? true : false;
        $user['point'] = null;
        $user['userRoles'] = null;
        $user['userBusinesses'] = null;
        $user['userPoints'] = null;
        $user['orders'] = null;
        $user['callHistories'] = null;
        $user['geoLocations'] = null;
        $user['sourceReassigns'] = null;
        $user['targetReassigns'] = null;
        unset($user['username'],$user['resettime'],$user['pointid']);
        return $user;
    }
}