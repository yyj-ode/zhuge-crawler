<?php
include_once '../common/common.php';
/**
 * 创建Server对象
 * 监听 127.0.0.1:9501端口
 */
$serv = new swoole_server($config['swoole']['swoole_path'], $config['swoole']['swoole_port']);

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
    'package_max_length' => 81920,
    'heartbeat_check_interval' => 5,
    'heartbeat_idle_time' => 60,
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
    addThreadNum('Grab');//增加线程数
    if(trim($data) == 'shutdown'){
        $serv -> send($fd,'shutdown');
        $serv -> close($fd);
        $serv->shutdown();
    }else{
        $info = json_decode($data,1);
        echo date('Y-m-d H:i:s').'开始抓取！---'.$info['source']."\r\n";
        var_dump($info);
        $res = callSeed($info['source'], 'house_list', $info['source_url']);
        if(!$res || empty($res)){
            echo date('Y-m-d H:i:s').'种子为空！'."\r\n";
            $serv -> send($fd, 'list fail!');
        }else{
            foreach((array)$res as $key => $value){
                if(empty($value['source_url']) || empty($value['source'])){
                    illegalSeed($info['source']);  //记录不合法的种子
                }else{

                }
                $task_id = $serv->task(json_encode(['fd' => $fd, 'data' => $value]));
            }
        }
    }
    decrThreadNum('Grab');  //减少进程数
});

/**
 * 处理异步任务
 */
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    //返回任务执行的结果
    $data = json_decode($data,1);
    if(!empty($data['data']['source_url']) && !empty($data['data']['source'])){
        saveUrl($data['data']['source_url'], $data['data']['source']);
        echo date('Y-m-d H:i:s').'种子url：'.$data['data']['source_url'].'---'.'种子源:'.$data['data']['source']."\r\n";
    }else{
        echo date('Y-m-d H:i:s').'种子不合法！'."\r\n";
    }
    $serv->finish(json_encode(['fd' => $data['fd'], 'data' => $data['data']]));
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
$d = ['port' => $config['swoole']['swoole_port'], 'time' => time()];
settingIps('Grab-server', $ip, $d); //获取服务器服务

resetThreadNum('Grab'); //重置线程数
//启动服务器
$serv->start();