<?php
/**
 * Created by PhpStorm.
 * User: Tony
 * Date: 16/3/23
 * Time: 下午8:13
 */
include_once '../common/common.php';

$redis = getRedisInit();
$redis->sub([$_SERVER['config']['redis']['redisChannel']], 'handleServer');

['ip' => '101.102.323.23', 'type' => 'Grab', 'concurrentnum' => '1000', 'instructions' => 'start|stop', 'setmod' => 'time|minute', 'value' => '23:00|60'];

function handleServer($instance, $channelName, $message){
    $ip = serverIP();
    $message = json_encode(['ip' => '192.168.1.7', 'type' => 'Grab', 'concurrentnum' => '10000', 'instructions' => 'start']);
    $messagedata = json_decode($message, true);
    if($ip == $messagedata['ip'] || $messagedata['ip'] == '0.0.0.0'){
        switch($messagedata['type']){
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
                if($GLOBALS['messagedata']['instructions'] == 'stop'){
                    //将该服务状态置为停止
                    resetServerStatus($GLOBALS['messagedata']['type'], 'stop');
                }else{
                    //将该服务状态置为开启
                    resetServerStatus($GLOBALS['messagedata']['type'], 'start');
                }
                $jsond = [
                    'type' => $GLOBALS['messagedata']['type'],
                    'instructions' => $GLOBALS['messagedata']['instructions'],
                    'setmod' => $GLOBALS['messagedata']['setmod'],
                    'value' => $GLOBALS['messagedata']['value'],
                    'concurrentnum' => $GLOBALS['messagedata']['concurrentnum'],
                ];
                $cli->send(json_encode($jsond)."\r\n");
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
            //发起连接
            if(!$client->connect($_SERVER['config']['swoole']['swoole_path'], $port, $_SERVER['config']['swoole']['swoole_timeout'])){
                echo "连接服务器失败！";
            }
        }
    }
}