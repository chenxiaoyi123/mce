<?php

namespace Home\Controller;

use Think\Controller;
use Think\Log;

header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求  

header("Content-Type: application/json; charset=utf-8");
class AccountsController extends Controller
{
    public function test(){
        $str='{
    "pageData":{
        "pf":{
            "msgList":[
                {
                    "moduleId":"cm1001",
                    "phoneNum":"18512128002",
                    "content":"测试"
                }
            ]
        }
    }
}';
        $msgList=array(
            "moduleId"=>"cm1001",
            "phoneNum"=>"18512128002",
            "content"=>"测试"
        );
        $data['pageData']['pf']['msgList'][]=$msgList;
    $data=json_encode($data);
       // $data='{"pageData":{"pf":{"msgList":[{"moduleId":"cm1001","phoneNum":"18512128002","content":"测试"}]}}}';
        $url='http://www.51186.com.cn/vflymsg/message/invoke/MessageDispatch.json?token=6BAEF815D480A81DEE8FA68020E830CE&source=chengmei&service=postRealMsg';
        $return_data=doPost($url,$data);
        print_r($return_data);die();
    }
    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Accounts/signin",
     *   tags={"Accounts"},
     *   summary="登录",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="username",
     *     in="query",
     *     description="用户登录账号",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     description="登录密码",
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
    public function signin()
    {
        $dataarr = is_json();
        $loginName = $dataarr['loginName'];
        $password = $dataarr['password'];
        if (empty($loginName)) {
            return_err('请输入账号');
        }
        $where['UserName'] = array('eq', $loginName);
		$where['Phone'] = array('eq',$loginName);
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
        $map['Review'] = 1;
        $map['Deleted'] = 0;
        $user = M('users');
        $userinfo = $user->field('password,id,name,username,retry')->where($map)->find();
        if ($userinfo == false) {
            return_err('用户名不存在');
        } else {

            if ($userinfo['retry'] > 5) {
                Log::record('被锁定账号' . $loginName);
                return_err('该账户已经被锁定');
            }
            if ($userinfo['password'] != "$password") {
                $user->where($map)->setInc('Retry');//密码错误次数加一
                return_err('用户名或密码错误');
            }
            cookie('username', $userinfo['username'], 60 * 60 * 24); // 指定cookie保存时间
            $user->where($map)->setField(array('Retry' => 0));//登录成功错误次数清空
            $userroles = M("userroles");
            $rolemap['userroles.UserId'] = $userinfo['id'];
            $rolemap['userroles.Deleted'] = 0;
            $rolemap['roles.Deleted'] = 0;
            $list = $userroles->join('RIGHT JOIN roles ON userroles.RoleId = roles.Id')->field('rolename')->where($rolemap)->select();
            $str = '';
            foreach ($list as $value => &$v) {
                $str .= empty($str) ? $v['rolename'] : ',' . $v['rolename'];
            }
            unset($v);
            $return_data['user'] = array(
                'id' => $userinfo['id'],
                'name' => $userinfo['name'],
                'roles' => $str,
                'userName' => $userinfo['username'],
            );
            return_data($return_data);
        }
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Accounts/logout",
     *   tags={"Accounts"},
     *   summary="登出",
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
    public function logout()
    {
        is_cookie();
        $username = is_cookie();
        Log::record('退出账号cookie：' . $username);
        cookie('name', null);
        return_data('success');
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Accounts/signup",
     *   tags={"Accounts"},
     *   summary="注册",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="userName",
     *     in="query",
     *     description="用户登录账号",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     description="登录密码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     description="用户姓名",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     description="用户手机号",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="address",
     *     in="query",
     *     description="用户地址",
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
    public function signup()
    {
        $dataarr = is_json();
        $userName = $dataarr['userName'];
        $password = $dataarr['password'];
        $phone = $dataarr['phone'];
        $address = $dataarr['address'];
        $name = $dataarr['name'];
        if (empty($userName) || empty($password) || empty($phone) || empty($address) || empty($name)) {
            return_err_500('Index was out of range. Must be non-negative and less than the size of the collection.\r\nParameter name: index');
        }
        $user = M('users');
        $where1 = array(
            'Deleted' => '0',
            'UserName' => "$userName"
        );
        $where2 = array(
            'Name' => "$name",
        );
        $where3 = array(
            'Phone' => "$phone",
        );
        $where = array($where1, $where2, $where3, '_logic' => 'or');
        $userinfo = $user->field('id')->where($where)->find();
        if ($userinfo) {
            return_err('用户名、姓名或手机已被占用');
        } else {
            $lat_lng = $this->LbsMap($address);
            $data['UserName'] = "$userName";
            $data['Name'] = "$name";
            $data['Password'] = "$password";
            $data['Phone'] = "$phone";
            $data['Address'] = "$address";
            $data['Lat'] = $lat_lng[0];
            $data['Lng'] = $lat_lng[1];
            $add = $user->add($data);
            if ($add == false) {
                return_err('注册失败');
            }
            $userrole = M('userroles');
            $new_userinfo = $user->field('id')->where($where1)->find();
            $uid = $new_userinfo['id'];
            $rolesmap['UserId'] = $uid;
            $rolesmap['RoleId'] = 3;
            $roleadd = $userrole->add($rolesmap);
            if ($roleadd == false) {
                return_err('注册失败');
            }
            return_data($new_userinfo);
        }
    }

    public function LbsMap($address)
    {
        $url = 'https://restapi.amap.com/v3/place/text?key=4a9acf4ec92ff9a5f4c99bb6006503b7&keywords=' . $address . '&city=上海';
        $data = doGet($url);
        $dataarr = json_decode($data, true);
        $location = $dataarr['pois'][0]['location'];
        $lat_lng = explode(',', $location);
        return $lat_lng;
    }

    /**
     * @SWG\Post(path="/orderschedule/index.php/Home/Accounts/changePassword",
     *   tags={"Accounts"},
     *   summary="修改密码",
     *   description="",
     *   operationId="loginUser",
     *   produces={"application/json", "application/json"},
     *   @SWG\Parameter(
     *     name="originPassword",
     *     in="query",
     *     description="旧密码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="newPassword",
     *     in="query",
     *     description="新密码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="rePassword",
     *     in="query",
     *     description="确认新密码",
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
    public function changePassword()
    {
        $username = is_cookie();
        $dataarr = is_json();
        $user = M('users');
        $where = array(
            'Deleted' => '0',
            'UserName' => "$username");
        $userinfo = $user->field('password')->where($where)->find();
        if ($userinfo == false) {
            return_err('用户不存在');
        }
        $Password = $userinfo['password'];
        $originPassword = $dataarr['originPassword'];
        $newPassword = $dataarr['newPassword'];
        $rePassword = $dataarr['rePassword'];
        if ($newPassword != $rePassword) {
            return_err('两次输入密码不一致');
        }
        if ($originPassword != $Password) {
            return_err('旧密码输入错误');
        }
        $update = $user->where($where)->setField(array('Password' => "$rePassword"));
        if ($update == false) {
            return_err('更改失败，请重试');
        }
        return_data('suceess');
    }
}