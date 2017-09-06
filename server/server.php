<?php
include_once '../common/common.php';
        $d[] = '-help 帮助';
        $d[] = '-name <城市名-渠道名称>  例(beijing-Lianjia)';
        
        $d[] = '-firstall <天数> 首次全站抓取新增数据,之后持续抓取最新页,如指定天数(默认为3天),则按该天数循环执行全站抓取';
        $d[] = '-firstnew <天数> 最新房源抓取新增数据,如指定天数(默认为3天),则按该天数循环执行全站抓取';
//        $d[] = '-typeall 持久化全站抓取';
        $d[] = '-all <状态> 全站重新抓取';
//        $d[] = '-diff <状态> 是否比较房源变化(yes 是 no 否 默认为 yes)';
        $d[] = '-n 并发数 默认为50';
        $help = implode(PHP_EOL, $d).PHP_EOL;

        if(empty($argv[1]) || in_array('-help', $argv)){
            echo $help;
            die;
        }


        //记录运行脚本参数
        $keyShell = implode(' ', $argv);

        // 端口
        $list = getArgvValues(['-name', '-firstall', '-firstnew', '-n'], $argv);
        
        $name = $list['-name'];
        $firstall = $list['-firstall'];
        $firstnew = $list['-firstnew'];
//        $diff = $list['-diff'];
        $n = $list['-n'];
        $day = 3; 
        if(!empty($name)){
            $firstallstatus = array_search('-firstall', $argv);
            $firstnewstatus = array_search('-firstnew', $argv);
//            $diffstatus = array_search('-diff', $argv);
            $allstatus = array_search('-all', $argv);
            if(!empty($firstallstatus) || !empty($firstnewstatus)){
                $port = getSourcePort($name);
                if($port){  //文件是否存在
                    $redis = getRedisInit();
                    $page_url = md5(str_replace('-', '/', $name)).'_page_url';
                    if(!empty($firstallstatus)){ //首次全站抓取,之后持续抓取最新页,如指定天数(默认为3天),则按该天数循环执行全站抓取
                        $redis->delete($page_url);
                        if(!empty($firstall) && is_numeric($firstall)){
                            $day = (int)$firstall;
                        }
                        $redis->setNum('house_pageNum', 3);
                        $_SERVER['day'] = $day;
                    }elseif(!empty($firstnewstatus)){   //最新房源抓取,如指定天数(默认为3天),则按该天数循环执行全站抓取
                        if(!empty($firstnew) && is_numeric($firstnew)){
                            $day = (int)$firstnew;
                        }
                        $data = explode('-', $name);
                        $city = $data[0];
                        $source = $data[1];
                        $classname = $city.'\\'.$source;
                        $filepath = '../seedrules/city/' .$city.'/'.$source.'.class.php';
                        include_once $filepath;
                        $sourceclass = new $classname();
            //            $sourceclass = new $class($city);
                        if(is_callable([$sourceclass, 'callNewData'])){
                            $urllist = $sourceclass->callNewData();
                            $redis->set($page_url, $urllist, $day*86400);
                        }
                    }else{
                        echo $help;
                        die;
                    }
//                    if(!empty($diffstatus)){ 
//                        if(trim($diff) == 'no'){ //删除指纹
//                            $redis->delete($name.'_fingerprint');
//                        }
//                    }
                    if(!empty($allstatus)){
                        $redis->delete(str_replace('-', '/', $name));
                        $redis->delete(str_replace('-', '/', $name).'seed');
                        $redis->delete($name.'_key');
                        $redis->delete($name.'_list');
                        $redis->delete(str_replace('-', '/', $name).'illega');
                        $redis->delete($name.'illega');
//                        $redis->delete($name.'_fingerprint');
                        $redis->delete($name);
                    }
                }else{    
                    echo $help;
                    die;
                }
            }else{       
                echo $help;
                die;
            }
        }else{
            echo $help;
            die;
        }
        
        if(empty($n)){ //默认并发数 
            $n = 10;
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
    'package_max_length' => 99999999,
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
$serv->on('receive', function ($serv, $fd, $from_id, $data) use($name, $port, $n, $source, $day, $keyShell){
    $serv->close($fd);
    
    $data = json_decode($data,1);
    
        if($data['instructions'] == 'stop'){ return false; }
        
        $sourcename = $name;
        $sourcename = str_replace('-', '/', $sourcename);
        
        getRedisInit();
        echo '爬取' . date('Y-m-d H:i:s') . '----' . $sourcename . '渠道'."\r\n";
        if(isExistsStr($source, 'Deal')){ //如果抓取的是小区成交,抓取频率按设置的天数,默认3天
            $frequency = $day*24;
        }else{ //默认180分钟
            $frequency = 180;
        }
        
        $getHousePage = function() use($serv, $fd, $from_id, $data,$name, $port, $n, $source, $day, $sourcename, $frequency, $keyShell){
            
            settingSourceStatus($sourcename, 'start'); //任务开始
            $listsource_data = callSeed($sourcename, 'house_page', '');
            if(empty($listsource_data) || !is_array($listsource_data)){
                sleep(3);
                $listsource_data = callSeed($sourcename, 'house_page', '');
                if(empty($listsource_data) || !is_array($listsource_data)){
                    sleep(3);
                    $listsource_data = callSeed($sourcename, 'house_page', '');
                    if(empty($listsource_data) || !is_array($listsource_data)){
                        illegalSeed($sourcename);
                        echo '规则有异常(house_page)'."\r\n";
                        return '';
                    }
                }
            }else{
               $page_count = count($listsource_data);
               initPageSourceStatus($sourcename);
               while($listsource_data){
                   if(empty($listsource_data)){
//                       echo '爬取完成' . $sourcename."\r\n";
                       break;
                   }
                  $typenum = $data['type'].$name.$port;
                  $mantypenum = $typenum.'num';
                    if ($concurrentnum = handleCheckThreadNum($typenum, $n)) {
                        $i = 0;
                        foreach ((array)$listsource_data as $k => $v) {
                            usleep(200);
                            if ($i >= $concurrentnum) {
                                echo '并发数： '.$n.'－可用数：'.$concurrentnum.'－当前数：'.$i."\r\n";;
                                break;
                            } else {
                                if(!empty($v)){
                                    try{
                                        $task_id = $serv->task(json_encode(['fd' => $fd, 'source' => $sourcename, 'data' => $v, 'type' => $typenum, 'page_count' => $page_count, 'frequency' => $frequency, 'name' => $name, 'keyShell' => $keyShell]));
                                    } catch (Exception $ex) {
                                        $log = 'taskError.log';
                                        $errorlog = $ex->getMessage();
                                        file_put_contents($log, $errorlog.PHP_EOL, FILE_APPEND);
                                    }
                                } else {
                                    //记录不良种子数量
                                    illegalSeed($sourcename);
                                }
                                unset($listsource_data[$k]);
                            }
                            $i++;
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
                    echo '---------------------------------------------'."\r\n";
                    sleep(1);
               }
            }
            settingSourceStatus($sourcename, 'stop'); //任务完成
            echo '----------------------------------爬取完成[' . $sourcename."]-----------------------------------------\r\n";
        };
        
        
        //首次运行
        $getHousePage();
        
        $serv->tick((1000*60)*$frequency, function() use ($serv, $fd, $from_id, $data,$name, $port, $n, $source, $day, $sourcename, $getHousePage, $frequency, $keyShell) {
            /***加入监控服务状态*****/
            //joinControl($name, 'server', $keyShell, 180);
            /***加入监控服务状态*****/
            $status = getSourceStatus($sourcename);
            if($status == 'stop'){ //任务执行完成
                $getHousePage();
            }
        });
});

/**
 * 处理异步任务
 */
$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    sleep(mt_rand(0, 3));
    //返回任务执行的结果
    $data = json_decode($data,1);
    addThreadNum($data['type']);//增加线程数
    $source = $data['source']; //渠道
    $page_count = $data['page_count'];//列表总数
    $frequency = $data['frequency'];


    /***加入监控服务状态*****/
    $name = $data['name'];
    $keyShell = $data['keyShell'];
//    joinControl($name, 'server', $keyShell, 180);
    /***加入监控服务状态*****/

    
    $count = setPageSourceStatus($source);
    echo '['.date('Y-m-d H:i:s').'] --['.$source.']--正在抓取列表页:第'.$count.'页'."\r\n";
    
    $source_data = callSeed($source, 'house_list', $data['data']);
    if(!empty($source_data)){
//        echo date('Y-m-d H:i:s') . $source . '------' . implode('|', $source_data) . "\r\n";
        foreach((array)$source_data as $key => $value){
            echo '['.date('Y-m-d H:i:s').']--['.$source.']--';
            if(!empty($value)){
//                echo date('Y-m-d H:i:s').'种子url：'.$value.'---'.'种子源:'.$source."\r\n";
                saveUrl($value, $source);
            }else{
                echo '[种子不合法]';
//                echo date('Y-m-d H:i:s').'种子不合法！'."\r\n";
                illegalSeed($source);
            }
            echo "\r\n";
        }
    }else{
        echo '['.$source.'] -- [列表为空]'."\r\n";
    }
    decrThreadNum($data['type']);
    
    if($count >= $page_count){
        echo date('Y-m-d H:i:s').'爬取种子(总列表:'.$page_count.', 实际爬取列表: '.$count.')完成(设定爬取频次'.$frequency.'分钟抓取一次)'."\r\n";
    }else{
        echo '---------------------------------------------进度: '.(($count/$page_count)*100).'%';
    }
//    if(!empty($_SERVER['redisobj'])){ //当一个线程处理完之后，关闭redis连接（不能浪费！）
//        $_SERVER['redisobj']->close();
//        unset($_SERVER['redisobj']);
//    }
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
$d = ['port' => $port, 'time' => time()];
settingIps('Grab-server', $ip, $d); //获取服务器服务
$redis = getRedisInit();
$redis->set($ip.'_Grab', 0);
$redis->set($ip.'_Grab'.$name.$port, 0);
$redis->set('pushhtmlnum', 0);
echo exec('php -q ../client/client.php -p '.$port.' > /dev/null &');
//启动服务器
$serv->start();

