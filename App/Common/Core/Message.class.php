<?php
/**
 * Created by PhpStorm.
 * User: 97558
 * Date: 2018/11/28
 * Time: 9:43
 */

namespace Common\Core;

use Think\Log;

define('AppId', 'wx7d0494284af1af9c');
define('AppSecret', 'cb4eaa39f11be828d8241ec877ce7009');
define('url', 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=');

class Message
{
    /*王帅您好，您的账户余额发生变动，信息如下。
    账户类型：消费返利账户
    操作类型：订单支付抵扣金额
    操作内容：订单2015163443230008
    变动额度：-205
    账户余额：568
    如对上述余额变动有异议，请联系客服人员协助处理。*/
    public function AccountBalance($messge)
    {//账户余额变动通知
        $access_token = $this->Getaccess_token();
        $url = url . $access_token;
        $data = '{
           "touser":"' . $messge['openid'] . '",
           "template_id":"v7DRwEQuZa_rY832K3mcPl2Z66mqkWMgBV83EQfUdsk",
           "url":"' . $messge['url'] . '",  
           "data":{
                   "first": {
                       "value":"' . $messge['first'] . '",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $messge['level'] . '",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"' . $messge['type'] . '",
                       "color":"#173177"
                   },
                   "keyword3": {
                       "value":"' . $messge['content'] . '",
                       "color":"#173177"
                   },
                    "keyword4": {
                       "value":"' . $messge['change_money'] . '",
                       "color":"#173177"
                   },
                    "keyword5": {
                       "value":"' . $messge['balance'] . '",
                       "color":"#173177"
                   },
                   "remark":{
                       "value":"' . $messge['remark'] . '",
                       "color":"#173177"
                   }
           }
       }';
        $d = $this->doPost($url, $data);
        $darray = json_decode($d);
        return $darray;
    }

    /*您好，您购买的玩具熊已经完成核销自提。
    核销时间：2016-06-21 15:00:00
    核销名称：玩具小熊
    核销总额：100
    祝您生活愉快!*/
    public function Verification($messge)
    {//核销成功通知
        $access_token = $this->Getaccess_token();
        $url = url . $access_token;
        $data = '{
           "touser":"' . $messge['openid'] . '",
           "template_id":"wcCS6OHpwmESOyOp1F_NuBbZIHZmOyTmPz7ics3F-ww",
           "url":"' . $messge['url'] . '",  
           "data":{
                   "first": {
                       "value":"' . $messge['first'] . '",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $messge['time'] . '",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"' . $messge['name'] . '",
                       "color":"#173177"
                   },
                   "keyword3": {
                       "value":"' . $messge['money'] . '",
                       "color":"#173177"
                   },
                   "remark":{
                       "value":"' . $messge['remark'] . '",
                       "color":"#173177"
                   }
           }
       }';
        $d = $this->doPost($url, $data);
        $darray = json_decode($d);
        return $darray;
    }

    /*您的订单已支付成功。 >>查看订单详情
    用户名：123456789@qingpinji.com
    订单号：2015698571200
    订单金额：￥98.80
    商品信息：星冰乐（焦糖味） 家乐氏香甜玉米片*2 乐天七彩爱情糖*3
    如有问题请致电xxx客服热线400-8070028或直接在微信留言，客服在线时间为工作日10:00——18:00.客服人员将第一时间为您服务。*/
    public function PaySuccessful($order_data)
    {//订单支付成功通知
        $access_token = $this->Getaccess_token();
        $url = url . $access_token;
        $data = '{
           "touser":"' . $order_data['openid'] . '",
           "template_id":"Xw3Wv8XPgjowvBzRhGaBz5zdi2cfh9AXovsEApKC3Mw",
           "url":"' . $order_data['url'] . '",  
           "data":{
                   "first": {
                       "value":"' . $order_data['first'] . '",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $order_data['username'] . '",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"' . $order_data['order_no'] . '",
                       "color":"#173177"
                   },
                   "keyword3": {
                       "value":"' . $order_data['money'] . '",
                       "color":"#173177"
                   },
                    "keyword4": {
                       "value":"' . $order_data['content'] . '",
                       "color":"#173177"
                   },
                   "remark":{
                       "value":"' . $order_data['remark'] . '",
                       "color":"#173177"
                   }
           }
       }';
        $d = $this->doPost($url, $data);
        $darray = json_decode($d);
        return $darray;
    }

    /*您已预订成功
    订单编号：2018051274100001
    预订金额：2.00
    预订时间：2018-05-21 14:26:48
    如有疑问，请联系微信客服*/
    public function BookingSuccessful($order_data)
    {//预订成功通知
        $access_token = $this->Getaccess_token();
        $url = url . $access_token;
        $data = '{
           "touser":"' . $order_data['openid'] . '",
           "template_id":"XcWfY3ErdTmLtLx_gkONYLb_WQs4DI9RNszdaLBtu9Y",
           "url":"' . $order_data['url'] . '",  
           "data":{
                   "first": {
                       "value":"' . $order_data['first'] . '",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $order_data['order_no'] . '",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"' . $order_data['money'] . '",
                       "color":"#173177"
                   },
                   "keyword3": {
                       "value":"' . $order_data['time'] . '",
                       "color":"#173177"
                   },
                    "remark":{
                       "value":"' . $order_data['remark'] . '",
                       "color":"#173177"
                   }
           }
       }';
        $d = $this->doPost($url, $data);
        $darray = json_decode($d);
        return $darray;
    }

    /*
    校园大使申请结账
    申请人：小明
    申请理由：圆满完成季度kpi
    请尽快处理*/
    public function ApplyPayment($apply_data)
    {//会员申请离店
        $access_token = $this->Getaccess_token();
        $url = url . $access_token;
        $data = '{
           "touser":"' . $apply_data['openid'] . '",
           "template_id":"2PUdSCpbWUAbc-TnpyBWQBsktq43_B1zcIv3I-xfdzE",
           "url":"' . $apply_data['url'] . '",  
           "data":{
                   "first": {
                       "value":"' . $apply_data['first'] . '",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $apply_data['roomname_username'] . '",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"' . $apply_data['excuse'] . '",
                       "color":"#173177"
                   },
                    "remark":{
                       "value":"' . $apply_data['remark'] . '",
                       "color":"#173177"
                   }
           }
       }';
        $d = $this->doPost($url, $data);
        $darray = json_decode($d);
        return $darray;
    }

    /*尊敬的用户，您的订单处于未付款状态，交易即将关闭，请您尽快处理
    订单号：1234567880
    商品名称：韩国通用
   支付金额：578
   感谢您对环球漫游的支持！如有问题请及时点击【在线咨询】*/
    public function OrderAwait($order_data)
    {//用户待支付订单
        $access_token = $this->Getaccess_token();
        $url = url . $access_token;
        $data = '{
           "touser":"' . $order_data['openid'] . '",
           "template_id":"d0UPILkob1Q6qGvBTOb-dAXzeTDMQJUjIVLgL63lYo4",
           "url":"' . $order_data['url'] . '",  
           "data":{
                   "first": {
                       "value":"' . $order_data['first'] . '",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $order_data['order_no'] . '",
                       "color":"#173177"
                   },
                   "keyword2": {
                       "value":"' . $order_data['roomname'] . '",
                       "color":"#173177"
                   },
                   "keyword3": {
                       "value":"' . $order_data['money'] . '",
                       "color":"#173177"
                   },
                   "remark":{
                       "value":"' . $order_data['remark'] . '",
                       "color":"#173177"
                   }
           }
       }';
        $d = $this->doPost($url, $data);
        $darray = json_decode($d);
        return $darray;
    }

    private function doPost($url, $post_data, $header = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        // 请求头，可以传数组
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HEADER, $header);
        }
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function doGet($url)
    {//初始化

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

    public function Getaccess_token()
    {
        $appid = AppId;
        $secret = AppSecret;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret;
        $json = $this->doGet($url);
        $array = json_decode($json);
        $access_token = $array->access_token;
        log::record("access_token:" . "$access_token");
        return $access_token;
    }
}