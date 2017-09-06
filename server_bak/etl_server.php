<?php
include_once '../common/common.php';
/**
 * 创建Server对象
 * 监听 127.0.0.1:9501端口
 */
$serv = new swoole_server($config['swoole']['swoole_path'], $config['swoole']['swoole_port3']);

$serv->set(array(
    'reactor_num' => 4,
    'worker_num' => 8,    //worker process num
    'task_worker_num' =>8,
    'backlog' => 128,   //listen backlog
    'max_request' => 2000,
    'dispatch_mode'=>3,
    'buffer_output_size' => 32 * 1024 *1024, //必须为数字
    'open_eof_split' => true,
    'package_eof' => "\r\n",
    'heartbeat_check_interval' => 5,
    'heartbeat_idle_time' => 60,
    'package_max_length' => 9990000,
    'max_conn' => 10000,
//    'daemonize' => 1,
));

/**
 * 监听连接进入事件
 */
$serv->on('connect', function ($serv, $fd) {
    echo "Client: Connect.\n";
});

/**
 * 监听数据发送事件
 * 接受客户端数据，调用相应抓取规则，处理，返回数据到客户端
 */
$serv->on('receive', function ($serv, $fd, $from_id, $data){
    if(trim($data) == 'shutdown'){
        $serv -> send($fd,'shutdown');
        $serv -> close($fd);
        $serv->shutdown();
    }else{
        $info = json_decode($data,1);
        var_dump($info);
        $task_id = $serv->task(json_encode(['fd' => $fd, 'data' => $info['data'], 'key' => $info['key']]));
    }
});

/**
 * 处理异步任务
 */
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    addThreadNum('Etl');//增加线程数
    echo '-------------------------------------------';
    //返回任务执行的结果
    $data = json_decode($data,1);
    var_dump($data);
    callApi($data['data'], $data['key']);
    $serv->finish(json_encode(['fd' => $from_id, 'data' => $data]));
});

/**
 * 异步任务处理结果
 */
$serv->on('finish', function ($serv, $task_id, $data) {
    decrThreadNum('Etl');
    echo '';
});
/**
 * 监听连接关闭事件
 */
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

$ip = serverIP();
$d = ['port' => $config['swoole']['swoole_port3'], 'time' => time()];
settingIps('Etl-server', $ip, $d); //获取服务器服务

resetThreadNum('Etl'); //重置线程数
//启动服务器
$serv->start();