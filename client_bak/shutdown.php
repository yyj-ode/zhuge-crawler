<?php
include_once '../common/common.php';
$client = new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);

$client->set(array(
    'open_eof_check' => true,
    'package_eof' => "\r\n",
    'package_max_length' => 1024 * 1024 * 2,
));

/**
 * 注册连接成功回调
 * 负责发布种子
 */
$client->on("connect", function($cli) {
    $cli->send("shutdown\r\n");
});

/**
 * 注册数据接收回调
 * 对服务器返回数据做入队处理
 */
$client->on("receive", function($cli, $data){
    echo $data;
    $cli -> close();
});

/**
 * 注册连接失败回调
 * 终端中断监控
 */
$client->on("error", function($cli){
    echo "Connect failed\n";
});

/**
 * 注册连接关闭回调
 */
$client->on("close", function($cli){
    echo "Connection close\n";
});

//发起连接
if($client->connect($config['swoole']['swoole_path'], $config['swoole']['swoole_port'], $config['swoole']['swoole_timeout'])){
//                $client->send("data");
} else {
    echo "连接服务器失败！";
}
