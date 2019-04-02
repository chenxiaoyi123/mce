<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2019/1/9
 * Time: 14:50
 */

namespace Home\Controller;

use Common\Core\Fun;
use Common\Core\Method;
use Think\Controller;
use Think\Log;

class ExpressContrastController extends Controller
{
    /**
     * @SWG\Get(path="/orderschedule/index.php/Home/ExpressContrast/obtainAll",
     *   tags={"ExpressContrast"},
     *   summary="获取所有派送员和联通系统对照关系",
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
    public function obtainAll_bak()
    {
        $crawlerconfig = M('crawlerconfigs');
        $map['Deleted'] = 0;
        $list = $crawlerconfig->where($map)->select();
        $return_data['crawlerConfigs'] = $list;
        return_data($return_data);
    }

    public function obtainAll()
    {
        $expresscontrasts = M('expresscontrasts');
        $map['Deleted'] = 0;
        $list = $expresscontrasts->where($map)->select();
        foreach ($list as &$row){
            $row['id'] = Method::getInt($row['id']);
            $row['deleted'] = Method::getInt($row['deleted']);
            $row['userId'] = Method::getInt($row['userid']);
            unset($row['userid']);
        }
        unset($row);
        $return_data['expressContrasts'] = $list;
        return_data($return_data);
    }

    public function detail(){
        $id = Fun::requestInt('id');
        if (empty($id)) return_err('参数错误，id为空');

        $expresscontrasts = M('expresscontrasts');
        $info = $expresscontrasts->where("Deleted=0 and Id=".$id)->find();
        $info['id'] = Method::getInt($info['id']);
        $info['deleted'] = Method::getInt($info['deleted']);
        $info['userId'] = Method::getInt($info['userid']);
        unset($info['userid']);
        return_data(array('expressContrast'=>$info));
    }
    public function add(){
        $data = is_json();
        $adddata['UserId']=$data['UserId'];
        $adddata['Delivery']=$data['Delivery'];
        $expresscontrasts = M('expresscontrasts');
        $add=$expresscontrasts->add($adddata);
        if($add){
            return_data('success');
        }
        else{
            return_err('添加失败');
        }
    }
    public function update(){
        $data = is_json();
        $id = intval($data['id']);
        if (empty($id)) return_err('参数错误，id为空');

        $jsonPatchDocument = $data['jsonPatchDocument'];
        $return_data = json_patch($jsonPatchDocument);

        $expresscontrasts = M('expresscontrasts');
        $res = $expresscontrasts->where("Deleted=0 and Id=".$id)->save($return_data);
        if ($res == false){
            return_err('更新失败');
        }

        return_data('success');
    }

    public function delete(){
        $data = is_json();
        $id = intval($data['id']);
        if (empty($id)) return_err('参数错误，id为空');

        $expresscontrasts = M('expresscontrasts');
        $res = $expresscontrasts->where("Deleted=0 and Id=".$id)->save(array('Deleted'=>$id));
        if ($res == false){
            return_err('删除失败');
        }

        return_data('success');
    }

}