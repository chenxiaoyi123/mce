<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/9
 * Time: 9:29
 */

namespace Home\Controller;

use Common\Core\Method;
use Think\Controller;
use Think\Log;

class CrawlerConfigController extends Controller
{
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/CrawlerConfig/obtainAll",
     *   tags={"CrawlerConfig"},
     *   summary="查看所有配置",
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
        $crawlerconfig = M('crawlerconfigs');
        $map['Deleted'] = 0;
        $list = $crawlerconfig->where($map)->select();
        foreach ($list as &$row){
            $row['id'] = Method::getInt($row['id']);
            $row['deleted'] = Method::getInt($row['deleted']);
            $row['needMatchPoint'] = Method::getInt($row['needmatchpoint']) == 0 ? false : true;
            if (!empty($row['userid'])){
                $row['userId'] = Method::getInt($row['userid']);
                $row['user'] = $this->userWrap($row['userid']);
            }else{
                $row['user'] = null;
            }
            if (!empty($row['pointid'])){
                $row['pointId'] = Method::getInt($row['pointid']);
                $row['point'] = $this->pointWrap($row['pointid']);
            }else{
                $row['point'] = null;
            }

            unset($row['userid'],$row['pointid'],$row['needmatchpoint']);
        }
        unset($row);
        $return_data['crawlerConfigs'] = $list;
        return_data($return_data);
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/CrawlerConfig/establish",
     *   tags={"CrawlerConfig"},
     *   summary="新增通话记录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="pointId",
     *     in="",
     *     description="pointId",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="userId",
     *     in="",
     *     description="userId",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="delivery",
     *     in="",
     *     description="delivery",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="needMatchPoint",
     *     in="",
     *     description="needMatchPoint",
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
    public function establish_bak()
    {
        $datatrr = is_json();
        $pointId = $datatrr['pointId'];
        $userId = $datatrr['userId'];
        $delivery = $datatrr['delivery'];
        $needMatchPoint = $datatrr['needMatchPoint'];
        $crawlerconfigs = M('crawlerconfigs');
        $data['Delivery'] = "$delivery";
        $data['NeedMatchPoint'] = "$needMatchPoint";
        $data['UserId'] = "$userId";
        $data['PointId'] = "$pointId";
        $add = $crawlerconfigs->add($data);
        if ($add == false) {
            return_err('创建失败，请重试');
            Log::record('失败数据'.json_encode($datatrr));
        }
        return_data('success');
    }

    public function establish(){
        $data = is_json();
        $phone = $data['phone'];
        $userId = $data['userId'];
        $pointId = $data['pointId'];
        $needMatchPoint = $data['needMatchPoint'];

        if (empty($phone)) return_err('手机号为空');
        $needMatchPoint = $needMatchPoint ? 1 : 0;

        $sql = "insert into crawlerconfigs (Phone, NeedMatchPoint, UserId, PointId, Deleted) values ('".$phone."',".$needMatchPoint.",'".$userId."','".$pointId."',0)";
        $res = M()->execute($sql);
        if (empty($res)){
            return_err('创建失败，请重试');
        }else{
            return_data('success');
        }
    }
    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/CrawlerConfig/delete",
     *   tags={"CrawlerConfig"},
     *   summary="删除配置",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
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
    public function delete()
    {
        //   is_cookie();//验证是否登录
        $data = is_json();
        $id = $data['id'];
        if (!$id > 0) {
            return_err('参数错误');
        }
        $crawlerconfigs = M('crawlerconfigs');
        $map['Id'] = $id;
        $data['Deleted'] = $id;
        $delete = $crawlerconfigs->where($map)->save($data);
        if ($delete == false) {
            return_err('删除失败，请重试');
        }
        return_data('success');
    }
    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/CrawlerConfig/update",
     *   tags={"CrawlerConfig"},
     *   summary="修改配置",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
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
       // is_cookie();//验证是否登录
        $id = I('id');
        $data = is_json();
        $jsonPatchDocument = $data['patchDocument'];
        $return_data = json_patch($jsonPatchDocument);
        $crawlerconfigs = M('crawlerconfigs');
        $map['id'] = $id;
        $update = $crawlerconfigs->where($map)->save($return_data);
        if ($update == false) {
            return_err('删除失败，请重试');
        }
        return_data('success');
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
//        $user['point'] = $this->pointWrap(5);
        $user['point'] = !empty($user['pointid']) ? $this->pointWrap(5) : null;
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