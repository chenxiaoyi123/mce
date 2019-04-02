<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/12
 * Time: 14:44
 */

namespace Home\Controller;

use Common\Core\Excel;
use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;
use Think\Log;

Vendor('PHPExcel.PHPExcel', '', '.php');
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Credentials:true");
header('Access-Control-Allow-Origin:*');

class OrdersController extends Controller
{

    public function getOrdersLngAndLatList(){
        set_time_limit(0);
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";

        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $datatrr = is_json();
        $map = where($datatrr);
        $orders = M('orders');
        $list = $orders
            ->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
            ->field('orders.Id,orders.Lat,orders.Lng,orders.Address')
            ->where($map)->limit(0,100)->select();

        foreach ($list as $key => &$val){
            if (empty($val['lat']) || empty($val['lng'])){
                list($val['lng'], $val['lat']) = $this->getLatAndLng($val['address']);
            }
            $val['id'] = Method::getInt($val['id']);
            unset($val['address']);
        }
        unset($val);
        return_data($list);

    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/establish",
     *   tags={"Orders"},
     *   summary="创建新订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="json",
     *     in="query",
     *     description="订单json数据",
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
        $data = is_json();
        $username = $data['loginName'];
        if (empty($username)){
            $username=is_cookie();
        }
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $params = array();
        $orders = M('orders');
        $orderId = $data['orderId'];
        $isExist = $orders->where("OrderId='".$orderId."'")->find();
        if ($isExist) return_err('此订单编号已存在');
        $importsequences = M('importsequences');
        $time = time();
        $str = create_password();

        $importdata['Guid'] = strtoupper(md5($time . '_' . $str));
        $importdata['ImportTime'] = date('Y-m-d H:i:s');
        $importdata['Name'] = '手动导入';
        $importdata['Count'] = 1;
        $importdata['BusinessId'] = $data['businessId'];
        $importadd = $importsequences->add($importdata);
        if ($importadd == false) {
            return_err('创建订单流水失败');
            Log::record('创建订单流水：' . json_encode($importdata));
        }

        //首字母大写转换
        foreach ($data as $key => $val) {
            $uc_key = ucfirst($key);
            $params[$uc_key] = $val;
        }

        $params['ImportSequenceId'] = $importadd;
        $add = $orders->add($params);
        if ($add == false) {
            return_err('订单创建失败');
            Log::record($username.'创建订单失败：' . json_encode($params));
        }

        Log::record('-----import order------');
        Log::record($username."创建订单成功");
        return_data('success');
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/ordersOne?id={id}",
     *   tags={"Orders"},
     *   summary="获取单个订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     description="订单id",
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
    public function ordersOne()
    {
        $username = is_cookie();
        $id = I('id');
        $orders = M('orders');
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $map['Deleted'] = 0;
        $map['Id'] = $id;
        $orderinfo = $orders->where($map)->select();
        foreach ($orderinfo as $value => &$k) {
            $k['id'] = Method::getInt($k['id']);
            $k['businessId'] = Method::getInt($k['businessid']);
            $k['orderId'] = $k['orderid'];
            $k['pointId'] = $k['pointid'] == 0 ? Method::zeroToNull($k['pointid']) : Method::getInt($k['pointid']);
            $k['userId'] = $k['userid'] == 0 ? Method::zeroToNull($k['userid']) : Method::getInt($k['userid']);
            $k['orderTime'] = $k['ordertime'];
            $k['planName'] = $k['planname'];
            $k['orderPhone'] = $k['orderphone'];
            $k['lastCallState'] = $k['lastcallstate'];
            $k['visitTime'] = $k['visittime'] == '0000-00-00 00:00:00' ? '' : $k['visittime'];
            $k['nextCallTime'] = $k['nextcalltime'] == '0000-00-00 00:00:00' ? '' : $k['nextcalltime'];
            $k['scheduleRemark'] = $k['scheduleremark'];
            $k['user'] = $this->getUser($k['userid']);
            $k['business'] = $this->getBusiness($k['businessid']);
            $k['point'] = $this->getPoints($k['pointid']);
            $k['importSequence'] = $this->getImportSequence($k['importsequenceid']);
            unset($k['orderid'], $k['pointid'], $k['ordertime'], $k['planname'], $k['orderphone'], $k['businessid']);
            $return_data['order'] = $k;
        }
        unset($k);
        return_data($return_data);
    }

    private function getUser($uid)
    {
        $users = M('users');
        $array = array();
        $map['Id'] = $uid;
        $userinfo = $users->where($map)->find();
        if ($userinfo == false) {
            return $array;
        }
        return $userinfo;
    }

    private function getBusiness($bid)
    {
        $businesses = M('businesses');
        $array = array();
        $map['Id'] = $bid;
        $businessesinfo = $businesses->where($map)->find();
        if ($businessesinfo == false) {
            return $array;
        }
        return $businessesinfo;
    }

    private function getPoints($pid)
    {
        $points = M('points');
        $array = array();
        $map['Id'] = $pid;
        $pointsinfo = $points->where($map)->find();
        if ($pointsinfo == false) {
            return $array;
        }
        return $pointsinfo;
    }

    private function getImportSequence($iod)
    {
        $importsequences = M('importsequences');
        $array = array();
        $map['Id'] = $iod;
        $map['Deleted'] = 0;
        $importsequencesinfo = $importsequences->where($map)->find();
        $importsequencesinfo['importTime'] = $importsequencesinfo['importtime'];
        if ($importsequencesinfo == false) {
            return $array;
        }
        return $importsequencesinfo;
    }


    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/Orders/delete?id={id}",
     *   tags={"Orders"},
     *   summary="删除订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     description="订单id",
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
        $username = is_cookie();
        $data = is_json();
        $id = $data['orderId'];
        if (!$id > 0) {
            return_err('参数错误');
        }
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $orders = M('orders');
        $orderhistory = M('orderhistory');
        $ordersinfo = $orders->field('Id')->where(array('Id' => $id, 'Deleted' => 0))->find();
        if ($ordersinfo == false) {
            return_err('订单不存在');
        }
        $ordermap['RowId'] = $id;
        $ordermap['TableName'] = 'orders';
        $ordermap['Changed'] = '';
        $ordermap['Kind'] = 3;
        $ordermap['Created'] = date('Y-m-d H:i:s');
        $ordermap['EditBy'] = $usersinfo['name'];
        $ordermap['Before'] = json_encode(array('Deleted' => 0), JSON_UNESCAPED_UNICODE);
        $ordermap['After'] = json_encode(array('Deleted' => $id), JSON_UNESCAPED_UNICODE);
        $add = $orderhistory->add($ordermap);
        if ($add == false) {
            return_err('订单或改派记录不存在');
        }
        $map['Id'] = $id;
        $data['Deleted'] = $id;
        $delete = $orders->where($map)->save($data);
        if ($delete == false) {
            return_err('删除失败，请重试');
        }
        return_data('success');
    }

    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/Orders/update?id={id}",
     *   tags={"Orders"},
     *   summary="更新订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     description="id",
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
    public function update()
    {
        $value = cookie('username');
        if (empty($value)) {
            $username = "bossManage";
        } else {
            $username = $value;
        }
        $orderid = I('get.id');
        $datatrr = is_json();
        $jsonPatchDocument = $datatrr['jsonPatchDocument'];
        $return_data = json_patch($jsonPatchDocument);
        $State = isset($return_data['State']) ? $return_data['State'] : '';
        $return_data['CompleteTime'] = date('Y-m-d H:i:s');
       if ($State == '派送') {
                $return_data['AssignTime'] = date('Y-m-d H:i:s');
                $this->pachong($orderid,$return_data['UserId']);
        }
        $field = field($return_data);//数据转换成字符串
        $orderid = isset($orderid) ? $orderid : 0;
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
        $add = order_history($orderid, $usersinfo, $orderinfo, $return_data);
        if ($add == false) {
            $orders->rollback();
            return_err('订单或改派记录不存在');
        }
        //    $map['UserId'] = $usersinfo['id'];
        $map['Id'] = $orderid;
        $update = $orders->where($map)->save($return_data);
        if ($update == false) {
            $orders->rollback();
            return_err('订单内容未发生改变');
        }
        $orders->commit();
        return_data('success');
    }
   private function pachong($orderid,$userid)
    {
        $order=M('orders');
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
        $users=M('expresscontrasts');
        $usermap['Deleted']=0;
        $usermap['UserId']=$userid;
        $usersinfo=$users->field('Delivery')->where($usermap)->find();
        if ($usersinfo==false){
            Log::record('插入爬虫数据失败，未查询到派送员');
            return false;
        }
        $data=array(
            'ORDER_ID'=>$orderinfo['orderid'],
            'PHONE'=>$usersinfo['delivery'],
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
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/callStatus",
     *   tags={"Orders"},
     *   summary="获取通话情况",
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
    public function callStatus()
    {
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $orders = M('orders');
        $map['Deleted'] = 0;
        $map['State'] = array(array('EQ', '繁忙另约'), array('EQ', '未分配'), 'OR');
        $orderslist = $orders->field('state,lastcallstate,notconnectcount')->where(array($map))->select();
        $total = 0;
        $seq = 0;
        $busy = 0;
        $notConnect = 0;
        $three = 0;
        foreach ($orderslist as $value => &$k) {
            if ($k['state'] == '繁忙另约' || $k['state'] == '未分配') {
                $total++;
            }
            if ($k['lastcallstate'] == '已提取') {
                $seq++;
            }
            if ($k['state'] == '繁忙另约') {
                $busy++;
            }
            if ($k['lastcallstate'] == '未接通') {
                $notConnect++;
            }
            if ($k['notconnectcount'] >= 3) {
                $three++;
            }
        }
        $return_data = array(
            'total' => $total,
            'seq' => $seq,
            'busy' => $busy,
            'notConnect' => $notConnect,
            'three' => $three,
        );
        return_data($return_data);
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/rollback",
     *   tags={"Orders"},
     *   summary="未接通回滚提取",
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
    public function rollback()
    {
        $username = is_cookie();
        $users = M('users');
        $userroles = M('userroles');
        $map['UserName'] = "$username";
        $map['Deleted'] = 0;
        $userinfo = $users->field('id,name')->where($map)->find();
        if ($userinfo == false) {
            return_err('用户不存在或被禁用');
        }
        $rolemap['userroles.UserId'] = $userinfo['id'];
        $rolesinfo = $userroles->join('RIGHT JOIN roles ON userroles.RoleId = roles.Id')->field('roles.rolename')->where($rolemap)->find();
        if ($rolesinfo == false) {
            return_err('用户没有权限');
        }
        if ($rolesinfo['rolename'] == 'rollback' || $username == 'admin') {
            $rollbackhistory = M('rollbackhistory');
            $sava = array('rollback' => 1, 'username' => $userinfo['name'], 'addtime' => date('Y-m-d H:i:s'));
            $update = $rollbackhistory->where('id=1')->save($sava);
            if ($update == false) {
                return_err('回滚失败，请重试');
                Log::record('rollback回滚失败:' . json_encode($sava));
            } else {
                return_data('success');
            }

        } else {
            return_err('用户没有权限');
        }
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/connectedRollback",
     *   tags={"Orders"},
     *   summary="已接通回滚提取",
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
    public function connectedRollback()
    {
        $username = is_cookie();
        $users = M('users');
        $userroles = M('userroles');
        $map['UserName'] = "$username";
        $map['Deleted'] = 0;
        $userinfo = $users->field('id,name')->where($map)->find();
        if ($userinfo == false) {
            return_err('用户不存在或被禁用');
        }
        $rolemap['userroles.UserId'] = $userinfo['id'];
        $rolesinfo = $userroles->join('RIGHT JOIN roles ON userroles.RoleId = roles.Id')->field('roles.rolename')->where($rolemap)->find();
        if ($rolesinfo == false) {
            return_err('用户没有权限');
        }
        if ($rolesinfo['rolename'] == 'rollback' || $username == 'admin') {
            $rollbackhistory = M('rollbackhistory');
            $sava = array('connectedRollback' => 1, 'username' => $userinfo['name'], 'addtime' => date('Y-m-d H:i:s'));
            $update = $rollbackhistory->where('id=1')->save($sava);
            if ($update == false) {
                return_err('回滚失败，请重试');
                Log::record('connectedRollback回滚失败:' . json_encode($sava));
            } else {
                return_data('success');
            }

        } else {
            return_err('用户没有权限');
        }
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/search",
     *   tags={"Orders"},
     *   summary="搜索订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="json",
     *     in="query",
     *     description="json数据",
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
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $datatrr = is_json();
        //$tt =implode(",", $datatrr['state']);
      //  var_dump($datatrr['state']);
       // Log::record($tt);
        $PageIndex = $datatrr['pageIndex'];
        $PageSize = $datatrr['pageSize'];
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;
        $limit = $offset . ", " . $PageSize;
//        unset($item);
       // Log::record('sasda'.json_encode($datatrr));
        $map = where($datatrr);
        $orders = M('orders');
        $orderslist = $orders
            ->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
            ->field('orders.*')
            ->where($map)->limit($limit)->select();
        foreach ($orderslist as $value => &$item) {
            $item['id'] = Method::getInt($item['id']);
            $item['orderId'] = $item['orderid'];
            $item['planName'] = $item['planname'];
            $item['orderPhone'] = $item['orderphone'];
             $item['acceptTime'] = $item['accepttime'];
            $item['orderTime'] = $item['ordertime'];
            $item['orderType'] = $item['ordertype'];
            $item['lastCallState'] = $item['lastcallstate'];
            $item['notConnectCount'] = $item['notconnectcount'];
            $item['lastCallTime'] = $item['lastcalltime'];
            $item['lastCallUser'] = $this->getLastCallname($item['id']);
            $item['nextCallTime'] = $item['nextcalltime'];
            $item['completeTime'] = $item['completetime'];
            $item['visitTime'] = $item['visittime'];
            $item['importSequenceId'] = Method::zeroToNull($item['importsequenceid']);
            $item['scheduleRemark'] = $item['scheduleremark'];
            $item['expressRemark'] = $item['expressremark'];
            $item['user'] = $this->getUser($item['userid']);
            $item['business'] = $this->getBusiness($item['businessid']);
            $item['importSequence'] = $this->getImportSequence($item['importsequenceid']);
            $item['importTime'] = $item['importSequence']['importTime'];
            $item['point'] = $this->getPoints($item['pointid']);

            unset($item['orderid'], $item['planname'], $item['orderphone'], $item['ordertime'], $item['ordertype'], $item['lastcallstate'], $item['notconnectcount'], $item['lastcalltime'], $item['nextcalltime'], $item['visittime'], $item['importsequenceid'], $item['scheduleremark']);
        }
        unset($item);
        $total = $orders
            ->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
            ->where($map)->count();
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
            'entities' => $orderslist,
            'total' => intval($total),
            'pageIndex' => $PageIndex,
            'pageSize' => $PageSize,
            'hasPreviousPage' => $hasPreviousPage,
            'hasNextPage' => $hasNextPage,
        );
        return_data($return_data);
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/searchByCall",
     *   tags={"Orders"},
     *   summary="根据通话搜索订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="json",
     *     in="query",
     *     description="json数据",
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
    public function searchByCall()
    {
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $datatrr = is_json();
        $name = $datatrr['name'];
        $startTime = $datatrr['startTime'];
        $endTime = $datatrr['endTime'];
        $PageIndex = $datatrr['pageIndex'];
        $PageSize = $datatrr['pageSize'];
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;
        $limit = $offset . ", " . $PageSize;
        $orders = M('orders');
        $map['Deleted'] = 0;
        if (!empty($startTime)) {
            $map['LastCallTime'][] = array('EGT', $startTime);
        }
        if (!empty($endTime)) {
            $map['LastCallTime'][] = array('ELT', $endTime);
        }
        if (!empty($name)) {
            $callhistories = M('callhistories');
            $str = '';
            $callhistoriesmap['Deleted'] = 0;
            $callhistoriesmap['Name'] = "$name";
            $callhistorieslist = $callhistories->field('OrderId')->where($callhistoriesmap)->select();
            foreach ($callhistorieslist as $value => &$item) {
                $str .= empty($str) ? $item['orderid'] : ',' . $item['orderid'];
            }
            unset($item);
            if (!empty($str)) {
                $map['Id'] = array('in', $str);
            }
        }
        $orderslist = $orders->where($map)->limit($limit)->select();
        foreach ($orderslist as $value => &$item) {
            $item['orderId'] = $item['orderid'];
            $item['planName'] = $item['planname'];
            $item['orderPhone'] = $item['orderphone'];
            $item['orderTime'] = $item['ordertime'];
            $item['orderType'] = $item['ordertype'];
            $item['lastCallState'] = $item['lastcallstate'];
            $item['notConnectCount'] = $item['notconnectcount'];
            $item['lastCallTime'] = $item['lastcalltime'];
            $item['nextCallTime'] = $item['nextcalltime'];
            $item['visitTime'] = $item['visittime'];
            $item['importSequenceId'] = $item['importsequenceid'];
            $item['scheduleRemark'] = $item['scheduleremark'];
            $item['user'] = $this->getUser($item['userid']);
            $item['business'] = $this->getBusiness($item['businessid']);
            $item['importSequence'] = $this->getImportSequence($item['importsequenceid']);
            $item['importTime'] = $item['importSequence']['importTime'];
            $item['point'] = $this->getPoints($item['pointid']);

            unset($item['orderid'], $item['planname'], $item['orderphone'], $item['ordertime'], $item['ordertype'], $item['lastcallstate'], $item['notconnectcount'], $item['lastcalltime'], $item['nextcalltime'], $item['visittime'], $item['importsequenceid'], $item['scheduleremark']);

        }
        unset($item);
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
            'entities' => $orderslist,
            'total' => intval($total),
            'pageIndex' => $PageIndex,
            'pageSize' => $PageSize,
            'hasPreviousPage' => $hasPreviousPage,
            'hasNextPage' => $hasNextPage,
        );
        return_data($return_data);
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/upload",
     *   tags={"Orders"},
     *   summary="上传 Excel",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="BusinessId",
     *     in="query",
     *     description="BusinessId",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="Block",
     *     in="query",
     *     description="Block",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="Distinct",
     *     in="query",
     *     description="Distinct",
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
    public function upload()
    {
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }

        $file = $_FILES['file'];
        $filename = $file['tmp_name'];
        $upload_dir = 'Uploads/' . date('Y-m-d');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir);
        }
        $upload_path = $upload_dir . '/' . iconv("UTF-8", "gbk", $file['name']);

        if (move_uploaded_file($filename, $upload_path)) {
            $exts = array_pop(explode('.', $upload_path));
            $data = data_import($upload_path, $exts);
            if ($data) {
                $datatrr = array(
                    'BusinessId' => I('businessId'),
                    'Block' => I('block'),
                    'Distinct' => I('distinct')
                );
                $this->save_import($data, $datatrr, $usersinfo['name']);
            } else {
                return_err('文件转换错误');
            }
        } else {
            return_err('上传文件错误');
        }

    }

//保存导入数据
    private function save_import($data, $datatrr, $name)
    {
        $orders = M('orders');
        $BusinessId = $datatrr['BusinessId'];
        $Block = $datatrr['Block'];//暂时没有判断
        $Distinct = $datatrr['Distinct'];//暂时没有做
        foreach ($data as $k => $v) {
            if (!empty($v['A'])) {
                $info['OrderId'] = $v['A'];
                $info['PlanName'] = $v['B'];
                $info['OrderPhone'] = $v['C'];
                $info['OrderTime'] = $v['D'];
                $info['Address'] = $v['E'];
                $info['Consignee'] = $v['F'];
                $info['Phone'] = $v['G'];
                $info['ScheduleRemark'] = $v['H'];
                $block = $v['I'] == '是' ? 0 : 1;
                $info['Block'] = $block;
                $info['District'] = $this->getAdname($info['Address']);
                $info['BusinessId'] = $BusinessId;
                $arr[] = $info;
            }
        }
        M()->startTrans();

        $importsequences = M('importsequences');
        $importdata['Guid'] = strtoupper(md5(time() . create_password()));
        $importdata['ImportTime'] = date('Y-m-d H:i:s');
        $importdata['Name'] = $name;
        $importdata['Count'] = count($arr);
        $importdata['BusinessId'] = $BusinessId;
        $importadd = $importsequences->add($importdata);
        if ($importadd == false) {
            M()->rollback();
            return_data('数据导入失败');
            Log::record('插入数据流水失败：' . json_encode($arr));
        }

        foreach ($arr as &$a) {
            $a['ImportSequenceId'] = $importadd;
        }
        unset($a);

        $result = $orders->addall($arr);
        if (!$result) {
            M()->rollback();
            return_err('数据导入失败');
        }

        M()->commit();

        return_data('数据导入成功');
    }

//获取地理位置所在的区名
    public function getAdname($keywords)
    {
        $key = "4a9acf4ec92ff9a5f4c99bb6006503b7";
        $Adname = '';
        if (empty($keywords)) {
            return $Adname;
        }
        $url = "https://restapi.amap.com/v3/place/text?key=$key&keywords=$keywords";
        $data_json = doGet($url);
        $data_trr = json_decode($data_json, true);
        for ($i = 0; $i < count($data_trr['pois']); $i++) {
            if (!empty($data_trr['pois'][$i]['adname'])) {
                $Adname = $data_trr['pois'][$i]['adname'];
                break;
            }
        }
        return $Adname;
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/assign",
     *   tags={"Orders"},
     *   summary="分配区域和人员",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="query",
     *     description="id",
     *     required=true,
     *     type="number"
     *   ),
     *   @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     description="phone",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="businessId",
     *     in="query",
     *     description="businessId",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     description="address",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="district",
     *     in="query",
     *     description="district",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="visitTime",
     *     in="query",
     *     description="visitTime",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="lat",
     *     in="query",
     *     description="lat",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="lng",
     *     in="query",
     *     description="lng",
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
    public function assign()
    {
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $datatrr = is_json();
        $id = $datatrr['id'];
        $phone = $datatrr['phone'];
        $businessId = $datatrr['businessId'];//userbusinesses
        $address = $datatrr['address'];
        $district = $datatrr['district'];
        $visitTime = $datatrr['visitTime'];
        $visitTime = date('Y-m-d', strtotime($visitTime));//worktime
        $lat = $datatrr['lat'];
        $lng = $datatrr['lng'];
        $points = M('points');
        $map['Deleted'] = 0;
        $map['District'] = $district;
        $orders = M('orders');
        $ordermap['Deleted'] = 0;
        $ordermap['Id'] = array('NEQ', $id);
        $ordermap['State'] = array(array('EQ', '派送'), array('EQ', '派送中'), 'OR');
        $ordermap['Address'] = array('EQ', $address);
        $ordermap['Phone'] = array('EQ', $phone);
        $ordersinfo = $orders->field('userid,pointid')->where($ordermap)->find();
        if ($ordersinfo) {
            if ($ordersinfo['userid'] > 0 && $ordersinfo['pointid'] > 0) {
                $users = find('users', array('Id' => $ordersinfo['userid'], 'Deleted' => 0));
                $point = find('points', array('Id' => $ordersinfo['pointid'], 'Deleted' => 0));
                $return_data = array(
                    'point' => $point,
                    'user' => $users
                );
                return_data($return_data);
            }
        }
        $pointslist = $points->where($map)->select();
        foreach ($pointslist as $value => &$item) {
            $item['id'] = Method::getInt($item['id']);
            $item['distance'] = intval(getDistance($lat, $lng, $item['lat'], $item['lng']) / 1000);
        }
        unset($item);
        $pointslist = array_sort($pointslist, 'distance');
        if (count($pointslist) > 0) {
            $pointsinfo = $pointslist[0];
            $userpoints = M('userpoints');
            $userpointsmap['userpoints.Deleted'] = 0;
            $userpointsmap['userpoints.PointId'] = $pointsinfo['id'];
            $userpointsmap['userpoints.WorkTime'] = $visitTime;
            $userpointsmap['userbusinesses.Deleted'] = 0;
            $userpointsmap['userbusinesses.BusinessId'] = $businessId;
            $userpointsinfo = $userpoints->join('left JOIN userbusinesses ON userpoints.UserId = userbusinesses.UserId')->field('userpoints.UserId')->where($userpointsmap)->select();
        }
        $users = array();
        if (count($userpointsinfo) >= 1) {
            $str = '';
            foreach ($userpointsinfo as $value => &$item) {
                $str .= empty($str) ? $item['userid'] : ',' . $item['userid'];
            }
            $ordersql = 'SELECT count(*) as count,UserId FROM `orders` where UserId in (' . $str . ') and Deleted=0 and left(VisitTime,10)=\'' . $visitTime . '\' GROUP BY UserId ORDER BY count ASC limit 1';
            $orderlist = M()->query($ordersql);
            if ($userpointsinfo) {
                $users = find('users', array('Id' => $orderlist[0]['userid'], 'Deleted' => 0));
            }
        } else {
            if ($userpointsinfo) {
                $users = find('users', array('Id' => $userpointsinfo['userid'], 'Deleted' => 0));
            }
        }
        $return_data = array(
            'point' => $pointslist[0],
            'user' => $users
        );
        return_data($return_data);
    }


    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/outcall",
     *   tags={"Orders"},
     *   summary="外呼系统提取电话号码",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="Num",
     *     in="query",
     *     description="Num",
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
    public function outcall()
    {
        $datatrr = is_json();
        $username = $datatrr['loginName'];
        $Num = $datatrr['Num'];
        $num = empty($Num) ? 10000 : $Num;
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $rollbackhistory = M('rollbackhistory');
        $rollbackhistoryinfo = $rollbackhistory->where('deleted=0')->find();
        if ($rollbackhistoryinfo) {
            if ($rollbackhistoryinfo['connectedrollback'] == 1) {
                $map['orders.Block'] = 0;
                $map['orders.LastCallState'] = '已接通';
                $map['orders.State'] = '未分配';
                $map['orders.NotConnectCount'] = array('lt', 3);
                $map['orders.UserId'] = array('exp', 'is null');
                $DateTime = date('Y-m-d H:i:s', strtotime("- 3 minute"));
                $map['orders.LastCallTime'] = array(array('exp', 'is null'), array('lt', $DateTime), 'OR');
            } else {
                if ($rollbackhistoryinfo['rollback'] == 1) {//未接通回滚
                    $map['orders.Block'] = 0;
                    $map['orders.LastCallState'] = '未接通';
                    $map['orders.State'] = '未分配';
                    $map['orders.NotConnectCount'] = array('lt', 3);
                } else {//没有回滚
                    $map['orders.Block'] = 0;
                    $map['orders.LastCallState'] = '未拨打';
                    $map['orders.State'] = '未分配';
                }
            }
            $NotConnectCount = 1;
        } else {
            $map['orders.Block'] = 0;
            $map['orders.LastCallState'] = '未拨打';
            $map['orders.State'] = '未分配';
            $NotConnectCount = 0;
        }
        $orders = M('orders');
        $orderslist = $orders->join('left join businesses ON orders.BusinessId = businesses.Id')->field('orders.LastCallState,orders.NotConnectCount,orders.id,orders.priority,orders.phone,orders.businessId,businesses.type')->where($map)->order('priority desc')->limit($num)->select();
        if ($orderslist == false) {
            return_err('没有可提取数据');
        }
        $orders->startTrans();
        $orderhistory = M('orderhistory');
        foreach ($orderslist as $value => &$item) {
            if ($NotConnectCount > 0) {
                $sava = array('LastCallState' => '已提取', 'NotConnectCount' => $item['notconnectcount'] + $NotConnectCount);
                $Before = array('LastCallState' => $item['lastcallstate'], 'NotConnectCount' => $item['notconnectcount']);
            } else {
                $sava = array('LastCallState' => '已提取');
                $Before = array('LastCallState' => $item['lastcallstate']);
            }
            $update = $orders->where(array('Id' => $item['id']))->save($sava);
            if ($update == false) {
                $orders->rollback();
                Log::record('更新状态失败：' . json_encode($sava));
                return_err('更新状态失败');
            }
            $data[] = array(
                'RowId' => $item['id'],
                'TableName' => 'orders',
                'Kind' => 3,
                'Created' => date("Y-m-d H:i:s"),
                'EditBy' => $usersinfo['name'],
                'Before' => json_encode($Before, JSON_UNESCAPED_UNICODE),
                'After' => json_encode($sava, JSON_UNESCAPED_UNICODE),
            );
            $return_data['orders'][] = array(
                "id" => $item['id'],
                "priority" => $item['priority'],
                "phone" => $item['phone'],
                "type" => $item['type'],
                "businessId" => $item['businessid']
            );
        }
        unset($item);
        $add = $orderhistory->addAll($data);
        if ($add == false) {
            $orders->rollback();
            return_err('订单记录添加失败');
        }
        $orders->commit();
        return_data($return_data);
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/OutCallRecover",
     *   tags={"Orders"},
     *   summary="外呼故障恢复时获得已提取的数据",
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
    public function OutCallRecover()
    {
        $data = is_json();
        $username = $data['loginName'];
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $map['orders.Block'] = 0;
        $map['orders.LastCallState'] = '已提取';
        $map['orders.State'] = '未分配';
        $orders = M('orders');
        $orderslist = $orders->join('RIGHT JOIN businesses ON orders.BusinessId = businesses.Id')->field('orders.LastCallState,orders.NotConnectCount,orders.id,orders.priority,orders.phone,orders.businessId,businesses.type')->where($map)->order('priority desc')->select();
        foreach ($orderslist as $value => &$item) {
            $return_data['orders'][] = array(
                "id" => $item['id'],
                "priority" => $item['priority'],
                "phone" => $item['phone'],
                "type" => $item['type'],
                "businessId" => $item['businessid']
            );
        }
        unset($item);
        return_data($return_data);
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/changeExpress",
     *   tags={"Orders"},
     *   summary="改派派送员所有未完成订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="sourceUserId",
     *     in="query",
     *     description="sourceUserId",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="targetUserId",
     *     in="query",
     *     description="targetUserId",
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
    public function changeExpress()
    {
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $datatrr = is_json();
        $sourceUserId = $datatrr['sourceUserId'];//变更之前用户ID
        $targetUserId = $datatrr['targetUserId'];//变更之后用户ID
        $map['State'] = array(array('eq', '派送'), array('eq', '改约'), 'OR');
        $map['Deleted'] = 0;
        $map['UserId'] = $sourceUserId;
        $orders = M('orders');
        $orderslist = $orders->field('id')->where($map)->select();
        foreach ($orderslist as $value => &$item) {
            $orderinfo = array('UserId' => $sourceUserId);
            $return_data = array('UserId' => $targetUserId);
            $add = order_history($item['id'], $usersinfo, $orderinfo, $return_data);
            if ($add) {
                $update = $orders->where('Id=' . $item['id'])->save(array('UserId' => $targetUserId));
                if ($update == false) {
                    Log::record('改派派送员所有未完成订单，更换失败oid:' . $item['id']);
                }
            }
        }
        unset($item);
        return_data('success');
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/batchChangeExpress",
     *   tags={"Orders"},
     *   summary="订单 ID 批量改派",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="orderIds",
     *     in="query",
     *     description="orderIds",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="targetUserId",
     *     in="query",
     *     description="targetUserId",
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
    public function batchChangeExpress()
    {
        $username = is_cookie();
        $users = M('users');
        $usermap['UserName'] = "$username";
        $usermap['Deleted'] = 0;
        $usersinfo = $users->field('id,name')->where($usermap)->find();
        if ($usersinfo == false) {
            return_err('用户已经被禁用');
        }
        $datatrr = is_json();
        $orderIds = $datatrr['orderIds'];
        $targetUserId = $datatrr['targetUserId'];
        $count = count($orderIds);
        $str = '';
        for ($i = 0; $i < $count; $i++) {
            $str .= empty($str) ? $orderIds[$i] : ',' . $orderIds[$i];
        }
        $map['Id'] = array('in', $str);
        $map['State'] = array(array('eq', '派送'), array('eq', '改约'), 'OR');
        $map['Deleted'] = 0;
        $orders = M('orders');
        $orderslist = $orders->field('id,userid')->where($map)->select();
        if (empty($orderslist) || $count > count($orderslist)) {
            return_err('订单状态必须是派送或者改约');
        }
        foreach ($orderslist as $value => &$item) {
            $orderinfo = array('UserId' => $item['userid']);
            $return_data = array('UserId' => $targetUserId);
            $add = order_history($item['id'], $usersinfo, $orderinfo, $return_data);
            if ($add) {
                $update = $orders->where('Id=' . $item['id'])->save(array('UserId' => $targetUserId));
                if ($update == false) {
                    Log::record('改派派送员所有未完成订单，更换失败oid:' . $item['id']);
                }
            }
        }
        unset($item);
        return_data('success');
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/recover",
     *   tags={"Orders"},
     *   summary="回收订单, 清除派送点和派送员信息",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="query",
     *     description="orderId",
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
    public function recover()
    {
        $username = is_cookie();
        $arr = is_json();
        $orderId = $arr['orderId'];

        if (empty($orderId)) return_err('参数错误：订单id为空');

        $UsersModel = M('Users');
        $OrdersModel = M('Orders');

        $userinfo = $UsersModel->where("Deleted=0 and UserName='" . $username . "'")->find();
        if (empty($userinfo)) return_err('用户不存在');
        $order_info = $OrdersModel->where("Deleted=0 and Id=" . $orderId)->find();
        if (empty($order_info)) return_err('订单不存在');

        M()->startTrans();
        try {
            $data = array();
            $data['UserId'] = null;
            $data['PointId'] = null;
            $data['State'] = '未分配';
            $OrdersModel->where("Deleted=0 and Id=" . $orderId)->save($data);  //更新订单

            $return_data = $OrdersModel->where("Deleted=0 and Id=" . $orderId)->find(); //查询更新后订单

            order_history($orderId, $userinfo, $order_info, $return_data);  //插入订单更改记录

            M()->commit();
            return_data('success');
        } catch (\Exception $e) {
            M()->rollback();
            return_err($e->getMessage());
        }

    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/template",
     *   tags={"Orders"},
     *   summary="下载订单模板",
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
    public function template()
    {
        //导出Excel
        $xlsName = "模板";
        $xlsCell = array(
            array('OrderId', '订单编号'),
            array('PlanName', '套餐'),
            array('OrderPhone', '订购号码'),
            array('OrderTime', '下单时间'),
            array('Address', '地址'),
            array('Consignee', '收件人'),
            array('Phone', '电话号码'),
            array('ScheduleRemark', '调度员备注'),
            array('Block', '需要外呼'),
        );
        $xlsData = array();
        exportExcel($xlsName, $xlsCell, $xlsData);
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/export",
     *   tags={"Orders"},
     *   summary="导出搜索结果 Excel",
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

    public function export()
    {
        $data_trr['importSequenceId'] = I('importSequenceId');
        $data_trr['importSequenceStartTime'] = I('importSequenceStartTime');
        $data_trr['importSequenceEndTime'] = I('importSequenceEndTime');
        $data_trr['startOrderTime'] = I('startOrderTime');
        $data_trr['endOrderTime'] = I('endOrderTime');
        $data_trr['startCompleteTime'] = I('startCompleteTime');
        $data_trr['endCompleteTime'] = I('endCompleteTime');
        $data_trr['orderId'] = I('orderId');
        $data_trr['phone'] = I('phone');
        $data_trr['userId'] = I('userId');
        $data_trr['pointId'] = I('pointId');
        $data_trr['businessId'] = I('businessId');
        $data_trr['callState'] = I('callState');
        $status = I('state');
        if (!empty($status)) {
            $data_trr['state'] = explode(',', $status);
        }
        $data_trr['startCallTime'] = I('startCallTime');
        $data_trr['endCallTime'] = I('endCallTime');
        $data_trr['notConnectCount'] = I('notConnectCount');
        $data_trr['district'] = I('district');
        $data_trr['visitTime'] = I('visitTime');
     // print_r($data_trr);die();
        $map = where($data_trr);
        $orders = M('orders');
        $count = $orders
            ->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
            ->where($map)->count();
        $i = 0;
        $limit = 1000;
        $ppp = ceil($count / $limit);
        $pp = range(1, $ppp);
        foreach ($pp as $kkk => $vvv) {
            $offset = ($vvv - 1) * $limit;
            $res[$kkk] = $orders
                ->join('left join importsequences on orders.ImportSequenceId=importsequences.Id')
                ->join('left join users on orders.UserId=users.Id')
                ->join('left join points on orders.PointId=points.Id')
                ->field('orders.*,importsequences.ImportTime as importsequencetime,users.name as username,points.address as pointaddress,points.Name as pointname')
                ->where($map)->limit($offset, $limit)->select();
            $str[$kkk] = "订单编号,套餐,订购号码,下单时间,地址,收件人,电话号码,调度员备注,派送员备注,地区,订单纬度,经度,接单时纬度,接单时经度,接单地址,完结时纬度,完结时经度,完结地址,最后呼叫状态,订单状态,订单类型,最后呼叫时间,优先级,快递员编号,业务编号,导入序列编号,导入时间,配送点编号,上门时间,需要外呼,配送员,配送点地址,分配时间,接单时间,完结时间,下次呼叫时间,最后呼叫工号,最后操作调度员,调度员退单原因,快递员退单原因,调度员非当天上门原因,派送员非当天上门原因,副卡号码,额外信息,最后操作配送员,配送员完结时间,充值金额,外呼通话时长";
            $exl11[$kkk] = explode(',', $str[$kkk]);
            foreach ($res[$kkk] as $k => $v) {
                $v['orderid'] = trim($v['orderid']);
                $LastCallname=$this->getLastCallname($v['id']);
                $v['lastcallname'] = $LastCallname['name'];
                $v['duration'] = $LastCallname['duration'];
                $v['editbyname'] = $this->getLastEditbyname($v['id']);

                if ($v['userid']==0||empty($v['userid'])&&$v['state']=='成功'){
                    $lastdata=$this->getLastUserName($v['id']);
                    $v['lastusername']=isset($lastdata['name'])?$lastdata['name']:'';
                    $v['lastcompletetime']=isset($lastdata['time'])?$lastdata['time']:'';
                }
                else{
                    $v['lastusername']=$v['username'];
                    $v['lastcompletetime']=$v['completetime'];

                }
                $exl[$kkk][] = array(
                    $v['orderid'], $v['planname'], $v['orderphone'], $v['ordertime'], $v['address'], $v['consignee'], $v['phone'], $v['scheduleremark'], $v['expressremark'], $v['district'], $v['lat'], $v['lng'], $v['startlat'], $v['startlng'], $v['startaddress'], $v['endlat'], $v['endlng'], $v['endaddress'], $v['lastcallstate'], $v['state'], $v['ordertype'], $v['lastcalltime'], $v['priority'], $v['userid'], $v['businessid'], $v['importsequenceid'], $v['importsequencetime'], $v['pointname'], $v['visittime'], $v['block'], $v['username'], $v['pointaddress'], $v['assigntime'], $v['accepttime'], $v['completetime'], $v['nextcalltime'], $v['lastcallname'], $v['editbyname'], $v['schedulecancelreason'], $v['expresscancelreason'], $v['scheduleanotherdayreason'], $v['expressanotherdayreason'], $v['secondarycard'], $v['extra'],$v['lastusername'],$v['lastcompletetime'],$v['recharge'],$v['duration']
                );
            }
            
            Method::exportToExcel('orders_' . time() . $vvv . '.csv', $exl11[$kkk], $exl[$kkk], $i);
            $i++;
        }
    }

    private function getLastCallname($orderid)
    {
        $name = "";
        $duration=0;
        $callhistories = M('callhistories');
        $map['Name'] = array('neq', 'Robot');
        $map['Deleted'] = 0;
        $map['OrderId'] = $orderid;
        $callhistoriesinfo = $callhistories->field('name,(UNIX_TIMESTAMP(EndTime)-UNIX_TIMESTAMP(StartTime)) as duration')->where($map)->order('StartTime desc')->find();
        if ($callhistoriesinfo) {
            $name = $callhistoriesinfo['name'];
            $duration= $callhistoriesinfo['duration'];
        }
        $arr=array(
            'name'=>$name,
            'duration'=>$duration
        );
        return $arr;
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
    private function getLastUserName($orderid)
    {
        $data = array();
        $sql = 'select EditBy,Created FROM orderhistory as a LEFT JOIN users on a.EditBy=users.Name LEFT JOIN userroles as b on users.Id=b.UserId LEFT JOIN roles on b.RoleId=roles.Id where roles.RoleName=\'express\'  and a.Deleted=0 and a.RowId=' . $orderid . ' ORDER BY a.Created desc LIMIT 1';
        $list = M()->query($sql);
        if ($list) {
            $data=array(
                'name'=>$list[0]['editby'],
                'time'=>$list[0]['created'],
            );
        }
        return $data;
    }
  /*  private function getLastExpressName($orderid)
    {
        $name = "";
        $sql = 'select EditBy,Created FROM orderhistory as a LEFT JOIN users on a.EditBy=users.Name LEFT JOIN userroles as b on users.Id=b.UserId LEFT JOIN roles on b.RoleId=roles.Id where roles.RoleName=\'express\' and roles.Deleted=0 and a.Deleted=0 and users.Deleted=0 and a.RowId=' . $orderid . ' ORDER BY a.Created desc LIMIT 1';
        $list = M()->query($sql);
        if ($list) {
            $name = $list[0]['editby'];
            $created = $list[0]['created'];
        }
        return array('name' => $name, 'created' => $created);
    }*/

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Orders/delay",
     *   tags={"Orders"},
     *   summary="获取超时订单",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="PageIndex",
     *     in="query",
     *     description="PageIndex",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="PageSize",
     *     in="query",
     *     description="PageSize",
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
    public function delay()
    {
        $return_data['pageInfo'] = array(
            'entities' => array(),
            'total' => 0,
            'pageIndex' => 1,
            'pageSize' => 5,
            'hasPreviousPage' => false,
            'hasNextPage' => false,
        );
        return_data($return_data);
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Orders/manualCallCheck",
     *   tags={"Orders"},
     *   summary="检查是否符合手拨条件",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     description="phone",
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
    public function manualCallCheck()
    {
        is_cookie();
        $phoneArr = array();
        $data = is_json();

        $phone = $data['phone'];
        if (empty($phone)) return_err('参数错误：手机号为空');

        $where = "Deleted=0 and LastCallState='已提取'";

        $OrdersModel = M('Orders');
        $list = $OrdersModel->where($where)->order('id asc')->select();
        foreach ($list as $row) {
            $phoneArr[] = $row['phone'];
        }

        if (in_array($phone, $phoneArr)) {
            return_err('已提取');
        }

        return_data('success');
    }

    //更新订单手机号码--一次作废
//    public function updateData(){
//        $table = C('CRAWLER_TABLE');
//        $_conn = mysqli_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'), C('CRAWLER_DB_NAME'), C('DB_PORT'));
//        if (!$_conn) {
//            die('数据库连接失败');
//        }
//        $sql = "select * from " . $table;
//        $result = mysqli_query($_conn, $sql, MYSQLI_USE_RESULT);
//
//        while ($row = mysqli_fetch_assoc($result)) {
//            $sql = "update orders set OrderPhone='".$row['ORDER_PHONE_NUMBER']."' where OrderId='".$row['ORDER_ID']."'";
//            M()->execute($sql);
//        }
//        echo '执行成功';
//    }

    //更新订购号码--一次作废
//    public function updateOrderData(){
//        $xls_file_url = "Public/excel/order_import.xls";
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
//        for ($j=2; $j<=$highestRow; $j++){
//            for ($k='A'; $k<=$highestColumn; $k++){
//                $data[$k][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
//            }
//        }
//
//
//
//        $count = count($data['A']);
//
//        for ($i=0; $i<$count; $i++){
//            if (!empty($data['A'][$i])){
//                $sql = "update orders set OrderPhone='".$data['B'][$i]."' where OrderId='".$data['A'][$i]."'";
//                M()->execute($sql);
//            }
//        }
//
//        echo '执行成功';
//
//    }


    public function updateCallhistoryData(){
        set_time_limit(0);
        $xls_file_url = "Public/excel/callhistory_import.xls";

        vendor('PHPExcel.Classes.PHPExcel');

        vendor('PHPExcel.Classes.PHPExcel.IOFactory');
        vendor('PHPExcel.Classes.PHPExcel.Reader.Excel5');

        $objPHPExcel = \PHPExcel_IOFactory::load($xls_file_url);


        $sheet = $objPHPExcel->getSheet(0);

        $highestRow = $sheet->getHighestRow();

        $highestColumn = $sheet->getHighestColumn();


        $data = array();

        for ($j=2; $j<=$highestRow; $j++){
            for ($k='A'; $k<=$highestColumn; $k++){
                $data[$k][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }

        $count = count($data['B']);


        for ($i=0; $i<$count; $i++){
            $new_file = str_replace('/API//REC/manREC/', '', $data['B'][$i]);
            $arr = explode('/', $new_file);
            $total = count($arr)-1;
            $recordFile = $arr[$total];

            if (!empty($recordFile)){
                $sql = "update callhistories set RecordFile='".$recordFile."' where OrderId='".$data['A'][$i]."'";
//                echo $sql."\r\n";
                M()->execute($sql);
            }
        }

        echo '执行成功';

    }


    /**
     * ["ORDER_ID"]=>
     * string(10) "2992274235"
     * ["TRADING_TIME"]=>
     * string(15) "2019-01-2611:26"
     * ["ORDER_AREA"]=>
     * string(22) " 上海 浦东新区"
     * ["DELIVERER"]=>
     * string(15) "澄美李可可"
     * ["DELIVERER_PHONE"]=>
     * string(11) "18501661126"
     * ["SEND_ORDER_TIME"]=>
     * string(15) "2019-01-2809:56"
     * ["SEND_ORDER_TYPE"]=>
     * string(12) "他人转派"
     * ["PRODUCT_NAME"]=>
     * string(24) "阿里小宝卡亲情卡"
     * ["ORDER_PHONE_NUMBER"]=>
     * string(19) "16621254971(上海)"
     * ["RECEIVER_ADDRESS"]=>
     * string(75) "上海市浦东新区高东新路500栋2504室（CM约1月28日晚上门）"
     * ["PERSON_NAME"]=>
     * string(21) "张恒义      
     * "
     * ["PERSON_PHONE_NUM"]=>
     * string(11) "16621254971"
     * ["UPDATE_TIME"]=>
     * string(16) "2019-01-28 15:59"
     */
    public function import()
    {
        $execute_start_time = $this->fulldate('Y-m-d H:i:s u');
        Log::record('-----------执行Orders/import开始时间: ' . $execute_start_time . '-------');

        $table = C('CRAWLER_TABLE');
        $_conn = mysqli_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'), C('CRAWLER_DB_NAME'), C('DB_PORT'));
        if (!$_conn) {
            die('数据库连接失败');
        }
        $sql = "select * from " . $table;
        $result = mysqli_query($_conn, $sql, MYSQLI_USE_RESULT);
        $paramsArr = array();  //插入orders参数数组
        $orderArr = array(); //参数数组集合
        $OrdersModel = M('Orders');
        $cur_time = date('Y-m-d H:i:s');
        $businessId = $this->getBusinessId();

        $sql = "select c.*,p.Lat,p.lng,p.Radius from crawlerconfigs c LEFT JOIN points p  ON  c.PointId=p.Id where c.Deleted=0 and c.PointId is not null order by p.Radius asc";
        $match_list = M()->query($sql); //匹配站点列表

        while ($row = mysqli_fetch_assoc($result)) {
            //查询orders是否存在该记录，存在去重
            $isExist = $OrdersModel->where("Deleted=0 and OrderId='" . $row['ORDER_ID'] . "'")->find();
            if (!$isExist) {
                //判断配置是否存在，存在插入，不存在不记录
                $crawler = $this->crawlerconfigsWrap($row['DELIVERER_PHONE']);
                if (!empty($crawler)) {
                    //插入orders数据库
                    $formatArea = $this->formatArea($row['ORDER_AREA']);
                    $formatOrderTime = $this->formatOrderTime($row['TRADING_TIME']);
                    $paramsArr['OrderId'] = $row['ORDER_ID'];
                    $paramsArr['PlanName'] = $row['PRODUCT_NAME'];
                    $paramsArr['OrderPhone'] = $row['ORDER_PHONE_NUMBER'];
                    $paramsArr['OrderTime'] = $formatOrderTime;
                    $paramsArr['Address'] = $row['RECEIVER_ADDRESS'];
                    $paramsArr['Consignee'] = $row['PERSON_NAME'];
                    $paramsArr['Phone'] = $row['PERSON_PHONE_NUM'];
                    $paramsArr['District'] = $formatArea;
                    $paramsArr['OrderType'] = '可派单';

                    if (empty($crawler['needmatchpoint'])) {
                        if (empty($crawler['userid']) && empty($crawler['pointid'])) {
                            $paramsArr['UserId'] = '';
                            $paramsArr['PointId'] = '';
                            $paramsArr['State'] = '未分配';
                            $paramsArr['LastCallState'] = '未拨打';
                            $paramsArr['AssignTime'] = '';
                            $paramsArr['VisitTime'] = '';

                        } else {
                            $paramsArr['UserId'] = $crawler['userid'];
                            $paramsArr['PointId'] = $crawler['pointid'];
                            $paramsArr['State'] = '派送';
                            $paramsArr['LastCallState'] = '未拨打';
                            $paramsArr['AssignTime'] = $cur_time;
                            $paramsArr['VisitTime'] = $cur_time;

                        }
                    } else {
                        //查询所有userid不为空，deleted为0的记录，根据距离排序获得pointid，插入orders数据库
                        $matchpoint_list = $this->matchpoint($row['RECEIVER_ADDRESS'], $match_list);
                        if (empty($matchpoint_list)) {
                            $paramsArr['UserId'] = '';
                            $paramsArr['PointId'] = '';
                            $paramsArr['State'] = '未分配';
                            $paramsArr['LastCallState'] = '未拨打';
                            $paramsArr['AssignTime'] = '';
                            $paramsArr['VisitTime'] = '';
                        } else {
                            $paramsArr['UserId'] = $matchpoint_list['userid'];
                            $paramsArr['PointId'] = $matchpoint_list['pointid'];
                            $paramsArr['State'] = '派送';
                            $paramsArr['LastCallState'] = '未拨打';
                            $paramsArr['AssignTime'] = $cur_time;
                            $paramsArr['VisitTime'] = $cur_time;
                        }

                    }
                    $orderArr[] = $paramsArr;
                }
            }
        }
        $uuid = $this->getUUId();
        $count = count($orderArr);
        $ImportsequencesModel = M('Importsequences');

        M()->startTrans();
        try {
            //获取到一批订单
            $data = array();
            $data['Guid'] = $uuid;
            $data['ImportTime'] = date('Y-m-d H:i:s');
            $data['Name'] = '系统导入';
            $data['Count'] = $count;
            $data['BusinessId'] = $businessId;
            $res = $ImportsequencesModel->add($data);

            Log::record('----count:'.$count.'--------');

            if ($count > 0) {
                $union_sql_arr = array();
                $insert_sql = "insert into orders (OrderId,PlanName,OrderPhone,OrderTime,Address,Consignee,Phone,District,OrderType,ImportSequenceId,UserId,PointId,State,LastCallState,AssignTime,VisitTime,BusinessId,Priority,Block,NotConnectCount) values ";

                for ($i = 0; $i < $count; $i++) {
                    $union_sql_arr[] = " ('" . $orderArr[$i]['OrderId'] . "','" . $orderArr[$i]['PlanName'] . "','" . $orderArr[$i]['OrderPhone'] . "','" . $orderArr[$i]['OrderTime'] . "','" . $orderArr[$i]['Address'] . "','" . $orderArr[$i]['Consignee'] . "','" . $orderArr[$i]['Phone'] . "','" . $orderArr[$i]['District'] . "','" . $orderArr[$i]['OrderType'] . "'," . $res . ",'" . $orderArr[$i]['UserId'] . "','" . $orderArr[$i]['PointId'] . "','" . $orderArr[$i]['State'] . "','" . $orderArr[$i]['LastCallState'] . "','" . $orderArr[$i]['AssignTime'] . "','" . $orderArr[$i]['VisitTime'] . "'," . $businessId . ",0,0,0)";
                }
                $union_sql_str = implode(',', $union_sql_arr);
                $full_sql = $insert_sql . $union_sql_str;
                Log::record('sql语句：'.$full_sql);
                M()->execute($full_sql);
            }

            M()->commit();

            $execute_end_time = $this->fulldate('Y-m-d H:i:s u');
            Log::record('-----------执行Orders/import结束时间: ' . $execute_end_time . '-------');
            return_data('success');
        } catch (\Exception $e) {
            M()->rollback();
            return_err($e->getMessage());
        }

    }

    public function formatOrderTime($orderTime)
    {
        $format = str_replace(substr($orderTime, 0, 10), substr($orderTime, 0, 10) . " ", $orderTime);
        return $format;
    }

    public function getUUId($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 12);
        return strtoupper($prefix . $uuid);
    }

    public function formatArea($order_area)
    {
        $new_area = trim(str_replace('上海', '', $order_area));
        return $new_area;
    }

    public function getBusinessId()
    {
        $BusinessesModel = M('Businesses');
        $info = $BusinessesModel->where("deleted=0 and Name='码上购'")->find();
        return $info['id'];
    }

    public function crawlerconfigsWrap($phone)
    {
        $CrawlerconfigsModel = M('Crawlerconfigs');
        $where = "Deleted=0 and Phone='" . $phone . "'";
        $crawler_info = $CrawlerconfigsModel->where($where)->find();
        return $crawler_info;
    }

    public function matchpoint($address, &$matchData)
    {
        $list = array();
        list($start_lng, $start_lat) = $this->getLatAndLng($address); //订单经纬度
        foreach ($matchData as &$row) {
            $distance = getDistance($start_lat, $start_lng, $row['lat'], $row['lng']);
            if (intval($distance / 1000) <= $row['radius']) {
                $list = $row;
                break;
            }
        }
        unset($row);
        return $list;
    }


    public function getLatAndLng($address)
    {
        $address = preg_replace('/([(（].*?[)）])/','', $address);
        $key = "4a9acf4ec92ff9a5f4c99bb6006503b7";
        $url = "http://restapi.amap.com/v3/place/text?keywords=" . $address . "&city=上海&offset=1&page=1&key=" . $key . "&extensions=all";
        $result = doGet($url);
        $result = json_decode($result, true);
        if ($result['status'] != 1) {
            array(0, 0);
        } else {
            list($lng, $lat) = explode(',', $result['pois'][0]['location']);
        }

        return array($lng, $lat);
    }


    function fulldate($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);//改这里的数值控制毫秒位数
        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }


}

