<?php
return array(
    //'配置项'=>'配置值'
    /* 数据库设置 */
    'DB_TYPE'               =>  'mysqli',     // 数据库类型
    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_NAME'               =>  'orderschedule',          // 数据库名
    'DB_USER'               =>  'cy',      // 用户名
    'DB_PWD'                =>  'cy987321',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  '',    // 数据库表前缀
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8

    //爬虫数据库配置
    'CRAWLER_DB_NAME'       => 'pachong',
    'CRAWLER_TABLE'         => 'order_send'
);