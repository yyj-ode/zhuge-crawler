<?php
class redisInit { 
        private $redis; //redis对象

        /**
         * 初始化Redis
         * $config = array(
         *  'server' => '127.0.0.1' 服务器
         *  'port'   => '6379' 端口号
         */
        private $config = [
//            'server' => '127.0.0.1',  //远程服务器地址
//            'port' => '6379',        //端口
//            'auth' => 'zhuge1116',   //用户
//            'db' => 2,    //redis库
        ];
        /*
         * @param array $config
         */
        public function __construct($config = []) {
//                $this->config = getRedisconf();
                return $this->getRedis($config);
        }
        
        private function getRedis($config = []){
            usleep(mt_rand(150, 200)); //延迟
            $this->config['server'] = empty($config['server']) ? $this->config['server'] : $config['server'];
            $this->config['port'] = empty($config['port']) ? $this->config['port'] : $config['port'];
            $this->config['auth'] = empty($config['auth']) ? $this->config['auth'] : $config['auth'];
            $this->config['db'] = empty($config['db']) ? $this->config['db'] : $config['db'];
            try{
                $this->redis = new Redis();
                if($this->config['server'] == '127.0.0.1'){
                    $this->redis->connect('/tmp/redis.sock');
                }else{
                    $this->redis->connect($this->config['server'], $this->config['port']);
                }
                if(!empty($this->config['auth'])){
                    $this->redis->auth($this->config['auth']);
                }
                if(!empty($this->config['db'])){
                    $this->redis->select($this->config['db']);
                }
            } catch (Exception $exc) {
                $log = 'redisError.log';
                $string = $exc->getMessage();
                file_put_contents($log, date('Y-m-d H:i:s').$string.PHP_EOL, FILE_APPEND);
            }

            
            return $this->redis;
        }
        
        private function getRedisObj($isMaster = true){
            $ip = serverIP();
            $ip = explode('.', $ip);
            if ($ip[0] != '192') { //如果是阿里的服务器则直接连接阿里redis服务，不需要做中转
                $this->getRedis($_SERVER['config']['alipayredis']);
            } else {
                if($isMaster){
                    $this->getRedis($_SERVER['config']['redis']);
                }else{
                    $redis_info = $_SERVER['config']['redis'];
                    $redis_info['server'] = '127.0.0.1';
                    $this->getRedis($redis_info);
                }
            }
        }

        /**
         * 设置值
         * @param string $key KEY名称
         * @param string|array $value 获取得到的数据
         * @param int $timeOut 时间
         */
        public function set($key, $value, $timeOut = 0) {
                $value = json_encode($value, TRUE);
                $retRes = $this->redis->set($key, $value);
                if ($timeOut > 0) $this->redis->setTimeout($key, $timeOut);
                return $retRes;
        }
        
        public function setNum($key, $value){
            if(!empty($value)){
                return $this->redis->set($key, $value);
            }
        }

        /**
         * 通过KEY获取数据
         * @param string $key KEY名称
         */
        public function get($key) {
//                $this->getRedisObj(false);
                $result = $this->redis->get($key);
                return json_decode($result, TRUE);
        }

        /**
         * 删除一条数据
         * @param string $key KEY名称
         */
        public function delete($key) {
                return $this->redis->del($key);
        }

        /**
         * 清空数据
         */
        public function flushAll() {
                return $this->redis->flushAll();
        }

        /**
         * 数据入队列
         * @param string $key KEY名称
         * @param string|array $value 获取得到的数据
         * @param bool $right 是否从右边开始入
         */
        public function push($key, $value ,$right = true) {
                $value = json_encode($value);
                $satrus = $right ? $this->redis->rPush($key, $value) : $this->redis->lPush($key, $value);
        }

        /**
         * 数据出队列
         * @param string $key KEY名称
         * @param bool $left 是否从右边开始出数据
         */
        public function pop($key , $right = true) {
                $val =  $right ? $this->redis->rPop($key) : $this->redis->lPop($key);
                return json_decode($val);
        }

        /**
         * 数据自增
         * @param string $key KEY名称
         */
        public function increment($key) {
                return $this->redis->incr($key);
        }

        /**
         * 数据自减
         * @param string $key KEY名称
         */
        public function decrement($key) {
                return $this->redis->decr($key);
        }

        /**
         * key是否存在，存在返回ture
         * @param string $key KEY名称
         */
        public function exists($key) {
//                $this->getRedisObj(false);
                return $this->redis->exists($key);
        }
        
        /**
         * key是否存在，存在返回ture
         * @param string $key KEY名称
         */
        public function hexists($key, $field) {
//                $this->getRedisObj(false);
                return $this->redis->hexists($key, $field);
        }

        /**
         * 存hash数据
         * @param string $hash
         * @param string $key
         * @param string $value
         * @return int
         */
        public function hset($hash = '', $key = '', $value = ''){
                if(!empty($hash) && !empty($key)){
                        return $this->redis->hSet($hash, $key, $value);
                }
                return false;
        }

        /**
         * 取hash数据
         * @param string $hash
         * @param string $key
         */
        public function hget($hash = '', $key = ''){
                if(!empty($hash) && !empty($key)){
                        return $this->redis->hGet($hash, $key);
                }
                return false;
        }

        /**
         * 获取hash所有key
         */
        public function hkeys($hash = ''){
                if(!empty($hash)){
                        return $this->redis->hKeys($hash);
                }
                return false;
        }
        
        /**
         * 获取hash所有key
         */
        public function hDel($hash = '', $key = ''){
                if(!empty($hash) && !empty($key)){
                        return $this->redis->hDel($hash, $key);
                }
                return false;
        }

        /**
         * 获取hash所有value
         */
        public function hvals($hash = ''){
                if(!empty($hash)){
                        return $this->redis->hVals($hash);
                }
                return false;
        }

        /**
         * 发布者
         * @param null $name
         * @param null $content
         * @return bool|int
         */
        public function pub($name = null, $content = null){
                if(!empty($name) && !empty($content)){
                        return $this->redis->publish($name, $content);
                }
                return false;
        }

        /**
         * 订阅者
         * @param null $name
         * @param null $callback
         * @return bool|void
         */
        public function sub($name = null, $callback = null){
                if(!empty($name) && !empty($callback)){
                        return $this->redis->subscribe((array)$name, $callback);
                }
                return false;
        }

        public function lRem($key = null, $value = null, $num = 0){
                return $this->redis->lRemove($key, $value, $num);
        }

        public function Llen($key = null){
                return $this->redis->lLen($key);
        }

        public function LINDEX($key = null, $index = null){
                return $this->redis->lIndex($key, $index);
        }



        /**
         * 返回redis对象
         */
        public function redis() {
                return $this->redis;
        }

        public function close(){
                return $this->redis->close();
        }
}

function getRedisconf(){
        return [];
}