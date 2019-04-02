<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/5
 * Time: 14:12
 */

namespace Home\Controller;

use Think\Controller;
use Think\Log;

class BusinessesController extends Controller
{
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Businesses/establish",
     *   tags={"Businesses"},
     *   summary="新建业务类型",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="name",
     *     in="必传",
     *     description="业务名称",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="description",
     *     in="必传",
     *     description="业务简述",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="type",
     *     in="必传",
     *     description="业务类型",
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
        is_cookie();//验证是否登录
        $datatrr = is_json();
        $name = $datatrr['name'];
        $description = $datatrr['description'];
        $type = $datatrr['type'];
        $type = empty($type) ? '调度业务' : $type;
        if (empty($name) || empty($description)) {
            return_err('参数错误');
        }
        $businesses = M('businesses');
        $data['Name'] = "$name";
        $data['Description'] = "$description";
        $data['Type'] = "$type";
        $add = $businesses->add($data);
        if ($add == false) {
            return_err('创建失败，请重试');
            Log::record('创建任务插入数据库失败：' . json_encode($datatrr));
        }
        return_data('success');
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Businesses/obtainAll",
     *   tags={"Businesses"},
     *   summary="获取所有业务类型",
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
    public function obtainAll()
    {
        is_cookie();//验证是否登录
        $businesses = M('businesses');
        $map['Delete'] = 0;
        $list = $businesses->where($map)->select();
        $return_data['businesses'] = $list;
        return_data($return_data);
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Businesses/obtainOne",
     *   tags={"Businesses"},
     *   summary="获取单个业务类型",
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
    public function obtainOne()
    {
        is_cookie();//验证是否登录
        $id = I('id');
        if (!$id > 0) {
            return_err('参数错误');
        }
        $businesses = M('businesses');
        $map['Id'] = $id;
        $list = $businesses->where($map)->find();
        $return_data['businesses'] = $list;
        return_data($return_data);
    }

    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/Businesses/delete",
     *   tags={"Businesses"},
     *   summary="删除业务类型",
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
        $businesses = M('businesses');
        $map['id'] = $id;
        $data['Deleted'] = $id;
        $delete = $businesses->where($map)->save($data);
        if ($delete == false) {
            return_err('删除失败，请重试');
        }
        return_data('success');
    }

    /**
 * @SWG\Patch(path="/orderschedule/index.php/Home/Businesses/update",
 *   tags={"Businesses"},
 *   summary="更新业务类型",
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
    public function update()
    {
        is_cookie();//验证是否登录
        $id = I('id');
        $data = is_json();
        $jsonPatchDocument = $data['jsonPatchDocument'];
        $return_data = json_patch($jsonPatchDocument);
        $businesses = M('businesses');
        $map['id'] = $id;
        $update = $businesses->where($map)->save($return_data);
        if ($update == false) {
            return_err('删除失败，请重试');
        }
        return_data('success');
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Businesses/validate",
     *   tags={"Businesses"},
     *   summary="验证是否可用",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="name",
     *     in="必传",
     *     description="业务名",
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
    public function validate()
    {
        is_cookie();//验证是否登录
        $data = is_json();
        $name = $data['name'];
        $map['Name'] = "$name";
        $map['Delete'] = 0;
        $businesses = M('businesses');
        $info = $businesses->field('id')->where($map)->find();
        if ($info) {
            return_err('业务名已经存在');
        } else {
            return_data('success');
        }
    }

}