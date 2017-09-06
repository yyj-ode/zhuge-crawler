<?php
include_once '../common/common.php';
$config = include_once 'config.php';
define("CONFIG_PATH", './config.php');
/**
 * Created by PhpStorm.
 * User: Tony
 * Date: 16/3/14
 * Time: 下午5:21
 */
class server{
    private $serv;
    private $config;
    private $redis;

    public function __construct($config){
        echo date('Y-m-d H:i:s')." 开启爬虫。。。";
        $this->config = $config;
        $this->connectRedis();
        $this->saveStatus(null);
        $this->swooleInit();
    }

    /*
     * 初始化swoole
     */
    private function swooleInit(){
        $this->serv = new swoole_websocket_server($this->config['SERVER_LISTEN_IP'], $this->config['SERVER_LISTEN_PORT']);
        //初始化swoole服务
        $this->serv->set(array(
            'worker_num'  => $this->config['WORKER_NUM'],
            'daemonize'   => $this->config['DAEMONIZE'],
            'max_request' => $this->config['MAX_REQUEST'],
            'log_file'    => $this->config['LOG_FILE'],
            'task_worker_num' => $this->config['TASK_WORKER_NUM'],
        ));
        //设置监听
        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->serv->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->serv->on('Connect', [$this, 'onConnect']);
        $this->serv->on("Close", [$this, 'onClose']);
        $this->serv->on("Task", [$this, 'onTask']);
        $this->serv->on("Finish", [$this, 'onFinish']);
        $this->serv->on("Message", [$this, 'onMessage']);
        $this->serv->on('Receive', [$this, 'onReceive']);

        echo "==>初始化完成";

        //开启
        $this->serv->start();
    }

    /*
     * 连接 Redis
     */
    public function connectRedis(){
        $this->redis = new Redis();
        $this->redis->connect($this->config['REDIS_SERVER_IP'], $this->config['REDIS_SERVER_PORT']);
        if(!empty($this->config['REDIS_AUTH'])){
            $this->redis->auth($this->config['REDIS_AUTH']);
        }
        if(!empty($this->config['REDIS_DB'])){
            $this->redis->select($this->config['REDIS_DB']);
        }
    }

    /*
     * 维持心跳
     */
    public function saveStatus($server){
        $key = $this->config['_REDIS_SERVER_RUN_STATUS_'];

        $data = json_encode(['ip' => $this->serverIP(), 'port' => $this->config['SERVER_LISTEN_PORT'], 'time' => time()]);
        $this->redis->set($key, $data);
        if($server){
            $this->broadcast($server, 'Assign Server is OK');
        }
    }

    /*
     * 广播给所有客户端
     */
    public function broadcast($server, $msg){
        $msg = json_encode($msg);
        foreach($server->connections as $clid => $info){
            //var_dump($clid);
            try{
                $server->push($clid, $msg);
            } catch (Exception $ex) {
                echo date('Y-m-d H:i:s').$ex->getMessage();
            }
        }
        echo "当前服务器共有 ".count($server->connections). " 个连接\n";
    }

    /*
     * 获取本机IP
     */
    public function serverIP(){
        return current(swoole_get_local_ip());
    }

    /*
     * Server 启动
     */
    public function onStart($server){
        echo "==>已启动\n";
        echo date('Y-m-d H:i:s')." 主进程ID：= " .$this->config['SERVER_MASTER_PROCESS_ID']."\n";
        cli_set_process_title($this->config['SERVER_MASTER_PROCESS_ID']);
    }


    /*
     * WorkerStart 启动
     */
    public function onWorkerStart(swoole_server $server, $worker_id) {
        cli_set_process_title($this->config['SERVER_WORKER_PROCESS_ID'] . $worker_id);
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if($worker_id == 0){
            $workerProcessNum = $this->config['WORKER_NUM']+$this->config['TASK_WORKER_NUM'];
            echo date('Y-m-d H:i:s').' 工作进程ID:= '.$this->config['SERVER_WORKER_PROCESS_ID'].$worker_id." 已启动 ".$workerProcessNum." 进程\n";
            $this->config = require(CONFIG_PATH);
            $this->startTimer($server);
        }
    }

    /*
     * 启动定时器
     */
    public function startTimer($server) {
        echo date('Y-m-d H:i:s').' 启动定时任务,周期为 '.$this->config['TIMER_INTERVAL']. "秒\n";
        $this->serv = $server;

        $this->timer_id = $server->tick($this->config['TIMER_INTERVAL'] * 1000, function () {
            $this->saveStatus($this->serv);
            $this->getCrawler($this->serv);
        });
    }

    public function getCrawler($serv){
        $urls = getCrawlerSource();
        foreach((array)$urls as $v){
            callSeed($v, 'house_page', '', $serv);
        }
    }

}


$server = new server($config);