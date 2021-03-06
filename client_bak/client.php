<?php
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
        $i = 1;
        while(true){
            if(checkThreadNum('Grab') < 32) {  //并发线程不超过32个
                $urls = getCrawlerSource();
                print_r($urls);
                foreach ((array)$urls as $v) {
                    echo '第'.$i.'次抓取'.date('Y-m-d H:i:s') . '----' . $v . '渠道';
                    callSeed($v, 'house_page', '', $cli);
                }
            }
            $i++;
        }
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
    if($client->connect($config['swoole']['swoole_path'], $config['swoole']['swoole_port'], $config['swoole']['swoole_timeout'])){
//                $client->send("data");
    } else {
            echo "连接服务器失败！";
    }
