<?php
return  [
    'swoole' => [
        'swoole_path' => '127.0.0.1',  //远程服务器的地址
        'swoole_port' => '9501', //远程服务器端口 爬取器
        'swoole_port2' => '9502', //远程服务器端口 抓取器
        'swoole_port3' => '9503', //ETL清洗端口
        'swoole_port4' => '50005', //远程服务器端口 抓取器
        'swoole_timeout' => 0.1, //是网络IO的超时，包括connect/send/recv，单位是s，支持浮点数。默认为0.1s，即100ms
    ],
     'alipayredis' => [
         'server' => '16c51b2287ed4bd2.m.cnbja.kvstore.aliyuncs.com',  //远程服务器地址
         'port' => '6379',        //端口
         'auth' => 'zhugeZHAOFANG1116',   //认证
         'db' => 7,    //redis库

         'redisChannel' => 'channel', //redis订阅频道 发布命令
     ],

   'alizhonyunredis' => [
       'server' => '101.200.81.152',  //远程服务器地址
       'port' => '6380',        //端口
       'auth' => 'zhugeZHAOFANG1116',   //认证
       'db' => 7,    //redis库

       'redisChannel' => 'channel', //redis订阅频道 发布命令
   ],

    'redis' => [
        'server' => '127.0.0.1',  //远程服务器地址
        'port' => '6379',        //端口
//        'auth' => 'zhugeZHAOFANG1116',   //认证
        'db' => 7,    //redis库

        'redisChannel' => 'channel', //redis订阅频道 发布命令
    ],

    'CrawlerSource' => [   //爬虫源
        'status' => true, //如果是true则设置爬取哪几个房源
        'content' => [
//            'beijing/DingdingHezu',
            'beijing/DingdingRent',
//            'beijing/Fang',
//            'beijing/FangHezu',
//            'beijing/FangRent',
//            'beijing/FangzhuHezu',
            'beijing/FangzhuRent',
            'beijing/Five8Personal',
            'beijing/Five8PersonalRent',
            'beijing/Iwjw',
            'beijing/Kufang',
        	'beijing/FangDeal',
//            'beijing/KufangHezu',
            'beijing/Landzestate',
            'beijing/LandzestateRent',
//            'beijing/LianjiaRent',
            'beijing/Mai',
            'beijing/MaiRent',
            'beijing/Qfang',
//            'beijing/QfangHezu',
            'beijing/QfangRent',
            'beijing/Wiwj',
//            'beijing/WiwjHezu',
            'beijing/WiwjRent',
            'beijing/Zhongyuan',
            'beijing/ZhongyuanRent',
//            'beijing/ZiroomHezu',
            'beijing/ZiroomRent',
            'beijing/Lianjia',
//            'beijing/HaizhuHezu',
            'beijing/HaizhuRent',
//            'beijing/LianjiaWap',
            'beijing/LianjiaRentWap',
            'beijing/IwjwRent',
//
//            'shanghai/ZiroomHezu',
            'shanghai/Zhongyuan',
//            'shanghai/YujianHezu',
//            'shanghai/WiwjRent',
//            'shanghai/WiwjHezu',
            'shanghai/Wiwj',
//            'shanghai/QingkeHezu',
//            'shanghai/QfangRent',
//            'shanghai/QfangHezu',
            'shanghai/Qfang',
            'shanghai/Lianjia',
//            'shanghai/KyjRent',
//            'shanghai/KyjHezu',
//            'shanghai/KufangRent',
            'shanghai/Kufang',
//            'shanghai/IwjwRent',
            'shanghai/Iwjw',
            'shanghai/Hanyu',
//            'shanghai/Five8PersonalRent',
//            'shanghai/Five8PersonalHezu',
            'shanghai/Five8Personal',
            'shanghai/Fdd',
//            'shanghai/DingdingRent',
//            'shanghai/DingdingHezu',
            'shanghai/Angejia',
//            'shanghai/Fang',
//
//            'shenzhen/Fang',
////            'shenzhen/FangHezu',
////            'shenzhen/FangRent',
//            'shenzhen/FangzhuHezu',
//            'shenzhen/FangzhuRent',
//            'shenzhen/Fdd',
//            'shenzhen/Five8Personal',
//            'shenzhen/Five8PersonalHezu',
//            'shenzhen/Five8PersonalRent',
//            'shenzhen/Iwjw',
//            'shenzhen/IwjwRent',
//            'shenzhen/KyjHezu',
//            'shenzhen/KyjRent',
//            'shenzhen/Lianjia',
//            'shenzhen/LianjiaRent',
//            'shenzhen/MLwuye',
//            'shenzhen/MLwuyeRent',
//            'shenzhen/Qfang',
//            'shenzhen/QfangHezu',
//            'shenzhen/QfangRent',
////            'shenzhen/Zczdc',
////            'shenzhen/ZczdcRent',
//            'shenzhen/Zhongyuan',
//            'shenzhen/ZhongyuanRent',
//            'shenzhen/ZiroomHezu',
//            'shenzhen/ZiroomRent',
//            'shenzhen/jjshome',
//            'shenzhen/jjshomeRent',
//
            'nanjing/ZhongyuanRent',
            'nanjing/Zhongyuan',
            'nanjing/WiwjRent',
            'nanjing/WiwjHezu',
            'nanjing/QfangRent',
            'nanjing/QfangHezu',
            'nanjing/Qfang',
            'nanjing/LianjiaRent',
//            'nanjing/Lianjia',
            'nanjing/KyjRent',
            'nanjing/KyjHezu',
            'nanjing/Iwjw',
            'nanjing/Five8PersonalRent',
            'nanjing/Five8PersonalHezu',
            'nanjing/Five8Personal',
            'nanjing/Fdd',
            'nanjing/FangzhuRent',
//            'nanjing/FangzhuHezu',
//            'nanjing/FangRent',
//            'nanjing/FangHezu',
//            'nanjing/Fang',
            'nanjing/DingdingRent',
            'nanjing/Wiwj',
//
//            'guangzhou/ZhongyuanRent',
//            'guangzhou/Zhongyuan',
//            'guangzhou/QfangRent',
//            'guangzhou/QfangHezu',
//            'guangzhou/Qfang',
//            'guangzhou/LianjiaRent',
//            'guangzhou/Lianjia',
//            'guangzhou/IwjwRent',
//            'guangzhou/Iwjw',
//            'guangzhou/Five8PersonalRent',
//            'guangzhou/Five8PersonalHezu',
//            'guangzhou/Five8Personal',
//            'guangzhou/Fdd',
//            'guangzhou/FangzhuRent',
//            'guangzhou/FangzhuHezu',
//            'guangzhou/FangRent',
//            'guangzhou/FangHezu',
//            'guangzhou/Fang',
	 ],
    ],

    'mysql' => [
        'host' => '101.200.81.152', //地址
        'post' => '3307',
        'user' => 'zhuge_online', //用户
        'pass' => '1hhf942c*$199b0dzc25%%$$8b622c%',    //密码
        'beijing_dbname' => 'spider', //北京数据库
        'shanghai_dbname' => 'spider_sh', //上海数据库
        'guangzhou_dbname' => 'spider_gz', //广州数据库
        'shenzhen_dbname' => 'spider_sz', //深圳数据库
        'nanjing_dbname' => 'spider_nj', //南京数据库
    	'tianjin_dbname' => 'spider_tj', //天津数据库
    	'haerbin_dbname' => 'spider_heb', //哈尔滨数据库
        'table' => 'test', //表名
    ],

    'concurrentnums' => [  //不同渠道设置不同的并发数
        'beijing/Lianjia' => 100,
        'beijing/Qfang' => 50,
        'beijing/Zhongyuan' => 50,
        'default' => 50,
    ],

    'sendMailCity' => [ //统计报表显示城市设置
        'beijing',
    ],

    'concurrent_number' => 20, //ippingbi并发数
    
    'sourecall' => '_all',

    'source_url_tag' => '(@#$%^&*(|||)',   //自定义渠道url时的标示为

//    'ETL_API_URL' => 'http://123.57.76.91:8000/Sell/ETLSell/run', //ETL清洗APi
    'ETL_API_URL' => 'http://123.57.76.91:8000/Etl/ETL/run', //ETL清洗APi
    'proxy_pass' => 'proxy.zhugefang'
];
