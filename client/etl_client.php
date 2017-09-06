<?php
include_once '../common/common.php';
$client = new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);

$client->set(array(
    'open_eof_split' => true,
    'package_eof' => "\r\n",
    'package_max_length' => 9990000,
));

/**
 * 注册连接成功回调
 * 负责发布种子
 */
$client->on("connect", function($cli) {
    echo '－－－－－－－正在清洗数据－－－－－－－－－';
    $message = json_encode(['type' => 'Etl', 'timer' => 60, 'concurrentnum' => '50', 'etl_num' => 500, 'instructions' => 'start']);
    $cli->send($message."\r\n");
});

/**
 * 注册数据接收回调
 * 对服务器返回数据入库处理
 */
$client->on("receive", function($cli, $data){
    
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
if($client->connect($config['swoole']['swoole_path'], $config['swoole']['swoole_port3'], $config['swoole']['swoole_timeout'])){

} else {
    echo "连接服务器失败！";
}