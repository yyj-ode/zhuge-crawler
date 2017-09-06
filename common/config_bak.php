<?php
return  [
    'swoole' => [
        'swoole_path' => '127.0.0.1',  //远程服务器的地址
        'swoole_port' => '9501', //远程服务器端口 爬取器
        'swoole_port2' => '9502', //远程服务器端口 抓取器
        'swoole_port3' => '9503', //ETL清洗端口
        'swoole_timeout' => 0.1, //是网络IO的超时，包括connect/send/recv，单位是s，支持浮点数。默认为0.1s，即100ms
    ],
    'redis' => [
//        'server' => '127.0.0.1',  //远程服务器地址
//        'port' => '6379',        //端口
//        'auth' => 'zhuge1116',   //用户
//        'db' => 2,    //redis库
        'server' => '16c51b2287ed4bd2.m.cnbja.kvstore.aliyuncs.com',  //远程服务器地址
        'port' => '6379',        //端口
        'auth' => 'zhugeZHAOFANG1116',   //认证
        'db' => 2,    //redis库

        'redisChannel' => 'channel', //redis订阅频道 发布命令
    ],

    'CrawlerSource' => [   //爬虫源
        'status' => true, //如果是true则设置爬取哪几个房源
        'content' => [
            'beijing/Lianjia',
            'beijing/Zhongyuan',
            'beijing/Wiwj',
            'beijing/QfangAction',
            'beijing/Mai',
            'beijing/Landzestate',
            'beijing/Kufang',
            'beijing/Iwjw',
            'beijing/Fang',
        ],
    ],

    'mysql' => [
        'host' => '123.57.61.107', //地址
        'user' => 'root', //用户
        'pwd' => 'zhuge1116',    //密码
        'dbname' => 'test', //数据库
        'table' => 'test', //表名
    ],

    'source' => [
        'Fang' => 9,
        'Iwjw' => 6,
        'Kufang' => 3,
        'Landzestate' => 8,
        'Lianjia' => 1,
        'Mai' => 7,
        'Qfang' => 5,
        'QfangAction' => 5,
        'Wiwj' => 4,
        'Zhongyuan' => 2,
    ],

    'ETL_API_URL' => 'http://123.57.76.91:8000/Sell/ETLSell/run?params=%27%27', //ETL清洗APi
];