<?php
/**
 * Created by PhpStorm.
 * User: Tony
 * Date: 16/3/23
 * Time: 下午8:13
 */
include_once '../common/common.php';

$client = new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);
$client->set(array(
    'open_eof_split' => true,
    'package_eof' => "\r\n",
    'package_max_length' => 81920,
));

/**
 * 注册连接成功回调
 * 负责发布种子
 */
$client->on("connect", function($cli){

});

/**
 * 注册数据接收回调
 * 对服务器返回数据做入队处理
 */
$client->on("receive", function($cli, $data){
    echo $data;
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


$redis = getRedisInit();
$redis->sub($_SERVER['config']['redis']['redisChannel'], 'handleServer');

function handleServer($instance, $channelName, $message){
    switch($message){
        case 'Grab' :  //爬取器
            $port = $_SERVER['config']['swoole']['swoole_port'];
            break;
        case 'Crawling' :
            $port = $_SERVER['config']['swoole']['swoole_port2'];
            break;
        case 'Etl' :
            $port = $_SERVER['config']['swoole']['swoole_port3'];
            break;
        default :
            $port = '';
            break;
    }
    if(!empty($port)){
        //发起连接
        if($client->connect($_SERVER['config']['swoole']['swoole_path'], $port, $_SERVER['config']['swoole']['swoole_timeout'])){
//                $client->send("data");
        } else {
            echo "连接服务器失败！";
        }
    }

}



