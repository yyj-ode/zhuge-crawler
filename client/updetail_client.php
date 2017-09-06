<?php
    sleep(2);
    include_once '../common/common.php';
    $d = [];
    $d[] = '-help 帮助';
    $d[] = '-p 端口号';
    $help = implode(PHP_EOL, $d).PHP_EOL;
    if(empty($argv[1]) || in_array('-help', $argv)){
        echo $help;
        die;
    }
    $argv = getArgvValues(['-p'], $argv);
    if(empty($argv['-p'])){
        echo $help;
        die;
    }
    $port = $argv['-p'];
    
    
    $client = new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);

    $client->set(array(
        'open_eof_split' => true,
        'package_eof' => "\r\n",
        'package_max_length' => 80000,
    ));

   /**
    * 注册连接成功回调
    * 负责发布种子
    */
    $client->on("connect", function($cli) {
        $message = json_encode(['type' => 'upCrawling', 'timer' => 60, 'concurrentnum' => '100', 'instructions' => 'start']);
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
    if($client->connect($config['swoole']['swoole_path'], $port, $config['swoole']['swoole_timeout'])){
//        $client->send("data");
    } else {
        echo "连接服务器失败！";
    }

