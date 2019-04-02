<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/18
 * Time: 11:31
 */
namespace Home\Controller;
use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;

class SchedulesController extends Controller{

    protected $SchedulesRoles = array(2,6,7,8);

    public function __construct()
    {
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
        $data = is_json();
        $nameOrPhone = $data['nameOrPhone'];
        $pageIndex = $data['pageIndex'];
        $pageSize = $data['pageSize'];

        $where = "u.Deleted = 0";
        if (!empty($nameOrPhone)) $where .= " and (name like '%".$nameOrPhone."%' or phone like '%".$nameOrPhone."%')";

        $schedules = array();

        $pageIndex = empty($pageIndex) ? 1 : $pageIndex;
        $pageSize = empty($pageSize) ? 5 : $pageSize;
        $offset = ($pageIndex - 1) * $pageSize;

        $total_sql = "SELECT u.* FROM UserRoles ur LEFT JOIN Roles r ON ur.RoleId=r.Id LEFT JOIN Users u ON ur.UserId=u.Id WHERE ".$where." and ur.Deleted=0 and ur.RoleId=2";
        $sql = "SELECT u.* FROM UserRoles ur LEFT JOIN Roles r ON ur.RoleId=r.Id LEFT JOIN Users u ON ur.UserId=u.Id WHERE ".$where." and ur.Deleted=0 and ur.RoleId=2 order by u.id asc limit ".$offset.",".$pageSize;
        $total = M()->query($total_sql);
        $list = M()->query($sql);

        foreach ($list as &$row){
            $row = $this->userWrap($row['id']);
        }
        unset($row);

        $schedules['entities'] = $list;
        $schedules['total'] = count($total);
        $schedules['pageIndex'] = $pageIndex;
        $schedules['pageSize'] = $pageSize;
        $schedules['hasNextPage'] = (count($total) / $pageSize - $pageIndex) <= 0 ? false : true;
        $schedules['hasPreviousPage'] = $pageIndex != 1 ? true : false;

        return_data(array('schedules'=>$schedules));

    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Schedules/schedules",
     *   tags={"Schedules"},
     *   summary="分页获取调度员",
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
    public function schedules(){
        $schedules = array();

        $PageIndex = I('PageIndex');//第几页
        $PageSize = I('PageSize');//数据条数
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;

        $total_sql = "SELECT u.* FROM UserRoles ur LEFT JOIN Roles r ON ur.RoleId=r.Id LEFT JOIN Users u ON ur.UserId=u.Id WHERE ur.Deleted=0 and ur.RoleId=2";
        $sql = "SELECT u.* FROM UserRoles ur LEFT JOIN Roles r ON ur.RoleId=r.Id LEFT JOIN Users u ON ur.UserId=u.Id WHERE ur.Deleted=0 and ur.RoleId=2 order by u.id asc limit ".$offset.",".$PageSize;

        $total = M()->query($total_sql);
        $list = M()->query($sql);

        foreach ($list as &$row){
            $row = $this->userWrap($row['id']);
        }
        unset($row);

        $schedules['entities'] = $list;
        $schedules['total'] = count($total);
        $schedules['pageIndex'] = $PageIndex;
        $schedules['pageSize'] = $PageSize;
        $schedules['hasNextPage'] = (count($total) / $PageSize - $PageIndex) <= 0 ? false : true;
        $schedules['hasPreviousPage'] = $PageIndex != 1 ? true : false;

        return_data(array('schedules'=>$schedules));

    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Schedules/create",
     *   tags={"Schedules"},
     *   summary="创建调度员",
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
    public function create(){
        $arr = is_json();

        $roleIds = $arr['roleIds'];
        $userName = $arr['userName'];
        $password = $arr['password'];
        $name = $arr['name'];
        $phone = $arr['phone'];
        $address = $arr['address'];

        if (empty($userName)) return_err('参数错误：名称为空');
        if (empty($password)) return_err('参数错误：密码为空');
        if (empty($phone)) return_err('参数错误：手机号为空');

        //users插入
        $data = array();
        $data['UserName'] = $userName;
        $data['Password'] = $password;
        $data['Name'] = $name;
        $data['Phone'] = $phone;
        $data['Address'] = $address;

        $UsersModel = M('Users');

        M()->startTrans();
        try{
            $res = $UsersModel->add($data); //users insert

            $full_sql = $this->combine_insert_user_roles_sql($res, $roleIds); //userroles insert
            M()->execute($full_sql);

            M()->commit();
        }catch (\Exception $e){
            M()->rollback();
            return_err($e->getMessage());
        }

        return_data('success');
    }


    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Schedules/detail",
     *   tags={"Schedules"},
     *   summary="根据 ID 查询调度员",
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
        $id = Fun::requestInt('id', 0);
        if (empty($id)) return_err("参数错误：用户id为空");

        $info = $this->userWrap($id);

        return_data(array('schedule'=>$info));
    }


    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/Schedules/delete",
     *   tags={"Schedules"},
     *   summary="删除调度员",
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
        $data = is_json();
        $id = $data['id'];
        if (empty($id)) return_err("参数错误：用户id为空");

        $update_sql = "update users u LEFT JOIN userroles ur ON u.Id=ur.UserId  set u.Deleted=u.Id, ur.Deleted=ur.Id where u.Deleted=0 and ur.Deleted=0 and u.Id=".$id;
        $rs = M()->execute($update_sql);

        if (empty($rs)) return_err('删除调度员失败');
        return_data('success');

    }


    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/Schedules/update",
     *   tags={"Schedules"},
     *   summary="更新调度员信息",
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
        $id = I('id',0);
        $arr = is_json();

        $addArr = array();
        $removeArr = array();
        $replaceArr = array();
        $replaceOtherArr = array();
        $schedulesArr = array();

        $jsonPatchDocument = $arr['jsonPatchDocument'];

        $schedules_sql = "SELECT r.* FROM UserRoles ur LEFT JOIN  Users u ON u.Id = ur.UserId LEFT JOIN Roles  r ON ur.RoleId= r.Id WHERE ur.Deleted=0 and u.Id = ".$id." ORDER BY r.Id asc";
        $schedules = M()->query($schedules_sql);
        foreach ($schedules as $s){
            $schedulesArr[] = $s['id'];
        }

        foreach ($jsonPatchDocument as $key => $val){
            $pathArr = explode('/', $val['path']);
            if ($val['op'] == 'add'){
                $addArr[] = $val['value']['roleId'];
            }elseif ($val['op'] == 'remove'){
                $removeArr[] = $schedulesArr[$pathArr[2]];
            }elseif ($val['op'] == 'replace'){
                if (isset($pathArr[2])){
                    $replaceOtherArr = array(
                        'id' => $schedulesArr[$pathArr[2]],
                        'value' => $val['value']
                    );
                }else{
                    $keyName = ucfirst($pathArr[1]);
                    $replaceArr[$keyName] = $val['value'];
                }

            }
        }

        $UsersModel = M('Users');
        $UserRolesModel = M('Userroles');

        M()->startTrans();
        try{
            if (!empty($addArr)){
                $full_sql = $this->combine_insert_user_roles_sql($id, $addArr);
                M()->execute($full_sql);
            }
            if (!empty($removeArr)){
                $ids = implode(',', $removeArr);
                $UserRolesModel->where("UserId=".$id." and  RoleId in (".$ids.") ")->delete();
            }
            if (!empty($replaceArr)){
                $where = "Deleted=0 and Id=".$id;
                $UsersModel->where($where)->save($replaceArr);
            }
            if (!empty($replaceOtherArr)){
                $UserRolesModel->where("Deleted=0 and UserId=".$id." and RoleId=".$replaceOtherArr['id'])->save(array('RoleId'=>$replaceOtherArr['value']));
            }
            M()->commit();
        }catch (\Exception $e){
            M()->rollback();
            return_err($e->getMessage());
        }

        return_data('success');
    }

    public function combine_insert_user_roles_sql($userId,$roleIds){
        $first_sql = "insert into UserRoles (UserId,RoleId) values ";
        $sqlArr = array();
        for ($i=0; $i< count($roleIds); $i++){
            $sqlArr[] = "(".$userId.",$roleIds[$i])";
        }
        $sqlStr = implode(',',$sqlArr);
        $full_sql = $first_sql.$sqlStr;
        return $full_sql;
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
        $user['point'] = !empty($user['pointid']) ? $this->pointWrap($user['pointid']) : null;
        $user['userRoles'] = $this->userRolesWrap($user_id);
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

    public function userRolesWrap($user_id){
        $sql = "select ur.* from userroles ur LEFT JOIN users u on ur.UserId = u.Id where ur.RoleId in (2,6,7,8) and u.Id=".$user_id;
        $list = M()->query($sql);
        foreach ($list as &$row){
            $row['roleId'] = Method::getInt($row['roleid']);
            $row['userId'] = Method::getInt($row['userid']);
            $row['role'] = $this->roleWrap($row['roleid']);
            unset($row['userid'], $row['roleid']);
        }
        unset($row);
        return $list;
    }

    public function roleWrap($role_id){
        $RolesModel = M('Roles');
        $info = $RolesModel->where("Deleted=0 and Id=".$role_id)->find();
        $info['roleName'] = $info['rolename'];
        unset($info['rolename']);
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