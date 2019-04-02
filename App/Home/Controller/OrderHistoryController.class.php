<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/1/11
 * Time: 11:37
 */
namespace Home\Controller;

use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;

class OrderHistoryController extends Controller{

    protected  $OrderCommentArray = array(
        'ID' => 'ID',
        'Deleted' => 'Deleted',
        'OrderId' => '订单编号',
        'PlanName' => '套餐',
        'OrderPhone' => '订购号码',
        'OrderTime' => '下单时间',
        'Address' => '地址',
        'Consignee' => '收件人',
        'Phone' => '电话号码',
        'ScheduleRemark' => '调度员备注',
        'ExpressRemark' => '派送员备注',
        'District' => '地区',
        'Lat' => '订单纬度',
        'Lng' => '经度',
        'StartLat' => '接单时纬度',
        'StartLng' => '接单时经度',
        'StartAddress' => '接单地址',
        'EndLat' => '完结时纬度',
        'EndLng' => '完结时经度',
        'EndAddress' => '完结地址',
        'LastCallState' => '最后呼叫状态',
        'State' => '订单状态',
        'OrderType' => '订单类型',
        'LastCallTime' => '最后呼叫时间',
        'Priority' => '优先级',
        'UserId' => '快递员编号',
        'BusinessId' => '业务编号',
        'ImportSequenceId' => '导入序列编号',
        'ImportTime' => '导入时间',
        'Block' => '黑名单',
        'PointId' => '配送点编号',
        'VisitTime' => '上门时间',
        'AssignTime' => '分配时间',
        'AcceptTime' => '接单时间',
        'CompleteTime' => '完结时间',
        'NextCallTime' => '下次呼叫时间',
        'ScheduleCancelReason' => '调度员退单原因',
        'ExpressCancelReason' => '快递员退单原因',
        'ScheduleAnotherDayReason' => '调度员非当天上门原因',
        'ExpressAnotherDayReason' => '派送员非当天上门原因',
        'SecondaryCard' => '副卡号码',
        'Extra' => '额外信息',
        'LastCallUser' => '最后呼叫工号',
        'NeedCall' => '需要外呼',
        'LastEditedSchedule' => '最后操作调度员'
    );

    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/OrderHistory/history",
     *   tags={"OrderHistory"},
     *   summary="获取历史订单记录",
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
    public function history(){
        is_cookie();

        $pageInfo = array();

        $PageIndex = I('PageIndex');//第几页
        $PageSize = I('PageSize');//数据条数
        $PageIndex = empty($PageIndex) ? 1 : $PageIndex;
        $PageSize = empty($PageSize) ? 5 : $PageSize;
        $offset = ($PageIndex - 1) * $PageSize;
        $limit = $offset.", ".$PageSize;

        $OrderhistoryModel = M('Orderhistory');

        $list = $OrderhistoryModel->order('id asc')->limit($limit)->select();
        foreach ($list as &$row){
            $row = $this->orderHistoryWrap($row['id']);
        }
        unset($row);

        $total_list = $OrderhistoryModel->select();
        $count = count($total_list);

        $pageInfo['entities'] = $list;
        $pageInfo['total'] = $count;
        $pageInfo['pageIndex'] = $PageIndex;
        $pageInfo['pageSize'] = $PageSize;
        $pageInfo['hasNextPage'] = ($count / $PageSize - $PageIndex) <= 0 ? false : true;
        $pageInfo['hasPreviousPage'] = $PageIndex != 1 ? true : false;

        return_data(array('pageInfo'=>$pageInfo));

    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/OrderHistory/detail",
     *   tags={"OrderHistory"},
     *   summary="获取历史订单详情",
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
    public function detail(){
        is_cookie();

        $histories = array();
        $orderId = Fun::requestInt('orderId', 0);
        if (empty($orderId)) return_err('参数错误：订单id为空');

        $OrderhistoryModel = M('Orderhistory');
        $list = $OrderhistoryModel->where("RowId=".$orderId)->order("Created DESC")->select();
        foreach ($list as &$row){
            $row = $this->orderHistoryWrap($row['id']);
            if (!empty($row['changed'])){
                $histories[] = array(
                    'changes' => $this->changedWrap($row['changed']),
                    'time' => $row['created'],
                    'editor' => $this->roleNameWrap($row['editby'])
                );
            }else{
                $changeses = array();
                $beforeArray = json_decode($row['before'], true);
                $afterArray = json_decode($row['after'], true);
//                var_dump($beforeArray);
                if (!empty($beforeArray['AcceptTime'])){
                    $beforeArray['AcceptTime'] = date_format(date_create($beforeArray['AcceptTime']), 'Y-m-d h:i:s');
                }
                if (!empty($beforeArray['VisitTime'])){
                    $beforeArray['VisitTime'] = date_format(date_create($beforeArray['VisitTime']), 'Y-m-d h:i:s');
                }
                if (!empty($beforeArray['AssignTime'])){
                    $beforeArray['AssignTime'] = date_format(date_create($beforeArray['AssignTime']), 'Y-m-d h:i:s');
                }
                if (!empty($beforeArray['CompleteTime'])){
                    $beforeArray['CompleteTime'] = date_format(date_create($beforeArray['CompleteTime']), 'Y-m-d h:i:s');
                }
                if (!empty($beforeArray['LastCallTime'])){
                    $beforeArray['LastCallTime'] = date_format(date_create($beforeArray['LastCallTime']), 'Y-m-d h:i:s');
                }
                if (!empty($afterArray['AcceptTime'])){
                    $afterArray['AcceptTime'] = date_format(date_create($afterArray['AcceptTime']), 'Y-m-d h:i:s');
                }
                if (!empty($afterArray['LastCallTime'])){
                    $afterArray['LastCallTime'] = date_format(date_create($afterArray['LastCallTime']), 'Y-m-d h:i:s');
                }
                if (!empty($afterArray['VisitTime'])){
                    $afterArray['VisitTime'] = date_format(date_create($afterArray['VisitTime']), 'Y-m-d h:i:s');
                }
                if (!empty($afterArray['AssignTime'])){
                    $afterArray['AssignTime'] = date_format(date_create($afterArray['AssignTime']), 'Y-m-d h:i:s');
                }
                if (!empty($afterArray['CompleteTime'])){
                    $afterArray['CompleteTime'] = date_format(date_create($afterArray['CompleteTime']), 'Y-m-d h:i:s');
                }

                $diffArray = array_diff($afterArray, $beforeArray);
                $diffKeyArray = array_keys($diffArray);
                for ($i=0; $i< count($diffKeyArray); $i++){
                    $changeses[] = $this->OrderCommentArray[$diffKeyArray[$i]].':'.$beforeArray[$diffKeyArray[$i]].' => '.$afterArray[$diffKeyArray[$i]];
                }
                $histories[] = array(
                    'changes' => $changeses,
                    'time' => $row['created'],
                    'editor' => $this->roleNameWrap($row['editby'])
                );
            }

        }
        unset($row);

        return_data(array('histories'=>$histories));

    }

    public function changedWrap($changed){
        $changes = array();
        $changedArr = json_decode($changed, true);
        $m = $changedArr['before'];
        $n = $changedArr['after'];
        if (!empty($m['AcceptTime'])){
            $m['AcceptTime'] = date_format(date_create($m['AcceptTime']), 'Y-m-d h:i:s');
        }
        if (!empty($m['VisitTime'])){
            $m['VisitTime'] = date_format(date_create($m['VisitTime']), 'Y-m-d h:i:s');
        }
        if (!empty($m['AssignTime'])){
            $m['AssignTime'] = date_format(date_create($m['AssignTime']), 'Y-m-d h:i:s');
        }
        if (!empty($m['CompleteTime'])){
            $m['CompleteTime'] = date_format(date_create($m['CompleteTime']), 'Y-m-d h:i:s');
        }
        if (!empty($m['LastCallTime'])){
            $m['LastCallTime'] = date_format(date_create($m['LastCallTime']), 'Y-m-d h:i:s');
        }
        if (!empty($n['LastCallTime'])){
            $n['LastCallTime'] = date_format(date_create($n['LastCallTime']), 'Y-m-d h:i:s');
        }
        if (!empty($n['AcceptTime'])){
            $n['AcceptTime'] = date_format(date_create($n['AcceptTime']), 'Y-m-d h:i:s');
        }
        if (!empty($n['VisitTime'])){
            $n['VisitTime'] = date_format(date_create($n['VisitTime']), 'Y-m-d h:i:s');
        }
        if (!empty($n['AssignTime'])){
            $n['AssignTime'] = date_format(date_create($n['AssignTime']), 'Y-m-d h:i:s');
        }
        if (!empty($n['CompleteTime'])){
            $n['CompleteTime'] = date_format(date_create($n['CompleteTime']), 'Y-m-d h:i:s');
        }

        $diffArray = array_diff($n, $m);
        $diffKeyArray = array_keys($diffArray);
        for ($i=0; $i< count($diffKeyArray); $i++){
            $changes[] = $this->OrderCommentArray[$diffKeyArray[$i]].':'.$m[$diffKeyArray[$i]].' => '.$n[$diffKeyArray[$i]];
        }

        return $changes;
    }

    public function orderHistoryWrap($orderId){
        $OrderhistoryModel = M('Orderhistory');
        $info = $OrderhistoryModel->where("Deleted=0 and Id=".$orderId)->find();
        $info['id'] = Method::getInt($info['id']);
        $info['rowId'] = $info['rowid'];
        $info['tableName'] = $info['tablename'];
        $info['kind'] = Method::getInt($info['kind']);
        $info['deleted'] = Method::getInt($info['deleted']);
        unset($info['rowid'], $info['tablename']);
        return $info;
    }

    public function roleNameWrap($username){
        $roleNmae = array();
        $sql = "SELECT r.* FROM UserRoles ur LEFT JOIN users u ON ur.UserId=u.Id LEFT JOIN roles r ON ur.RoleId=r.Id WHERE u.Deleted=0 and u.Name='".$username."'";
        $list = M()->query($sql);
        foreach ($list as &$row){
           $roleNmae[] = $row['rolename'];
        }
        $roleNmaeStr = implode(',', $roleNmae);
        return $username.'('.$roleNmaeStr.')';
    }
}