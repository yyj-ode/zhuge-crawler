<?php
include_once '../common/common.php';
/**
 * 创建Server对象
 * 监听 127.0.0.1:9501端口
 */
$serv = new swoole_server($config['swoole']['swoole_path'], $config['swoole']['swoole_port2']);

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
    'heartbeat_check_interval' => 30,
    'heartbeat_idle_time' => 60,
    'package_max_length' => 81920,
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
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    addThreadNum('Crawling');//增加线程数
    echo date('Y-m-d H:i:s').'-------------------------------------------处理数据';
    $info=json_decode($data,1);
    $t = microtime();
    $res = callSeed($info['source'], 'house_detail', $info['source_url']);
    echo $info['source_url'].'获取数据时间'.(microtime()-$t)."\r\n";
    $task_id = $serv->task(json_encode(['fd' => $fd, 'data' => $res, 'source' => $info['source']]));
    decrThreadNum('Crawling'); //减少线程数
});

/**
 * 处理异步任务
 */
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    echo '-------------------------------------------';
     //返回任务执行的结果
     $data = json_decode($data,1);
     $fd = $data['fd'];
     $res = $data['data'];
     $source = $data['source'];
     $res['source'] = getSourceSeed($source);
     $t = microtime();
     saveContent($res, $source);
     echo '写入时间：'.(microtime()-$t).'毫秒'."\r\n";
     $serv->finish(json_encode(['fd' => $fd, 'data' => $res]));
});
    
/**
 * 异步任务处理结果
 */
$serv->on('finish', function ($serv, $task_id, $data) {
    $data=json_decode($data,1);
    $serv->send($data['fd'], json_encode($data['data'])."\r\n");
});
/**
 * 监听连接关闭事件
 */
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

$ip = serverIP();
$d = ['port' => $config['swoole']['swoole_port2'], 'time' => time()];
settingIps('Crawling-server', $ip, $d); //获取服务器服务

resetThreadNum('Crawling'); //重置线程数
//启动服务器
$serv->start();