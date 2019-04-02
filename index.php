<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

//检测操作系统，定义换行符
$OS = strtoupper(substr(PHP_OS,0,3));
if($OS == 'WIN'){//WIN服务器
    $wrap_symbol = "\r\n";
}else{//直接指向linux服务器
    $wrap_symbol = "\n";
} 
define('U_OS',$OS);
define('U_WRAP_SYMBOL',$wrap_symbol);

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',false);

// 定义应用目录
define('APP_PATH','./App/');

// 引入ThinkPHP入口文件
require './Think/ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单