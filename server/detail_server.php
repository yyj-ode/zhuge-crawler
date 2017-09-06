<?php
include_once '../common/common.php';
    $d = [];
    $d[] = '-help 帮助';
    $d[] = '-name <城市名-渠道名称>  例(beijing-Lianjia)';
    $d[] = '-n 并发数 默认为50';
    $d[] = '-diff <状态> 是否比较房源变化(yes 是 no 否 默认为 yes)';
    $help = implode(PHP_EOL, $d);
    if(empty($argv[1]) || in_array('-help', $argv)){
        echo $help;
        die;
    }

    //记录运行脚本参数
    $keyShell = implode(' ', $argv);

    $argv = getArgvValues(['-name', '-n', '-diff'], $argv);
    $n = $argv['-n'];
    $diff = $argv['-diff'];
    if(empty($argv['-name'])){
        echo $help;
        die;
    }else{
        $name = $argv['-name'];
        //todo 转换成端口
        $port = getSourcePort($name);
        if(!$port){
            echo $help;
            die;
        }
    }
    if(empty($n)){ //默认并发数 
        $n = 10;
    }
    if(empty($diff)){
        $diff = 'yes';
    }
    echo '端口号: '.$port;

/**
 * 创建Server对象
 * 监听 127.0.0.1:9501端口
 */
$serv = new swoole_server($config['swoole']['swoole_path'], $port);

$serv->set(array(
    'reactor_num' => 12,
    'worker_num' => 12,    //worker process num
    'task_worker_num' => intval($n+10),
    'backlog' => 128,   //listen backlog
    'max_request' => 2000,
    'dispatch_mode'=>3,
    'open_eof_check' => false,
    'buffer_output_size' => 32 * 1024 *1024, //必须为数字
    'open_eof_split' => true,
    'package_eof' => "\r\n",
    'heartbeat_check_interval' => 30,
    'heartbeat_idle_time' => 60,
    'package_max_length' => 99999999,
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
$serv->on('receive', function ($serv, $fd, $from_id, $data) use($name, $port, $n, $diff, $keyShell){
    $serv->close($fd);
    $data = json_decode($data, true);
    if($data['instructions'] == 'stop'){ return false;}

    //getRedisInit();

    /***加入监控服务状态*****/
    //joinControl($name, 'detail_server', $keyShell, '15');
    /***加入监控服务状态*****/

    $serv->tick(2000, function() use ($serv, $fd, $from_id, $data, $name, $port, $n, $diff, $keyShell) {
        $typenum = $data['type'].$name.$port;
        $mantypenum = $data['type'].$name.$port.'num';
        if($concurrentnum = handleCheckThreadNum($typenum, $n)){
            for($i = 0; $i <= $concurrentnum; $i++){
                usleep(200);
                $task_id = $serv->task(json_encode(['fd' => $fd, 'type' => $data['type'], 'name' => $name, 'typenum' => $typenum, 'diff' => $diff, 'keyShell' => $keyShell]));
            }
        }else{
            $redis = getRedisInit();
            $mantypenumli = $redis->get($mantypenum);
            if($mantypenumli >= 15){
                $redis->set($mantypenum, 0);
                $redis->set(serverIP().'_'.$typenum, 0);
            }else{
                echo date('Y-m-d H:i:s').'连接数已满！('.$n.')'."\r\n";
                addNum($mantypenum); //增加线程数    
            }
        }
    });
});

/**
 * 处理异步任务
 */
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    sleep(mt_rand(0, 3));
    $data = json_decode($data,1);
    $name = $data['name'];
    $typenum = $data['typenum'];
    $diff = $data['diff'];


    /***加入监控服务状态*****/
    //$keyShell = $data['keyShell'];
    //joinControl($name, 'detail_server', $keyShell, '15');
    /***加入监控服务状态*****/

    //返回任务执行的结果
//    $fd = $data['fd'];
    addThreadNum($typenum);//增加线程数
    $info = getUrl($name);
    if($info){
        $source = $info['source'];
        $source_url = $info['source_url'];
        //放到临时目录
        tmpSourceUrl($name, $source_url);

        echo date('Y-m-d H:i:s') . '----' . '开始抓取: '.$source_url.'渠道：'.$source."\r\n";
        echo '-------------------------------------------'."\r\n";
//        if(($res = checkContent($source, $source_url))){
            $res = checkContent($source, $source_url);
            $t = microtime();
            echo $source_url.'获取数据时间'.(microtime() - $t)."\r\n";
            /************/
            if(!empty($res['source_url'])){
                $source_url = $res['source_url'];
                $sourcetag = getSourceUrlTag();
                if(isExistsStr($source_url, $sourcetag)){  //判断是否自定义渠道url
                    $source_url = explode($sourcetag, $source_url);
                    $source_url = $source_url[1];
                }
            }
            $res['source'] = $source;
            $res['source_url'] = $source_url;
            /************/
            $t = microtime();
            saveContent($res, $source, 'url', $diff);
            echo '写入时间：'.(microtime()-$t).'毫秒'."\r\n";
//        }else{
            //记录不良种子数量
//            illegalSeed($source);
//        }
        //放到临时目录
        delTmpSourceUrl($name, $source_url);
    }else{
//        echo 'sleep5分钟'."\r\n";
        sleep(5);
        settingTmpSourceUrl($name); //将失败的数据再次放到url队列
        echo date('Y-m-d H:i:s').'没有种子！！ '."\r\n";
    }
    decrThreadNum($typenum); //减少线程数
//    if(!empty($_SERVER['redisobj'])){ //当一个线程处理完之后，关闭redis连接（不能浪费！）
//        $_SERVER['redisobj']->close();
//        unset($_SERVER['redisobj']);
//    }
});
    
/**
 * 异步任务处理结果
 */
$serv->on('finish', function ($serv, $task_id, $data) {
//    $data=json_decode($data,1);
//    $serv->send($data['fd'], json_encode($data['data'])."\r\n");
});
/**
 * 监听连接关闭事件
 */
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

$ip = serverIP();
$d = ['port' => $port, 'time' => time()];
settingIps('Crawling-server', $ip, $d); //获取服务器服务

$redis = getRedisInit();
$redis->set($ip.'_Crawling', 0);
$redis->set($ip.'_Crawling'.$name.$port, 0);
$redis->set('pushhtmlnum', 0);
echo exec('php -q ../client/detail_client.php -p '.$port.' > /dev/null &');
//启动服务器
$serv->start();
