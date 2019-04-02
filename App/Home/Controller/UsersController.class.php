<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/17
 * Time: 11:35
 */
namespace Home\Controller;

use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;
use Think\Log;

class UsersController extends Controller{
    protected $orderState = array(
        '未分配','派送','繁忙另约','改约','改派中','派送中','成功','转物流','退单','已激活','转区域','拒访','失效'
    );

//    public function __construct()
//    {
////        $roles = array();
//        $username = is_cookie();
////        $sql = "select r.RoleName from  userroles ur LEFT JOIN users u on ur.UserId=u.Id LEFT JOIN roles r on ur.RoleId=r.Id where u.UserName='".$username."'";
////        $list = M()->query($sql);
////        foreach ($list as $row){
////            $roles[] = $row['rolename'];
////        }
////        if (!in_array('admin', $roles)) return_err_401("受限访问，请联系管理员更改权限");
//    }

    public function checkAuth(){
        $roles = array();
        $username = is_cookie();
        $sql = "select r.RoleName from  userroles ur LEFT JOIN users u on ur.UserId=u.Id LEFT JOIN roles r on ur.RoleId=r.Id where u.UserName='".$username."'";
        $list = M()->query($sql);
        foreach ($list as $row){
            $roles[] = $row['rolename'];
        }
        if (!in_array('admin', $roles)&&!in_array('schedule', $roles)) return_err_401("受限访问，请联系管理员更改权限");
    }


    public function search(){
        $this->checkAuth();
        $data = is_json();
        $pointId = $data['pointId'];
        $userId = $data['userId'];
        $pageIndex = $data['pageIndex'];
        $pageSize = $data['pageSize'];

        $where = "u.Deleted=0";
        if (!empty($pointId)) $where .= " and u.PointId=".$pointId;
        if (!empty($userId)) $where .= " and u.Id=".$userId;

        $pageIndex = empty($pageIndex) ? 1 : $pageIndex;
        $pageSize = empty($pageSize) ? 5 : $pageSize;
        $offset = ($pageIndex - 1) * $pageSize;

        $total_sql = "SELECT u.* FROM UserRoles ur LEFT JOIN users u ON u.Id=ur.UserId WHERE ".$where." and ur.RoleId=3";
        $sql = "SELECT u.* FROM UserRoles ur LEFT JOIN users u ON u.Id=ur.UserId WHERE ".$where." and ur.RoleId=3 order by u.Id asc limit ".$offset.",".$pageSize;
        $total = M()->query($total_sql);
        $list = M()->query($sql);

        foreach ($list as &$row){
            $row = $this->userWrap($row['id']);
        }
        unset($row);

        $pageInfo['entities'] = $list;
        $pageInfo['total'] = count($total);
        $pageInfo['pageIndex'] = $pageIndex;
        $pageInfo['pageSize'] = $pageSize;
        $pageInfo['hasNextPage'] = (count($total) / $pageSize - $pageIndex) <= 0 ? false : true;
        $pageInfo['hasPreviousPage'] = $pageIndex != 1 ? true : false;

        return_data(array('pageInfo'=>$pageInfo));

    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Users/express",
     *   tags={"Users"},
     *   summary="分页获取派送员",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="PageIndex",
     *     in="必传",
     *     description="当前页",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="PageSize",
     *     in="必传",
     *     description="每页显示记录",
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
    public function express(){
        $this->checkAuth();
        $pageInfo = array();

        $PageIndex = I('PageIndex');//第几页
        $PageSize = I('PageSize');//数据条数
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;

        $total_sql = "SELECT u.* FROM UserRoles ur LEFT JOIN users u ON u.Id=ur.UserId WHERE u.Deleted=0 and  ur.RoleId=3";
        $sql = "SELECT u.* FROM UserRoles ur LEFT JOIN users u ON u.Id=ur.UserId WHERE u.Deleted=0 and  ur.RoleId=3 order by u.Id asc limit ".$offset.",".$PageSize;
        $total = M()->query($total_sql);
        $list = M()->query($sql);

        foreach ($list as &$row){
            $row = $this->userWrap($row['id']);
        }
        unset($row);

        $pageInfo['entities'] = $list;
        $pageInfo['total'] = count($total);
        $pageInfo['pageIndex'] = $PageIndex;
        $pageInfo['pageSize'] = $PageSize;
        $pageInfo['hasNextPage'] = (count($total) / $PageSize - $PageIndex) <= 0 ? false : true;
        $pageInfo['hasPreviousPage'] = $PageIndex != 1 ? true : false;

        return_data(array('pageInfo'=>$pageInfo));

    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Users/expresses",
     *   tags={"Users"},
     *   summary="获取所有派送员",
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
    public function expresses(){
        $this->checkAuth();
        $sql = "SELECT u.* FROM UserRoles ur LEFT JOIN users u ON u.Id=ur.UserId WHERE ur.RoleId=3 and u.Deleted=0 order by u.Id asc";
        $list = M()->query($sql);

        foreach ($list as &$row){
            $row = $this->userWrap($row['id']);
        }
        unset($row);

        return_data(array('users'=>$list));
    }


    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Users/registers",
     *   tags={"Users"},
     *   summary="分页获取注册人员",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="PageIndex",
     *     in="必传",
     *     description="当前页",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="PageSize",
     *     in="必传",
     *     description="每页显示记录",
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
    public function registers(){
        $this->checkAuth();
        $pageInfo = array();

        $PageIndex = I('PageIndex');//第几页
        $PageSize = I('PageSize');//数据条数
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;

        $total_sql = "SELECT * FROM users WHERE Deleted=0 and  Review = 0";
        $sql = "SELECT * FROM  Users  WHERE Deleted=0 and  Review = 0 order by Id asc limit ".$offset.",".$PageSize;
        $total = M()->query($total_sql);
        $list = M()->query($sql);

        foreach ($list as &$row){
            $row = $this->userWrap($row['id']);
        }
        unset($row);

        $pageInfo['entities'] = $list;
        $pageInfo['total'] = count($total);
        $pageInfo['pageIndex'] = $PageIndex;
        $pageInfo['pageSize'] = $PageSize;
        $pageInfo['hasNextPage'] = (count($total) / $PageSize - $PageIndex) <= 0 ? false : true;
        $pageInfo['hasPreviousPage'] = $PageIndex != 1 ? true : false;

        return_data(array('pageInfo'=>$pageInfo));

    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Users/detail",
     *   tags={"Users"},
     *   summary="根据 ID 获取用户信息",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="用户id",
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
    public function detail(){
        $this->checkAuth();
        $id = Fun::requestInt('id', 0);
        if (empty($id)) return_err("参数错误：用户id为空");

        $info = $this->userWrap($id);

        return_data(array('user'=>$info));

    }


    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/Users/delete",
     *   tags={"Users"},
     *   summary="删除用户",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="用户id",
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
    public function delete(){
        $this->checkAuth();
        $username = is_cookie();
        $data = is_json();
        $id = $data['id'];
        if (empty($id)) return_err("参数错误：用户id为空");

        $order_sql = "select * from orders WHERE (State='".$this->orderState[0]."' or State='".$this->orderState[1]."' or State='".$this->orderState[3]."' or State='".$this->orderState[4]."' or State='".$this->orderState[5]."' or State='".$this->orderState[6]."') and Deleted=0 and UserId=".$id." limit 0,1";
        $order = M()->query($order_sql);

        if (!empty($order)) return_err("当前用户还有未完成订单，不允许删除");

        $update_sql = "update users u LEFT JOIN userpoints up ON u.Id = up.UserId LEFT JOIN userroles ur ON u.Id=ur.UserId LEFT JOIN userbusinesses ub ON u.Id=ub.UserId set u.Deleted=u.Id, up.Deleted=up.Id, ur.Deleted=ur.Id, ub.Deleted=ub.Id where u.Id=".$id;
        $rs = M()->execute($update_sql);

        Log::record('----delete user-----');
        Log::record($username."删除了".$id."结果：".$rs);

        if (empty($rs)) return_err('删除用户失败');
        return_data('success');
    }


    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/Users/update",
     *   tags={"Users"},
     *   summary="更新用户各种信息",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="用户id",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="jsonPatchDocument",
     *     in="必传",
     *     description="更新json",
     *     required=true,
     *     type="json"
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
    public function update(){
        $this->checkAuth();
        $username = is_cookie();
        $id = I('id',0);
        $arr = is_json();

        $addArr = array();
        $removeArr = array();
        $replaceArr = array();
        $replaceOtherArr = array();

        $jsonPatchDocument = $arr['jsonPatchDocument'];

        $businessesArr = array();
        $business_sql = "SELECT b.* FROM UserBusinesses ub LEFT JOIN  Users u ON u.Id = ub.UserId LEFT JOIN Businesses b ON ub.BusinessId= b.Id WHERE ub.Deleted=0 and u.Id = '".$id."' ORDER BY b.Id asc";
        $businesses = M()->query($business_sql);
        foreach ($businesses as $b){
            $businessesArr[] = $b['id'];
        }

        $UsersModel = M('Users');
        $UserBusinessesModel = M('Userbusinesses');

        foreach ($jsonPatchDocument as $key => $val){
            $pathArr = explode('/', $val['path']);
            if ($val['op'] == 'add'){
                $addArr[] = $val['value']['businessId'];
            }elseif ($val['op'] == 'remove'){
                $removeArr[] = $businessesArr[$pathArr[2]];
            }elseif ($val['op'] == 'replace'){
                if (isset($pathArr[2])){
                    $replaceOtherArr = array(
                        'id' => $businessesArr[$pathArr[2]],
                        'value' => $val['value']
                    );
                }else{
                    $keyName = ucfirst($pathArr[1]);
                    $replaceArr[$keyName] = $val['value'];
                    if (isset($replaceArr['Limited'])){
                        $replaceArr['Limited'] = abs($val['value']);
                    }
                }

            }
        }


        M()->startTrans();
        try{
            if (!empty($addArr)){
                $full_sql = $this->combine_insert_user_businesses_sql($id, $addArr);
                M()->execute($full_sql);
            }
            if (!empty($removeArr)){
                $ids = implode(',', $removeArr);
                $UserBusinessesModel->where("UserId=".$id." and  BusinessId in (".$ids.") ")->delete();
            }
            if (!empty($replaceArr)){
                $where = "Deleted=0 and Id=".$id;
                $UsersModel->where($where)->save($replaceArr);
            }
            if (!empty($replaceOtherArr)){
                $UserBusinessesModel->where("Deleted=0 and UserId=".$id." and BusinessId=".$replaceOtherArr['id'])->save(array('BusinessId'=>$replaceOtherArr['value']));
            }

            Log::record($username."成功更新了".$id);
            M()->commit();
        }catch (\Exception $e){
            Log::record($username."失败更新了".$id);
            M()->rollback();
            return_err($e->getMessage());
        }

        return_data('success');

    }

    public function combine_insert_user_businesses_sql($userId,$businessIds){
        $first_sql = "insert into UserBusinesses (UserId,BusinessId) values ";
        $sqlArr = array();
        for ($i=0; $i< count($businessIds); $i++){
            $sqlArr[] = "(".$userId.",$businessIds[$i])";
        }
        $sqlStr = implode(',',$sqlArr);
        $full_sql = $first_sql.$sqlStr;
        return $full_sql;
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Users/self",
     *   tags={"Users"},
     *   summary="获取自己信息",
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
    public function self(){
        $username = is_cookie();

        $today_start_time = date('Y/m/d 00:00:00');
        $last_month_start_time = date('Y/m/01 00:00:00', time());

//        list($cur_start_month_day,$cur_end_month_day) = Method::getMonthDaysTwoMethod();

        $user = $this->checkUserState($username);
        $user_id = $user['id'];

        $info = $this->userWrap($user_id);
        //未完成订单
        $not_finished_sql = "SELECT count(*) total FROM Orders  WHERE Deleted=0 AND (State='".$this->orderState[1]."' OR State='".$this->orderState[3]."' OR State='".$this->orderState[5]."') AND UserId=".$user_id;
        $notFinished = M()->query($not_finished_sql);
        $notFinishedCount = Method::getInt($notFinished[0]['total']);
        //今日完成订单
        $today_finished_sql = "SELECT count(*) total FROM Orders  WHERE Deleted=0 AND State='成功' AND CompleteTime>'".$today_start_time."' AND UserId=".$user_id;
        $todayFinished = M()->query($today_finished_sql);
        $todayFinishedCount = Method::getInt($todayFinished[0]['total']);
        //上月完成订单
        $last_month_finished_sql = "SELECT count(*) total FROM Orders  WHERE Deleted=0 AND State='成功' AND CompleteTime>= '".$last_month_start_time."' AND UserId=".$user_id;
        $lastMonthFinished = M()->query($last_month_finished_sql);
        $lastMonthFinishedCount = Method::getInt($lastMonthFinished[0]['total']);
        //总距离
        $distance = 0;
        $total_lat_and_lng_sql = "SELECT * FROM Orders WHERE Deleted=0 AND State='成功' AND UserId=".$user_id;
        $totalLatAndLngList = M()->query($total_lat_and_lng_sql);
        foreach ($totalLatAndLngList as &$row){
            if ($row['startlat'] != null && $row['endlat'] != null && $row['startlng'] != null && $row['endlng'] != null){
                $distance += getDistance($row['startlat'],$row['startlng'],$row['endlat'],$row['endlng']);
            }
        }
        unset($row);
        $totalKm = round($distance / 1000, 2);
        //退单
        $back_sql = "SELECT count(*) total FROM Orders  WHERE Deleted=0 AND CompleteTime >= '".$last_month_start_time."' AND State='退单' AND UserId=".$user_id;
        $back = M()->query($back_sql);
        $backCount = Method::getInt($back[0]['total']);

        return_data(array('user'=>$info,'notFinished'=>$notFinishedCount,'todayFinished'=>$todayFinishedCount,'lastMonthFinished'=>$lastMonthFinishedCount,'totalKm'=>$totalKm,'backCount'=>$backCount));
    }


    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Users/points",
     *   tags={"Users"},
     *   summary="获取派送点人员分配情况",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="pointId",
     *     in="必传",
     *     description="配送点id",
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
    public function points(){
        $this->checkAuth();
        $pointId = Fun::requestInt('pointId', 0);
        if (empty($pointId)) return_err('参数错误：派送点id为空');

        $UserpointsModel = M('Userpoints');
        $where = "Deleted=0 and PointId=".$pointId;
        $list = $UserpointsModel->where($where)->select();
        foreach ($list as &$row){
            $row['user'] = $this->userWrap($row['userid']);
            $row['point'] = $this->pointWrap($row['pointid']);
            $row['workTime'] = $row['worktime']." 00:00:00";
            $row['id'] = Method::getInt($row['id']);
            $row['deleted'] = Method::getInt($row['deleted']);
            $row['userId'] = Method::getInt($row['userid']);
            $row['pointId'] = Method::getInt($row['pointid']);
            unset($row['userid'],$row['pointid'],$row['worktime']);
        }
        unset($row);
        return_data(array('userPoints'=>$list));
    }


    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Users/review",
     *   tags={"Users"},
     *   summary="
    审核注册用户",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="userId",
     *     in="必传",
     *     description="用户id",
     *     required=true,
     *     type="json"
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
    public function review(){
        $this->checkAuth();
        $username = is_cookie();
        $arr = is_json();
        $userId = $arr['userId'];
        if (empty($userId)) return_err('参数错误：用户id为空');

        $data = array();
        $data['Review'] = 1;

        $UsersModel = M('Users');
        $where = "Deleted=0 and Id=".$userId;
        $res = $UsersModel->where($where)->save($data);
        Log::record($username."审核id为".$userId."结果：".$res);
        if ($res == false) return_err('审核失败，请重试');
        return_data('success');
    }

    public function checkUserState($username){
        $UsersModel = M('Users');
        $where = "Deleted=0 and UserName='".$username."'";
        $user = $UsersModel->where($where)->find();
        if (empty($user)) return_err("用户已被禁用");
        return $user;
    }

    public function userWrap($user_id){
        $UsersModel = M('Users');
        $user = $UsersModel->where("id=".$user_id." and deleted=0")->find();
        $user['id'] = Method::getInt($user['id']);
        $user['deleted'] = Method::getInt($user['deleted']);
        $user['userName'] = $user['username'];
        $user['resetTime'] = $user['resettime'];
        $user['pointId'] = !empty($user['pointid']) ? Method::getInt($user['pointid']) : 5;
        $user['lat'] = Method::zeroToNull($user['lat']);
        $user['lng'] = Method::zeroToNull($user['lng']);
        $user['review'] = !empty($user['review']) ? true : false;
        $user['point'] = $this->pointWrap(5);
//        $user['point'] = !empty($user['pointid']) ? $this->pointWrap(5) : null;
        $user['userRoles'] = null;
        $user['userBusinesses'] = $this->userBusinessWrap($user_id);
        $user['userPoints'] = null;
        $user['orders'] = null;
        $user['callHistories'] = null;
        $user['geoLocations'] = null;
        $user['sourceReassigns'] = null;
        $user['targetReassigns'] = null;
        unset($user['username'],$user['resettime'],$user['pointid']);
        return $user;
    }

    public function userBusinessWrap($user_id){
        $sql = "select ub.* from businesses b LEFT JOIN userbusinesses ub on b.id = ub.BusinessId where ub.Deleted=0 and ub.UserId=".$user_id;
        $list = M()->query($sql);
        foreach ($list as &$row){
            $row['businessId'] = $row['businessid'];
            $row['userId'] = $row['userid'];
            $row['business'] = $this->businessWrap($row['businessid']);
            unset($row['businessid'], $row['userid']);
        }
        unset($row);
        return $list;
    }

    public function businessWrap($business_id){
        $BusinessesModel = M('Businesses');
        $info = $BusinessesModel->where("Deleted=0 and Id=".$business_id)->find();
        return $info;
    }

    public function pointWrap($point_id){
        $PointsModel = M('Points');
        $point = $PointsModel->where("id=".$point_id." and deleted=0")->find();
        $point['id'] = Method::getInt($point['id']);
        $point['deleted'] = Method::getInt($point['deleted']);
        $point['lat'] = empty($point['lat']) ? Method::zeroToNull($point['lat']) : Method::getFloat($point['lat']);
        $point['lng'] = empty($point['lng']) ? Method::zeroToNull($point['lng']) : Method::getFloat($point['lng']);
        $point['radius'] = empty($point['radius']) ? Method::zeroToNull($point['radius']) : Method::getInt($point['radius']);
        $point['userPoints'] = null;
        $point['orders'] = null;
        return $point;
    }

}