<?php
return  [
    'swoole' => [
        'swoole_path' => '127.0.0.1',  //远程服务器的地址
        'swoole_port' => '9501', //远程服务器端口 爬取器
        'swoole_port2' => '9502', //远程服务器端口 抓取器
        'swoole_timeout' => 0.1, //是网络IO的超时，包括connect/send/recv，单位是s，支持浮点数。默认为0.1s，即100ms
    ],

    /*
     * 自定义主服务进程名ID
     */
    'SERVER_MASTER_PROCESS_ID' => 'crawler-master',
    /*
     * 自定义 WORKER-TASK 服务进程名ID前缀
     */
    'SERVER_WORKER_PROCESS_ID' => 'autoassign-server-worker-id-',




    /*
     * redis 服务ip
     */
    'REDIS_SERVER_IP' => '16c51b2287ed4bd2.m.cnbja.kvstore.aliyuncs.com',

    /*
     * redis 端口
     */
    'REDIS_SERVER_PORT' => '6379',

    /*
     * redis 认证
     */
    'REDIS_AUTH' => 'zhugeZHAOFANG1116',

    /*
     * redis 数据库
     */
    'REDIS_DB' => '2',

    /*
     * Redis 服务运行状态
     */
    '_REDIS_SERVER_RUN_STATUS_' => '_REDIS_SERVER_RUN_STATUS_',

    /*
     * 服务监听地址（默认 0.0.0.0）
     */
    'SERVER_LISTEN_IP' => '0.0.0.0',

    /*
     * 服务监听端口（默认 9501）
     */
    'SERVER_LISTEN_PORT' => 9501,

    /*
     * 设置启动的worker进程数
     * 业务代码是全异步非阻塞的，这里设置为CPU的1-4倍最合理
     * 业务代码为同步阻塞，需要根据请求响应时间和系统负载来调整
     * 比如1个请求耗时100ms，要提供1000QPS的处理能力，那必须配置100个进程或更多。
     * 但开的进程越多，占用的内存就会大大增加，而且进程间切换的开销就会越来越大。
     * 所以这里适当即可。不要配置过大。
     */
    'WORKER_NUM' => 2,

    /*
     * 配置task进程的数量
     */
    'TASK_WORKER_NUM' => 8,

    /*
     * 守护进程化
     * 设置daemonize => 1时，程序将转入后台作为守护进程运行。长时间运行的服务器端程序必须启用此项。
     * 如果不启用守护进程，当ssh终端退出后，程序将被终止运行。
     * 启用守护进程后，标准输入和输出会被重定向到 log_file
     * 如果未设置log_file，将重定向到 /dev/null，所有打印屏幕的信息都会被丢弃
     */
    'DAEMONIZE' => false,

    /*
     * 设置worker进程的最大任务数
     */
    'MAX_REQUEST' => 1000,

    /*
     * 定时器 单位秒
     */
    'TIMER_INTERVAL' => 6,

    /*
     * 日志文件
     */
    'LOG_FILE' => './autoassign-server.log',





    'CrawlerSource' => [   //爬虫源
        'status' => true, //如果是true则设置爬取哪几个房源
        'content' => [
            'Lianjia',
        ],
    ],

    'mysql' => [
        'host' => '123.57.61.107', //地址
        'user' => 'root', //用户
        'pwd' => 'zhuge1116',    //密码
        'dbname' => 'test', //数据库
        'table' => 'test', //表名
    ],
];