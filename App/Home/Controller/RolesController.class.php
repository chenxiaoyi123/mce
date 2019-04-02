<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/16
 * Time: 9:09
 */
namespace Home\Controller;

use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;

class RolesController extends Controller{
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

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Roles/getRoles",
     *   tags={"Roles"},
     *   summary="查看所有角色",
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
    public function getRoles(){
        $RolesModel = M('Roles');
        $where = "Deleted=0";
        $order = "id asc";

        $list = $RolesModel->where($where)->order($order)->select();
        foreach ($list as &$row){
            $row = $this->rolesWrap($row['id']);
        }
        unset($row);

        return_data(array('roles'=>$list));
    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Roles/create",
     *   tags={"Roles"},
     *   summary="创建角色",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="roleName",
     *     in="必传",
     *     description="角色名称",
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
    public function create(){
        $data = array();
        $arr = is_json();
        $roleName = $arr['roleName'];
        if (empty($roleName)) return_err("参数错误：角色名为空");

        $data['RoleName'] = $roleName;
        $data['Deleted'] = 0;

        $RolesModel = M('Roles');
        $res = $RolesModel->add($data);
        if (empty($res)){
            return_err("创建角色失败，请重试");
        }

        return_data('success');
    }
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Roles/detail",
     *   tags={"Roles"},
     *   summary="获取单个角色",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="角色id",
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
    public function detail(){
        $id = Fun::requestInt('id', 0);
        if (empty($id)) return_err("参数错误：角色id为空");

        $info = $this->rolesWrap($id);
        if (empty($info)) return_err("没有查到该角色");

        return_data(array('role'=>$info));
    }
    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/Roles/delete",
     *   tags={"Roles"},
     *   summary="删除角色",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="角色id",
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
        $id = I('id',0);
        if (empty($id)) return_err("参数错误：角色id为空");

        $sql = "UPDATE userroles ur LEFT JOIN roles r ON ur.RoleId=r.Id SET ur.Deleted=ur.Id, r.Deleted=r.Id WHERE r.Id=".$id;

        $res = M()->execute($sql);
        if (empty($res)) return_err("删除失败，请重试");
        return_data('success');
    }
    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/Roles/update",
     *   tags={"Roles"},
     *   summary="更新角色",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="角色id",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="roleName",
     *     in="必传",
     *     description="角色名称",
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
    public function update(){
        $data = array();
        $id = I('id', 0);

        $arr = is_json();
        $roleName = $arr['roleName'];
        if (empty($roleName)) return_err("角色名为空");

        $where = "Id=".$id." and Deleted=0";
        $data['RoleName'] = $roleName;

        $RolesModel = M('Roles');
        $res = $RolesModel->where($where)->save($data);

        if (empty($res)) return_err("更新失败");

        return_data('success');
    }


    public function rolesWrap($roles_id){
        $where = "Deleted=0 and id=".$roles_id;
        $RolesModel = M('Roles');
        $info = $RolesModel->where($where)->find();
        $info['id'] = Method::getInt($info['id']);
        $info['deleted'] = Method::getInt($info['deleted']);
        $info['roleName'] = $info['rolename'];
        $info['userRoles'] = null;
        unset($info['rolename']);
        return $info;
    }
}