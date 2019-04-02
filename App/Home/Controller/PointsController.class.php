<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/11
 * Time: 11:46
 */

namespace Home\Controller;

use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;
use Think\Log;

class PointsController extends Controller
{

    protected $orderState = array(
        '未分配', '派送', '繁忙另约', '改约', '改派中', '派送中', '成功', '转物流', '退单', '已激活', '转区域', '拒访', '失效'
    );

    public function __construct()
    {
        $roles = array();
        $username = is_cookie();
        $sql = "select r.RoleName from  userroles ur LEFT JOIN users u on ur.UserId=u.Id LEFT JOIN roles r on ur.RoleId=r.Id where u.UserName='" . $username . "'";
        $list = M()->query($sql);
        foreach ($list as $row) {
            $roles[] = $row['rolename'];
        }
        if (!in_array('admin', $roles) && !in_array('schedule', $roles)) return_err_401("受限访问，请联系管理员更改权限");

    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Points/assignWork",
     *   tags={"Points"},
     *   summary="批量分配工作",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *     @SWG\Parameter(
     *     name="assignModels",
     *     in="必传",
     *     description="json数据",
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
    public function assignWork()
    {
        $username = is_cookie();
        $arr = is_json();
        $assignModels = $arr['assignModels'];

        $newData = array();
//        $saveData = array();
        $todayDate = date('Y-m-d');

        //不管今天及以后什么情况，全部删除
        $sql = "update userpoints set deleted=id where worktime >= '" . $todayDate . "' and deleted=0";
        M()->execute($sql);

        foreach ($assignModels as $assign) {
            if (!empty($assign['userPoints'])) {
                $workTime = $assign['workTime'];
                foreach ($assign['userPoints'] as $val) {
                    $val['workTime'] = $workTime;
                    $newData[] = $val;
                }
            }

        }

//        if (count($saveData) > 0){
//            //批量更新
//            $this->batch_update($todayDate,$saveData);
//        }
        if (count($newData) > 0) {
            //批量添加
            $this->batch_insert($newData);
        }

        Log::record($username."操作了排班");

        return_data('success');

    }

    function batch_insert($datalist)
    {
        $sqlData = array();
        $sql = "insert into userpoints(userid,pointid,worktime,deleted) values ";
        foreach ($datalist as $row) {
            $sqlData[] = "(" . $row['userId'] . "," . $row['pointId'] . ",'" . $row['workTime'] . "',0)";
        }
        $sqlStr = implode(",", $sqlData);
        $full_sql = $sql . $sqlStr;
        $res = M()->execute($full_sql);

        return $res;
    }

    //批量修改 data二维数组 field关键字段 参考ci 批量修改函数 传参方式
    function batch_update($worktime, $saveData)
    {
        $saveStr = implode(",", $saveData);
        $sql = "update userpoints set deleted=id where id not in (" . $saveStr . ") and worktime >= '" . $worktime . "' and deleted=0";
        $res = M()->execute($sql);
        return $res;
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Points/userPoints",
     *   tags={"Points"},
     *   summary="查看今天及以后的上班情况",
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
    public function userPoints()
    {
        $username = is_cookie();
        $list = array();
        $today_time = date('Y-m-d');

        $UserpointsModel = M('Userpoints');

        $sql = "select worktime from userpoints where deleted=0 and worktime >= '" . $today_time . "' group by worktime";
        $groupWorkTime = M()->query($sql);
        foreach ($groupWorkTime as &$row) {
            $userPoints = $UserpointsModel->where("Deleted=0 and WorkTime='" . $row['worktime'] . "'")->select();
            foreach ($userPoints as &$val) {
                $val['user'] = $this->userWrap($val['userid']);
                $val['point'] = $this->pointWrap($val['pointid']);
                $val['workTime'] = $val['worktime'] . " 00:00:00";
                $val['id'] = Method::getInt($val['id']);
                $val['deleted'] = Method::getInt($val['deleted']);
                $val['userId'] = Method::getInt($val['userid']);
                $val['pointId'] = Method::getInt($val['pointid']);
                unset($val['userid'], $val['pointid']);
            }
            unset($val);
            $list[] = array(
                'workTime' => $row['worktime'],
                'userPoints' => $userPoints
            );
        }

        Log::record($username."查看了排班");

        return_data(array('assignModels' => $list));

    }


    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Points/todayWork",
     *   tags={"Points"},
     *   summary="查询今日派送人员",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="businessId",
     *     in="必传",
     *     description="业务id",
     *     required=true,
     *     type="int"
     *   ),
     *     @SWG\Parameter(
     *     name="visitTime",
     *     in="必传",
     *     description="访问时间",
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
    public function todayWork()
    {
        $username = is_cookie();
        $list = array();
        $users = array();
        $arr = is_json();
//        $businessId = $arr['businessId'];
        $businessId = 1;
        $cur_time = date('Y-m-d H:i:s', time());
        $visitTime = $arr['visitTime'];

        if (empty($visitTime)) {
            $visit_start_time = date('Y-m-d 00:00:00', time());
            $visit_end_time = $cur_time;
        } else {
            $visit_start_time = $visitTime . " 00:00:00";
            $visit_end_time = $visitTime . " 23:59:59";
        }

        $UserPointsModel = M('Userpoints');

        $sql = "SELECT up.PointId  FROM userpoints up LEFT JOIN userbusinesses ub ON up.UserId= ub.UserId  LEFT JOIN points p ON up.PointId = p.Id WHERE up.deleted=0 and p.Deleted=0 and up.WorkTime = '" . $visitTime . "' AND ub.BusinessId=" . $businessId . " GROUP BY up.PointId";
        $work = M()->query($sql);
        foreach ($work as &$row) {
            $point = $this->pointWrap($row['pointid']);
            $userPoints = $UserPointsModel->where("PointId=" . $row['pointid'] . " and WorkTime='" . $visitTime . "' and deleted=0")->select();
            foreach ($userPoints as &$val) {
                $users[] = $this->userWrap($val['userid'], $visit_start_time, $visit_end_time);
            }
            unset($val);

            $cur_users = $users;
            $users = array();

            $list[] = array(
                'point' => $point,
                'users' => $cur_users
            );

        }

//        Log::record(json_encode($list));
        unset($row);

        Log::record($username."查看了今日排班");

        return_data(array('work' => $list));

    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Points/validate",
     *   tags={"Points"},
     *   summary="验证配送点名称",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="name",
     *     in="必传",
     *     description="派送点名称",
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
        $arr = is_json();
        $name = $arr['name'];

        $where = "name='" . $name . "' and deleted=0";
        $PointsModel = M('Points');
        $info = $PointsModel->where($where)->find();

        if (!empty($info)) return_err("站点已存在");
        return_data('success');
    }


    /**
     * @SWG\Patch(path="/orderschedule/index.php/Home/Points/update",
     *   tags={"Points"},
     *   summary="更新配送点",
     *   description="",
     *   operationId="",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="派送点id",
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
    public function update()
    {
        $username = is_cookie();
        $id = I('id', 0);
        if (empty($id)) return_err("参数错误：id为空");

        $where = "id=" . $id . " and deleted=0";

        $arr = is_json();
        $jsonPatchDocument = $arr['jsonPatchDocument'];
        $data = json_patch($jsonPatchDocument);

        $PointsModel = M('Points');
        $rs = $PointsModel->where($where)->save($data);

        Log::record('----update points-----');
        Log::record($username."更新站点".$id."结果：".$rs);
        Log::record(json_encode($data));

        if ($rs == false) return_err("更新派送点失败");
        return_data('success');
    }


    /**
     * @SWG\Delete(path="/orderschedule/index.php/Home/Points/delete",
     *   tags={"Points"},
     *   summary="删除单个配送点",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="派送点id",
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
    public function delete()
    {
        $username = is_cookie();
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);
        $id = $data['id'];
        if (empty($id)) return_err("参数错误：id为空");

        $order_sql = "select * from orders WHERE (State='" . $this->orderState[0] . "' or State='" . $this->orderState[1] . "' or State='" . $this->orderState[3] . "' or State='" . $this->orderState[4] . "' or State='" . $this->orderState[5] . "' or State='" . $this->orderState[6] . "') and Deleted=0 and PointId=" . $id . " limit 0,1";
        $order = M()->query($order_sql);

        if (!empty($order)) return_err("当前派送点还有未完成订单，不允许删除");

        $update_sql = "update points p LEFT JOIN userpoints up ON p.Id = up.PointId set p.Deleted=p.Id, up.Deleted=up.Id where p.Id=" . $id;
        $rs = M()->execute($update_sql);

        Log::record('-----delete point------');
        Log::record($username."删除站点".$id."结果：".$rs);

        if (empty($rs)) return_err('删除派送点失败');
        return_data('success');
    }


    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Points/create",
     *   tags={"Points"},
     *   summary="添加配送点",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="name",
     *     in="必传",
     *     description="派送点名称",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="district",
     *     in="必传",
     *     description="派送点区域",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="lat",
     *     in="必传",
     *     description="派送点纬度",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="lng",
     *     in="必传",
     *     description="派送点经度",
     *     required=true,
     *     type="int"
     *   ),
     *   @SWG\Parameter(
     *     name="address",
     *     in="必传",
     *     description="派送点地址",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="radius",
     *     in="必传",
     *     description="派送点半径",
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
    public function create()
    {
        $username = is_cookie();
        $params = is_json();
        $name = $params['name'];
        $district = $params['district'];
        $lat = $params['lat'];
        $lng = $params['lng'];
        $address = $params['address'];
        $radius = $params['radius'];

        if (empty($name)) return_err("配送点名称为空");
        if (empty($district)) return_err("配送点区域为空");

        $data = array();
        $data['Name'] = $name;
        $data['District'] = $district;
        $data['Lat'] = $lat;
        $data['Lng'] = $lng;
        $data['Address'] = $address;
        $data['Radius'] = $radius;
        $data['Deleted'] = 0;

        $PointsModel = M('Points');
        $res = $PointsModel->add($data);

        Log::record('----add points----');
        Log::record(json_encode($data));
        Log::record("operator：".$username."添加了站点".$res.",名称：".$name);

        if ($res == false) return_err("添加派送点失败");

        return_data("success");
    }

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Points/export",
     *   tags={"Points"},
     *   summary="导出配送点",
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
    public function export()
    {
        Vendor('PHPExcel.PHPExcel', '', '.php');
        //导出Excel
        $xlsName = "Points";
        $xlsCell = array(
            array('id', 'ID'),
            array('name', '名字'),
            array('address', '地址'),
            array('district', '地区'),
            array('lat', '纬度'),
            array('lng', '经度'),
            array('radius', '半径'),
        );

        $PointsModel = M('Points');
        $xlsData = $PointsModel->where("Deleted=0")->order('id asc')->select();
        foreach ($xlsData as &$row) {
            $row = $this->pointWrap($row['id']);
        }
        unset($row);
        exportExcel($xlsName, $xlsCell, $xlsData);
    }


    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Points/getPoints",
     *   tags={"Points"},
     *   summary="查看所有配送点",
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
    public function getPoints()
    {
        $PointsModel = M('Points');
        $list = $PointsModel->where("deleted=0")->order("id asc")->select();
        foreach ($list as &$row) {
            $row = $this->pointWrap($row['id']);
        }
        unset($row);
        return_data(array('points' => $list));
    }


    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/Points/detail",
     *   tags={"Points"},
     *   summary="查看单个配送点",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="id",
     *     in="必传",
     *     description="派送点id",
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
    public function detail()
    {
        $id = Fun::requestInt('id', 0);
        if (empty($id)) return_err("配送点id为空");

        $info = $this->pointWrap($id);
        if (empty($info)) return_err("配送点不存在");

        return_data(array('point' => $info));
    }

    public function userWrap($user_id, $visit_start_time, $visit_end_time)
    {
        $UsersModel = M('Users');
        $user = $UsersModel->where("id=" . $user_id . " and deleted=0")->find();
        $user['id'] = Method::getInt($user['id']);
        $user['name'] = $user['name'] . "(" . $this->noFinishedOrderCount($user_id, $visit_start_time, $visit_end_time) . ")";
        $user['deleted'] = Method::getInt($user['deleted']);
        $user['userName'] = $user['username'];
        $user['resetTime'] = $user['resettime'];
        $user['pointId'] = !empty($user['pointid']) ? Method::getInt($user['pointid']) : null;
        $user['lat'] = Method::zeroToNull($user['lat']);
        $user['lng'] = Method::zeroToNull($user['lng']);
        $user['review'] = !empty($user['review']) ? true : false;
        $user['point'] = !empty($user['pointid']) ? $this->pointWrap($user['pointid']) : null;
        $user['userRoles'] = null;
        $user['userBusinesses'] = null;
        $user['userPoints'] = null;
        $user['orders'] = null;
        $user['callHistories'] = null;
        $user['geoLocations'] = null;
        $user['sourceReassigns'] = null;
        $user['targetReassigns'] = null;
        unset($user['username'], $user['resettime'], $user['pointid']);
        return $user;
    }

    public function noFinishedOrderCount($user_id, $visit_start_time,$visit_end_time)
    {
        //未完成订单
        $not_finished_sql = "SELECT count(*) total FROM Orders  WHERE Deleted=0 AND (State='" . $this->orderState[1] . "' OR State='" . $this->orderState[3] . "' OR State='" . $this->orderState[5] . "') and VisitTime >= '".$visit_start_time."'  and VisitTime <= '" . $visit_end_time . "' AND UserId=" . $user_id;
        $notFinished = M()->query($not_finished_sql);
        $notFinishedCount = Method::getInt($notFinished[0]['total']);
        return $notFinishedCount;
    }


    public function pointWrap($point_id)
    {
        $PointsModel = M('Points');
        $point = $PointsModel->where("id=" . $point_id . " and deleted=0")->find();
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