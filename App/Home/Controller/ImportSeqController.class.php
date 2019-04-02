<?php
/**
 * Created by PhpStorm.
 * User: 13838
 * Date: 2019/2/13
 * Time: 15:05
 */
namespace Home\Controller;

use Think\Controller;

class ImportSeqController extends Controller{
    public function ImportSeq(){
        $pageInfo = array();
        is_cookie();
        $data = is_json();
        $startTime = $data['startTime'];
        $endTime = $data['endTime'];
        $pageIndex = $data['pageIndex'];
        $pageSize = $data['pageSize'];

        $where = "Deleted=0";
        if (!empty($startTime)) $where .= " and ImportTime >= '".$startTime."'";
        if (!empty($endTime)) $where .= " and ImportTime <= '".$endTime."'";

        $pageIndex = empty($pageIndex) ? 1 : $pageIndex;
        $pageSize = empty($pageSize) ? 5 : $pageSize;
        $offset = ($pageIndex - 1) * $pageSize;

        $ImportSeqModel = M('importsequences');
        $total=$ImportSeqModel->where($where)->count();
        $list = $ImportSeqModel->where($where)->order("Id asc")->limit($offset, $pageSize)->select();

        foreach ($list as &$row){
            $row['businessId'] = $row['businessid'];
            $row['importTime'] = $row['importtime'];
        }
        unset($row);
        if ($pageIndex > 1 && $total > 0) {
            $hasPreviousPage = true;
        } else {
            $hasPreviousPage = false;
        }
        if (($total / $pageSize) > $pageIndex) {
            $hasNextPage = true;
        } else {
            $hasNextPage = false;
        }
        $pageInfo['entities'] = $list;
        $pageInfo['total'] = intval($total);
        $pageInfo['pageIndex'] = $pageIndex;
        $pageInfo['pageSize'] = $pageSize;
        $pageInfo['hasNextPage'] = $hasNextPage;
        $pageInfo['hasPreviousPage'] = $hasPreviousPage;

        return_data(array('pageInfo'=>$pageInfo));
    }
}