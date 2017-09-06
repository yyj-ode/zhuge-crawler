<?php
//设置header头
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');
ini_set('default_socket_timeout', -1);  //不超时
set_time_limit(0);

//配置文件（redis，swoole)
$config = include_once '../common/config.php';
$_SERVER['config'] = $config;

//加载种子类
//include_once 'Seed.class.php';

//加载redis
include_once 'RedisInit.php';

//include_once '../seedrules/PublicClass.class.php';

include_once '../vendor/autoload.php';

include_once '../common/Snoopy.class.php';
include_once '../common/simple_html_dom.php';
include_once '../common/smtp.php';
include_once 'errorHandler.php';

/* -------------- */
//include 'Lianjia.class_before.php';
//include 'Wiwj.class.php';
/* -------------- */
/**
 * 调用生成种子
 * @param $source
 * @param $func
 * @param $url
 */
function callSeed($source = '', $func = '', $url = ''){
    if(!empty($source) && !empty($func)){
        if($func == 'house_page'){
            $redis = getRedisInit();
            $daykey = md5($source).'_page_url'; //渠道分页key
            if($redis->exists($daykey)){
                if(!empty($data = $redis->get($daykey))){
                    return $data;
                }
            }
        }
//        $seed = getSeedInit();
//        $seed->callSource($source, $func, $url, $cli);
        if(!isExistsStr($source, '/')){
            $filepath = '../seedrules/' . $source . '.class.php';
            $class = $source;
            $city = '';
        }else{
            $filepath = '../seedrules/city/' .$source . '.class.php';
            $d = explode('/', $source);
            $class = $d[count($d)-1];
            $city = $d[count($d)-2];
        }
        if(file_exists($filepath)){
            include_once $filepath;
            $classname = $city.'\\'.$class;
            $sourceclass = new $classname();
//            $sourceclass = new $class($city);
            $data = $sourceclass->$func($url);
            if($func == 'house_page'){
                $num = $redis->get('house_pageNum');
                if($num <= 0){
                    $urllist = [];
                    if(isExistsStr($source, 'Deal')){ //小区成交价
                        $urllist = $data;
                    }else{
                        $i = 0;
                        $num = 100; //默认取出前100页进行缓存
                        if(!is_callable([$sourceclass, 'callNewData'])){
                            foreach((array)$data as $key => $value){
                                if($i >= $num){
                                    break;
                                }
                                $urllist[] = $value;
                                $i++;
                            }
                        }else{
                            $urllist = $sourceclass->callNewData($num);
                        }
                    }
                    if(empty($_SERVER['day'])){
                       $_SERVER['day'] = 3; 
                    }
                    $redis->set($daykey, $urllist, $_SERVER['day']*86400);
                }else{
                    $redis->set('house_pageNum', $num-1);
                }
            }
            return $data;
        }
    }
}

function isExistsStr($str, $search){
    $temp = str_replace($search, '', $str);
    return $temp != $str;
}




/**
 * 获取种子初始化
 * @return redisInit
 */
function getSeedInit(){
    static $Seed;
    if(!is_object($Seed)){
        $Seed = new Seed();
    }
    return $Seed;
}


/**
 * 获取种子（url)
 * @return array|bool
 */
function getUrl($url = 'url'){
    $redis = getRedisInit();
    if($url == 'up_url' || $url == 'lose_url'){
//        if($redis->exists($url)){
            $url = $redis->pop($url);
            echo '['.$url."]\r\n";
            if(!empty($url)){
                $url = explode('|||', $url);
                $source_list = str_replace('/', '-', $url[1]) . '_list';
                if (preg_match('/(https?|ftp|file)/', $url[0])) {
                    echo 'update------'."\r\n";
                    $redis->hset($source_list, md5($url[0]), $url[1]);
                    return ['source_url' => $url[0], 'source' => $url[1]];
                }
            }
//        }
        return false;
    }
    if($redis->exists($url.'_key')){
        $urlcontent = $redis->pop($url.'_key');
        if(!empty($urlcontent) && $urlcontent != 'false'){
            if (preg_match('/(https?|ftp|file)/', $urlcontent)) {
                # code...
            //增加抓取种子数
//            grabNum($url);
//            
//            $redis->push('url', $url, false); //在把url放到队尾(持久化抓取) 暂时不跑更新
            $source = $redis->hget($url.'_list', md5($urlcontent));
//            $source = $redis->get(md5($urlcontent));
//        $redis->delete(md5($url));
            return ['source_url' => $urlcontent, 'source' => $source];
            }
        }
    }
    return false;
}

function grabNum($url = 'url'){
    if($url == 'url'){ //新增队列
        addNum('Grab-count'); //总共抓取种子数量
        addNum(date('Y-m-d').'Grab-count'); //每天总共抓取种子数量
    }else{ //更新队列
        addNum('upGrab-count'); //总共抓取种子数量
        addNum(date('Y-m-d').'upGrab-count'); //每天总共抓取种子数量
    }
}

/**
 * 获取内容
 * @param $num
 * @return array|bool
 */
function getData($num, $key = 'beijing'){
    $redis = getRedisInit();
    $data = $redis->pop($key);
    if(!empty($data)){
        $d[] = json_decode($data);
        for($i = 1; $i <= $num; $i++){
            $data = $redis->pop($key);
            if(!empty($data)){
                $d[] = json_decode($data);
            }else{
                break;
            }
        }
        return ['data' => $d, 'key' => $key];
    }
    return false;
}

/**
 * 获取自定义渠道url时标示位
 */
function getSourceUrlTag(){
    return $_SERVER['config']['source_url_tag'];
}

/**
 * 调用api
 */
function callApi($data = null, $city = ''){
    if(!empty($data) && !empty($city)){
        $url = $_SERVER['config']['ETL_API_URL'].'?params=\'\'&dbname=' . $city;
        echo date('Y-m-d H:i:s') . ' 请求API' . $url . "\n";
        try {
            $data = ['house_info' => $data];
            $content = curl_post($url, ['params' => $data]);
            var_dump($content);
            //$data = (array)json_decode($d);
            //var_dump($data);
        } catch (Exception $ex) {
            echo date('Y-m-d H:i:s') . $ex->getMessage() . "\n";
            echo $ex->getTrace() . "\n";
        }
    }
}

/**
 * 返回线程数
 * @return mixed
 */
function checkThreadNum($threadname = ''){
    $redis = getRedisInit();
    return $redis->get($threadname);
}

/**
 * 增加线程数
 * @return int
 */
function addThreadNum($threadname = ''){
    addNum(serverIP().'_'.$threadname);
}

/**
 * 减少线程数
 * @return int
 */
function decrThreadNum($threadname = ''){
    return delNum(serverIP().'_'.$threadname);
}

function resetThreadNum($threadname = ''){
    $redis = getRedisInit();
    return $redis->set($threadname, 0);
}


/**
 * url和内容放入redis队列中
 */
function saveUrl($url = '', $source = '', $status = true){
    if (preg_match('/(https?|ftp|file)/', $url)) {
        if (!empty($url) && !empty($source)) {
            $redis = getRedisInit();
            $md5url = md5($url);
            saveSeedCount();//总爬取数量

            $source_list = str_replace('/', '-', $source) . '_list';
            echo '[' . $url . ']';
            $redis->hset(str_replace('/', '-', $source) . '-urls', $md5url, $url);
            if ($redis->hget($source_list, $md5url)) {
                if ($status) {
                    echo '[已存在]';
//                echo date('Y-m-d H:i:s').'该房源已存在：'.$url."\r\n";
                }
            } else {
                $key = str_replace('/', '-', $source) . '_key';
                $redis->push($key, $url);
                $redis->hset($source_list, $md5url, $source);
                saveSeed($source);  //记录渠道种子数
                echo '[已写入]';
            }
        }
    }
}

/**
 * 设置列表页抓取的字段
 * @param type $url
 * @param type $data
 */
function setListFields($url = '', $data = ''){
    if(!empty($url) && !empty($data)){
        $key = md5($url.'_list_fields');
        $redis = getRedisInit();
        $redis->set($key, $data, (86400*10));
    }
    return false;
}

/**
 * 获取列表页抓取的字段
 * @param type $url
 */
function getListFields($url = ''){
    if(!empty($url)){
        $key = md5($url.'_list_fields'); 
        $redis = getRedisInit();
        return $redis->get($key);
    }
    return false;
}

function delSoureSetting($source = ''){
    if(!empty($source)){
        $redis = getRedisInit();
        $redis->delete($source.$_SERVER['config']['sourecall']);
    }
}

function saveSeedCount(){
    addNum('seed-count'); //总共爬取种子数量
    addNum(date('Y-m-d').'seed-count'); //每天总共爬取种子数量
}


/**
 * 记录种子数量
 * @param $sourceseed
 */
function saveSeed($sourceseed){
    addNum($sourceseed.'seed'); //记录渠道种子总增量
    addNum(date('Y-m-d').'-'.$sourceseed.'seed'); //记录渠道每天种子总数
    addNum(date('Y-m-d').'-seed-count'); //记录每天种子总增量
}

function addNum($key){
    if(!empty($key)){
        $redis = getRedisInit();
        return $redis->increment($key);
    }
}


function delNum($key){
    $redis = getRedisInit();
    if($redis->get($key) < 0){
        return $redis->set($key, 0);
    }else{
        return $redis->decrement($key);
    }
}

/**
 * 保存数据
 */
function saveContent($document, $source, $url = 'url', $diff = 'yes'){
//    $status = saveMysql(json_encode($document), $source); //写入数据库
    saveSourceContent($url);
    $redis = getRedisInit();
    $source_url = $document['source_url']; 
    
    $status = false;
    if(isExistsStr($source, 'Deal')){//小区成交
        $contentmd5 = md5($document['borough_name'].$document['finish_time'].$document['finish_price'].$document['house_area'].$document['house_room'].$document['house_hall'].$document['house_toward'].$document['house_floor'].$document['house_topfloor'].$document['broker_name'].$document['company_name'].$document['building_number']);
    }else{
        $contentmd5 = md5($document['source_url'].$document['house_title'].$document['house_price'].$document['owner_phone'].$document['off_type'].$document['borough_name'].$document['house_totalarea'].$document['house_pic_unit'].$document['house_pic_layout'].$document['house_room'].$document['tag'].$document['house_desc'].$document['video_url']);
    }
    $key = str_replace('/', '-', $source).'_fingerprint';
    if($diff == 'yes'){
    //    $key = md5($source_url.'_content');
        if(($fingerprint = $redis->hget($key, md5($source_url)))){
            if(trim($fingerprint) != trim($contentmd5)){
                $status = true;
            }
        }else{
            $status = true;
        }
    }else{
        $status = true;
    }
    if($status){
        echo '增加新房源'."\r\n";
//        $redis->set($key, $contentmd5);
        $redis->hset($key, md5($source_url), $contentmd5);
        $status = saveRedis($document, $source);   //写入redis
        saveSourceNum($source, $url);
        return $status;
    }else{
        echo '房源无变化'."\r\n";
    }
}

function saveSourceContent($url = 'url'){
    if($url == 'url'){
        addNum(date('Y-m-d').'-content-count'); //每天抓取总增量
    }else{
        addNum(date('Y-m-d').'-upcontent-count'); //更新队列每天抓取总增量
    }
}

function saveSourceNum($source, $url = 'url'){
    if($url == 'url'){
        addNum($source); //渠道总增量
        addNum(date('Y-m-d').'-newcontent-count'); //每天抓取新内容总增量
        addNum(date('Y-m-d').'-'.$source.'-content-count'); //每天渠道新内容抓取总增量
    }else{
        addNum($source.'up'); //渠道总增量
        addNum(date('Y-m-d').'-upnewcontent-count'); //每天抓取新内容总增量
        addNum(date('Y-m-d').'-'.$source.'-upcontent-count'); //每天渠道新内容抓取总增量
    }
}

/**
 * 记录不合法种子数量
 */
function illegalSeed($source){
    addNum($source.'illega');
}

/**
 * 数据保存mysql
 * @param $documents
 */
function saveMysql($document, $source){
    mysql_connect($_SERVER['config']['mysql']['host'], $_SERVER['config']['mysql']['user'], $_SERVER['config']['mysql']['pwd']);
    mysql_select_db($_SERVER['config']['mysql']['dbname']);
    $time = date('Y-m-d H:i:s', time());
    $sql = 'insert into '.$_SERVER['config']['mysql']['table'].'(text, source, ctime) values(\''.$document.'\', \''.$source.'\', \''.$time.'\')';
    mysql_query($sql);
}

function saveRedis($document, $source){
    $redis = getRedisInit();
    $city = explode('/', $source);
    $redis->push($city[0].'-'.$city[1], json_encode($document));
}

/**
 * 存储mongo
 * @param $document
 * @return array|bool
 */
function saveMongo($document){
    $m = new MongoClient();
    $db = $m->spider;       //选择一个数据库
    $collection = $db->house_sell_gov;//选择一个集合
    return $collection->insert($document);
}

/**
 * 抓取网站数据异常重试机制
 * @param string $source 渠道
 * @param string $url 目标网址
 * @param string $num 重试次数
 * @return bool
 */
function checkContent($source = '', $url = '', $num = 3){
    if(!empty($source) && !empty($url)){
        $checkoutData = function($data = null, $source){
            if(!empty($data)){
                if(isExistsStr($source, 'Deal')){ //小区成交价
                    if(empty($data['borough_name']) && empty($data['house_totalarea']) && empty($data['house_price'])){
                        return false;
                    }
                }else{
                    if(empty($data['house_title']) && empty($data['borough_name']) && empty($data['house_price'])){
                        return false;
                    }
                }
                return true;
            }
            return false;
        };
        for($i = 0; $i < $num; $i++){
            $content = retryContent($source, $url);
            if($content === false){
                break;
            }
            if($checkoutData($content, $source)){
                break;
            }
            //todo 更换ip重新抓取
        }
        if(!$checkoutData($content, $source)){
                echo '抓取为空！'."\r\n";
        }
        return $content;
    }
    return false;
}

/**
 * 调用内容
 * @param string $source
 * @param string $url
 */
function retryContent($source = '', $url = ''){
    $data = callSeed($source, 'house_detail', $url);
    if($data == false){return false;}
    $content = $data['content'];
    unset($data['content']);
    if(!isExistsStr($source, 'Deal')){//房源(除小区成交价)
        //todo
        if(!empty($data)){
            $status = checkHouseOff($source, $url, $content);
            $data['off_type'] = $status[0];
            $data['off_reason'] = $status[1];
        }else{
            $data['off_type'] = 2;
            $data['off_reason'] = 1;
        }
    }
    return $data;
}

/**
 * 检查房源是否下架
 * @param type $source 渠道
 * @param type $url 链接
 * @return int 房源状态 1 下架 2 正常
 */
function checkHouseOff($source = '', $url = '', $content = ''){
    $off_type = 2;
    $off_reason = 1;
    if(!empty($source) && !empty($url)){
        $city = explode('/', $source);
        $filepath = '../seedrules/'.$city[0].'HouseOffClass.class.php';
        include_once $filepath;
        $offclass = new houseOffClass();
        $status = $offclass->checkHouseOff($source, $url, $content);
        $off_type = $status[0];
        $off_reason = $status[1];
    }
    return [$off_type, $off_reason];
}

/**
 * 检查房源是否下架
 * @param type $source 渠道
 * @param type $url 链接
 * @return int 房源状态 1 下架 2 正常
 */
function checkHouseOff_bak($source = '', $url = ''){
    $off_type = 2;
    $off_reason = 1;
    if(!empty($source) && !empty($url)){
        $filepath = '../seedrules/HouseOffClass.class.php';
        include_once $filepath;
        $offclass = new houseOffClass();
        $status = $offclass->checkHouseOff($source, $url);
        $off_type = $status[0];
        $off_reason = $status[1];
    }
    return [$off_type, $off_reason];
}

/**
 * 获取redis初始化
 * @return redisInit
 */
function getRedisInit(){
    try{
        static $redisobj = null;
        if(empty($redisobj)){
            $ip = serverIP();
//    $ip = get_real_ip();
            $ip = explode('.', $ip);
            if ($ip[0] != '192') { //如果是阿里的服务器则直接连接阿里redis服务，不需要做中转
                $_SERVER['redisobj'] = new redisInit($_SERVER['config']['alipayredis']);
            } else {
                $_SERVER['redisobj'] = new redisInit($_SERVER['config']['redis']);
            }
            $redisobj = $_SERVER['redisobj'];
        }
        return $redisobj;
    }catch(Exception $e){
        print $e->getMessage();
        if($e->getMessage() == 'Redis server went away'){
            //阿里的服务器连不上，发送邮件提醒
            $title = 'redis连接失败';
            $content = date('Y-m-d H:i:s') . 'redis连接失败(IP: ' . implode('.', $ip) . ')';
            if ($ip[0] != '192') {
                $content .= '      redis详情' . implode('--', $_SERVER['config']['alipayredis']);
            } else {
                $content .= '      redis详情' . implode('--', $_SERVER['config']['redis']);
            }
            sendMail('yong@zhugefang.com', $content, $title);
        }
    }
}

/**
 * 获取多个城市
 */
function getCitys(){
    $dir = '../seedrules/city';
    return getFiles($dir);
}

/**
 * 获取爬取的渠道
 * @return mixed
 */
function getCrawlerSource(){
    $CrawlerSource = $_SERVER['config']['CrawlerSource'];
    if($CrawlerSource['status']){
        if(!empty($CrawlerSource['content'])){
            $data = [];
            foreach((array)$CrawlerSource['content'] as $key => $value){
                if(!isExistsStr($value, '/')){
                    $path = '../seedrules/city/'.$value.'/';
                    $citylist = getFiles($path);
                    foreach((array)$citylist as $v){
                        $channelname = str_replace('.class.php', '', $v);
                        $data[] = $value.'/'.$channelname;
                    }
                }else{
                    $citytype = explode('/', $value);
                    $channetype = ['hezu', 'rent', 'ershoufang'];
                    $type = strtolower($citytype[1]);
                    if(!in_array($type, $channetype)){
                        $data[] = $value;
                    }else{
                        $path = '../seedrules/city/'.$citytype[0].'/';
                        $citylist = getFiles($path);
                        foreach((array)$citylist as $v){
                            $channelname = str_replace('.class.php', '', $v);
                            if($type == 'ershoufang'){
                                if(!isExistsStr(strtolower($channelname), 'hezu') && !isExistsStr(strtolower($channelname), 'rent')){
                                    $data[] = $citytype[0].'/'.$channelname;
                                }
                            }else{
                                if(isExistsStr(strtolower($channelname), $type)){
                                    $data[] = $citytype[0].'/'.$channelname;
                                }
                            }
                        }
                    }
                }
            }
            return $data;
        }else{
            return getSourceAll();
        }
    }else{
        return getSourceAll();
    }
}

/**
 * 获取所有渠道
 * @return array
 */
function getSourceAll(){
    $dir = '../seedrules';
    $files = getFiles($dir);
    foreach((array)$files as $value){
        if($value != 'city') {
            if(str_replace('.class.php', '', $value) != 'PublicClass'){
                $source[] = str_replace('.class.php', '', $value);
            }
        }else{
            $dir = $dir.'/city';
            $citys = getFiles($dir);
            foreach((array)$citys as $v){
                $f = getFiles($dir.'/'.$v);
                foreach((array)$f as $citysource){
                    if(str_replace('.class.php', '', $citysource) != 'PublicClass'){
                        $source[] = $v.'/'.str_replace('.class.php', '', $citysource);
                    }
                }
            }
        }
    }
    return $source;
}


/**
 * 获取指定目录下的所有文件
 * @param type $dir 目录
 * @return string 返回目录下到所有文件
 */
function getFiles($dir = '../seedrules'){
    $handler = opendir($dir);
    while(($file = readdir($handler)) !== false){
        if($file == "."||$file == ".."||$file == '.DS_Store'){continue;}
//        if (!is_dir($file)) {
            $data[] = $file;
//        }
    }
    return $data;
}

function getSourceSeed($source = ''){
    if(!empty($source)){
        if(isExistsStr($source, '/')){
            $source = explode('/', $source);
            $source = $source[1];
        }
        return $_SERVER['config']['source'][$source];
    }
    return 0;
}

function curl_get($url, $get = [], $cookie = [], $handlestatus = true){
    //初始化
    $ch = curl_init();
    foreach((array)$get as $key => $value){
        if(!empty($value)){
            $getstr[] = $key.'='.$value;
        }
    }
    if(!empty($getstr)){
        $url .= '?'.implode('&', (array)$getstr);
    }
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if($cookie){
        foreach((array)$cookie as $key => $value){
            $cookies = $key.'='.$value.';';
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    //打印获得的数据
    return $handlestatus ? analyJson($output) : $output;
//        return $output;
}

//参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
function curl_post($url, $post = '', $cookie = '', $returnCookie = 0, $handlestatus = true){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, ' ');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://xxx");
    if($post){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if($cookie){
        foreach((array)$cookie as $key => $value){
            $cookies = $key.'='.$value.';';
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if ($returnCookie) {
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie'] = substr($matches[1][0], 1);
        $info['content'] = $body;
        return $handlestatus ? analyJson($info) : $info;
    } else {
        return $handlestatus ? analyJson($data) : $data;
    }
}

function get_curl_post_data($url, $post = '', $cookie = '', $returnCookie = 0, $handlestatus = false){
    return curl_post($url, $post, $cookie, $returnCookie, $handlestatus);
}

/**
 * 解析json串
 * @param type $json_str
 * @return type
 */
function analyJson($json_str) {
    $json_str = str_replace('＼＼', '', $json_str);
    $out_arr = array();
    preg_match('/{.*}/', $json_str, $out_arr);
    if (!empty($out_arr)) {
        $result = objarray_to_array(json_decode($out_arr[0], TRUE));
    } else {
        return FALSE;
    }
    return $result;
}

/**
 * 获取本机ip
 * @return mixed
 */
function serverIP(){
    if(function_exists('swoole_get_local_ip')) {
        $localIpArray = swoole_get_local_ip();
        $localIp = current($localIpArray);
        return $localIp;
    }else{
        return gethostbyname($_ENV['COMPUTERNAME']);
    }
}

/**
 * 记录开启服务
 * @param string $typename
 * @param string $key
 * @param string $value
 * @return bool|int
 */
function settingIps($typename = '', $key = '', $value = ''){
    if(!empty($typename) && !empty($key)){
        $redis = getRedisInit();
        return $redis->hset($typename, $key, $value);
    }
    return false;
}


/**
 * 检查现有并发数量是否有空置
 * @param string $typename
 * @param string $concurrentnum
 * @return bool|string
 */
function handleCheckThreadNum($typename = '', $concurrentnum = ''){
    if(!empty($typename) && !empty($concurrentnum)){
        if(checkThreadNum(serverIP().'_'.$typename) < $concurrentnum){
            return ($concurrentnum-checkThreadNum(serverIP().'_'.$typename));
        }
    }
    return false;
}

/**
 * 检查渠道是否存在，不存在则创建
 */
function checkCrawlerSource(){
//    $key = strtotime(date('Y-m-d H')).'_source';
    $seedkey = 'crawler-seed-list';
    $redis = getRedisInit();
    if(!$redis->exists($seedkey)){
        $urls = getCrawlerSource();
//        $redis->push($seedkey, 'end');
        foreach((array)$urls as $value){
            $redis->push($seedkey, $value);
        }
    }
    $data = $redis->pop($seedkey);
//    if(!empty($data) && $data != 'end'){
    if(!empty($data)){
        return $data;
    }else{
        $redis->delete($seedkey);
        return checkCrawlerSource();
        // return false;
    }
}

/**
 * 设置服务状态
 */
function resetServerStatus($type = '', $instructions = 'stop'){
    if(!empty($type)){
        $key = serverIP() . '-'.$type;
        $redis = getRedisInit();
        if($instructions == 'stop'){
            $redis->set($key, 'stop');
        }else{
            $redis->set($key, 'start');
        }
    }
}

/***------------------------------------------------------------------------------***/

/**
 * 设置抓取房主的代理，模拟请求
 * User-Agent Fangzhur/3.0.4(Iphone;IOS8.3;Scale/2.00)
 */
function getFzSnoopy($url,$Parameters){

    $snoopy = new Snoopy;
    $snoopy->proxy_host="58.251.132.181";
    $snoopy->proxy_port = "8888";
    // $snoopy->agent="Fangzhur/3.0.4 (iPhone; iOS 8.3; Scale/2.00)";
    $snoopy->_submit_type = "application/x-www-form-urlencoded"; //设定submit类型
    $snoopy->rawheaders['COOKIE']="AUTH_ID=169769; AUTH_MEMBER_NAME=18611088268; AUTH_MEMBER_STRING=V1dXUFJTZmpValhZawNfUUUCBVZdU19UbFZRO1sNOQNZV0YCUlNWAmJU; db_name=fangzhu; msg_rentsell=1";
    $snoopy->submit($url,$Parameters);
    return $snoopy->results;
}
/**
 *
 * 设置动态代理，防止被屏蔽。
 */
function getSnoopy($url){
    $snoopy = new Snoopy();
//     $snoopy_content=($snoopy->fetch($url)->results);
//     return $snoopy_content;
    $snoopy->referer = "http://www.zjs.cn"; // 伪装来源页地址 http_referer
    $snoopy->rawheaders["Pragma"] = "no-cache"; // cache 的http头信息
    $snoopy->rawheaders["X_FORWARDED_FOR"] = "122.96.59.104"; //伪装ip118.244.149.153:80    42.121.33.160:8080
    $snoopy_content=str_get_html($snoopy->fetch($url)->results);
    return $snoopy_content;
}



/**
 *
 * 设置动态代理，防止被屏蔽。
 * 获取xml、json方式
 * @param unknown_type $url
 */
function getXmlJsonSnoopy($url){

    //	if(empty($url) || !isset($url)){
    //		echo 'nothing';
    //	}
    $snoopy = new Snoopy();
    $snoopy->cookies["PHPSESSID"] = 'fc106b1918bd522cc863f36890e6fff7'; // 伪装sessionid
    $snoopy->agent = "(compatible; MSIE 4.01; MSN 2.5; AOL 4.0; Windows 98)"; // 伪装浏览器
    $snoopy->referer = "http://www.only4.cn"; // 伪装来源页地址 http_referer
    $snoopy->rawheaders["Pragma"] = "no-cache"; // cache 的http头信息
    $snoopy->rawheaders["X_FORWARDED_FOR"] = getIp(); //伪装ip
    $snoopy_content=$snoopy->fetch($url)->results;
    return $snoopy_content;
}


/**

/**
 * 去除字符串中所有空格、tab、回车等
 */
function trimall($str)//删除空格
{
    $qian=array(" ","　","\t","\n","\r");$hou=array("","","","","");
    return str_replace($qian,$hou,$str);
}

function gbk_to_utf8($text){
    return mb_convert_encoding($text, "UTF-8","GBK");
}

function gb2312_to_utf8($text){
    return mb_convert_encoding($text, "UTF-8","GB2312");
}


/*全角转换半角
 * @author jsx
 * 2015.6.19
 */
function SBC_DBC($info) {
    $DBC = Array(
        '０' , '１' , '２' , '３' , '４' ,
        '５' , '６' , '７' , '８' , '９' ,
        'Ａ' , 'Ｂ' , 'Ｃ' , 'Ｄ' , 'Ｅ' ,
        'Ｆ' , 'Ｇ' , 'Ｈ' , 'Ｉ' , 'Ｊ' ,
        'Ｋ' , 'Ｌ' , 'Ｍ' , 'Ｎ' , 'Ｏ' ,
        'Ｐ' , 'Ｑ' , 'Ｒ' , 'Ｓ' , 'Ｔ' ,
        'Ｕ' , 'Ｖ' , 'Ｗ' , 'Ｘ' , 'Ｙ' ,
        'Ｚ' , 'ａ' , 'ｂ' , 'ｃ' , 'ｄ' ,
        'ｅ' , 'ｆ' , 'ｇ' , 'ｈ' , 'ｉ' ,
        'ｊ' , 'ｋ' , 'ｌ' , 'ｍ' , 'ｎ' ,
        'ｏ' , 'ｐ' , 'ｑ' , 'ｒ' , 'ｓ' ,
        'ｔ' , 'ｕ' , 'ｖ' , 'ｗ' , 'ｘ' ,
        'ｙ' , 'ｚ' , '－' , '　' , '：' ,
        '．' , '，' , '／' , '％' , '＃' ,
        '！' , '＠' , '＆' , '（' , '）' ,
        '＜' , '＞' , '＂' , '＇' , '？' ,
        '［' , '］' , '｛' , '｝' , '＼' ,
        '｜' , '＋' , '＝' , '＿' , '＾' ,
        '￥' , '￣' , '｀'
    );
    $SBC = Array( // 半角
        '0', '1', '2', '3', '4',
        '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E',
        'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T',
        'U', 'V', 'W', 'X', 'Y',
        'Z', 'a', 'b', 'c', 'd',
        'e', 'f', 'g', 'h', 'i',
        'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x',
        'y', 'z', '-', ' ', ':',
        '.', ',', '/', '%', '#',
        '!', '@', '&', '(', ')',
        '<', '>', '"', '\'','?',
        '[', ']', '{', '}', '\\',
        '|', '+', '=', '_', '^',
        '$', '~', '`'
    );
    return str_replace($DBC, $SBC, $info);
}


/*
 *判断一些房源是否下架，跳转到的页面
 * */
function get_jump_url($url) {
    $url = str_replace('shtml','html',$url);
    return $url;
    $url = str_replace(' ','',$url);
    do {//do.while循环：先执行一次，判断后再是否循环
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $header = curl_exec($curl);
        curl_close($curl);
        preg_match('|Location:\s(.*?)\s|',$header,$tdl);
        if(strpos($header,"Location:")){
            $url=$tdl ? $tdl[1] :  null ;
        }else{
            return $url.'';
            break;
        }
    }
    while(strpos($header,"Location:"));
}

/**
 * 获取一维数据中的值
 * @param $data
 * @param $key
 * @param $defautl
 */
function getValue($data, $key, $defautl=''){
    return isset($data[$key]) ? $data[$key] : $defautl;
}

function dumpp($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

    /* 对象数组转为普通数组
     *
	 * AJAX提交到后台的JSON字串经decode解码后为一个对象数组，
	 * 为此必须转为普通数组后才能进行后续处理，
	 * 此函数支持多维数组处理。
	 *
	 * @param array
	 * @return array
	 */
function objarray_to_array($obj){
    $ret = array();
    foreach((array)$obj as $key => $value){
        if (gettype($value) == "array" || gettype($value) == "object"){
            $ret[$key] =  objarray_to_array($value);
        }else{
            $ret[$key] = $value;
        }
    }
    return $ret;
}

spl_autoload_register('call_function');

function call_function($class){
    $baseclass = explode('\\',$class);
    $class = $baseclass[count($baseclass)-1];
    if($class == 'PublicClass'){
        include_once '../seedrules/'.$class.'.class.php';
    }else{
        include_once '../seedrules/city/'.$baseclass[0].'/'.$baseclass[1].'.class.php';
    }
}

/**
 * 发送邮件
 * @param string $mail  发送地址
 * @param string $content 发送内容
 * @param string $title 发送主题
 */
function sendMail($mail = '', $content = '', $title = ''){
    if(!empty($mail) && !empty($content)){
        $smtpserver = "smtp.exmail.qq.com";//SMTP服务器
        $smtpserverport = 25;//SMTP服务器端口
//        $smtpusermail = "18311023519@163.com";//SMTP服务器的用户邮箱
        $smtpusermail = "noreply@zhugefang.com";//SMTP服务器的用户邮箱
        $smtpemailto = $mail;//发送给谁
//        $smtpuser = "18311023519@163.com";//SMTP服务器的用户帐号
//        $smtppass = "Asd17090148950";//SMTP服务器的用户密码
        $smtpuser = "noreply@zhugefang.com";//SMTP服务器的用户帐号
        $smtppass = "Zhugefang1116";//SMTP服务器的用户密码
        $mailsubject = "$title";//邮件主题
        $mailbody = "<h1>$title</h1>$content";//邮件内容
        $mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
##########################################
        $smtp = new smtp($smtpserver, $smtpserverport, true, $smtpuser, $smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
        $smtp->debug = TRUE;//是否显示发送的调试信息
        $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
    }
}


/**
 * 短信接口
 * @param int $phone
 * @param string $content
 * @return
 *      success
 *         array(5) {
 *             ["returnstatus"] => string(7) "Success"
 *             ["message"] => string(12) "操作成功"
 *             ["remainpoint"] => string(5) "99999"
 *             ["taskID"] => string(16) "1508281320327125"
 *             ["successCounts"] => string(1) "1"
 *           }
 *       error
 *           array(5) {
 *             ["returnstatus"] => string(5) "Faild"
 *             ["message"] => string(21) "错误的手机号码"
 *             ["remainpoint"] => string(1) "0"
 *             ["taskID"] => array(0) {
 *             }
 *             ["successCounts"] => string(1) "0"
 *           }
 *       boolean true or false
 * @author Allen
 * @version dateTime 2015-8-28
 */

function czSMS($phone, $content) {
    $url_info = parse_url("http://sms.chanzor.com:8001/sms.aspx");
    $data = "action=send&userid=&account=zhaijisou&password=152602&mobile=".$phone."&sendTime=&content=".rawurlencode($content);
    $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
    $httpheader .= "Host:" . $url_info['host'] . "\r\n";
    $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
    $httpheader .= "Content-Length:" . strlen($data) . "\r\n";
    $httpheader .= "Connection:close\r\n\r\n";
    //$httpheader .= "Connection:Keep-Alive\r\n\r\n";
    $httpheader .= $data;
    $fd = fsockopen($url_info['host'], 80);
    fwrite($fd, $httpheader);
    $gets = "";
    while(!feof($fd)) {
        $gets .= fread($fd, 128);
    }
    fclose($fd);
    $start=strpos($gets,"<?xml");
    $data=substr($gets,$start);
    $xml=simplexml_load_string($data);
    $return = json_decode(json_encode($xml),TRUE);
    if($return['successCounts'] == 1){
        return true;
    }else{
        return false;
    }
}



function getKuaiHtml($url){
    for($i=0; $i<5; $i++){
        $html = curl_get('http://proxy.zhugefang.com/proxy.php', ['url' => urlencode($url), 'passwrod'=>$_SERVER['config']['proxy_pass']], '', false);
        //$html =  @file_get_contents('http://proxy.zhugefang.com/proxy.php?url='.urlencode($url));
        if($html) break;
    }
    return $html;
}

/**
 *
 * @param string $url
 * @param string $get_type
 */
function getHtml($url = '', $get_type = 'curl'){
    if(empty($url)) return false;
    if(GETTYPE){return getKuaiHtml($url);}
    $redis = getRedisInit();
    $pushhtmlnum = 'pushhtmlnum';
    $pushnum = $redis->get($pushhtmlnum);
    if($pushnum >= $_SERVER['config']['concurrent_number']){
        sleep(1);
        return getHtml($url, $get_type);
    }
    addNum($pushhtmlnum);
    $html = getKuaiHtml($url);
    delNum($pushhtmlnum);
    return $html;

    ###############################################################################

    /*
    $host = parse_url($url, PHP_URL_HOST);
    // 蚂蚁代理
    if(in_array($host, ['www.iwjw.com'])){
        $redis = getRedisInit();
        $pushhtmlnum = 'pushhtmlnum';
        $pushnum = $redis->get($pushhtmlnum);
        if($pushnum >= $_SERVER['config']['concurrent_number']){
            sleep(1);
            return getHtml($url, $get_type);
        }
        addNum($pushhtmlnum);
        $code = getMayiHtml($url, $get_type);
        delNum($pushhtmlnum);
        return $code;
    }else{ // 快代理
        for($i=0; $i<5; $i++){
            $html =  @file_get_contents('http://proxy.zhugefang.com/proxy.php?url='.urlencode($url));
            if($html) break;
        }
        return $html;
    }
*/


    /*
    if(!empty($url)){
        $redis = getRedisInit();
        $pushhtmlnum = 'pushhtmlnum';
        $pushnum = $redis->get($pushhtmlnum);
        if($pushnum >= $_SERVER['config']['concurrent_number']){
            sleep(1);
            return getHtml($url, $get_type);
        }
        addNum($pushhtmlnum);
        $code = getMayiHtml($url, $get_type);
        delNum($pushhtmlnum);
        return $code;
    }
    return false;
    */
}

/**
 * 利用蚂蚁代理获取html
 */
function getMayiHtml($url, $get_type = 'curl'){
    if(empty($url)){return false;}
    //设置时区（使用中国时间，以免时区不同导致认证错误）
    date_default_timezone_set("Asia/Shanghai");
    //AppKey 信息，请替换
    $appKey = '31595553';
    //AppSecret 信息，请替换
    $secret = '6b0a13acfaaf3e1cfdcc49d7c19d15a5';

    //示例请求参数
    $paramMap = array(
        'app_key'   => $appKey,
        'decode-chunk' => 'true',
        'enable-simulate' => 'false',
        'retrypost' => 'false',
        'timeout' => 100000,
        'timestamp' => date('Y-m-d H:i:s'),
    );

    //按照参数名排序
    ksort($paramMap);
    //连接待加密的字符串
    $codes = $secret;

    //请求的URL参数
    $auth = 'MYH-AUTH-MD5 ';
    foreach ($paramMap as $key => $val) {
        $codes .= $key . $val;
        $auth  .= $key . '=' . $val . '&';
    }

    $codes .= $secret;

    //签名计算
    $auth .= 'sign=' . strtoupper(md5($codes));
    
    //接下来使用蚂蚁动态代理进行访问（也可以使用curl方式)
    $opts = array(
        'http' => array(
            'proxy' => '123.56.251.212:8123',
//            'proxy' => '182.92.1.222:9064',
            'protocol_version' => '1.1',
            //'proxy' => 'test.proxy.mayidaili.com:8123',
            'request_fulluri' => true,
            'header' => "Proxy-Authorization: {$auth}",
        ),
    );
    if($get_type == 'curl'){
        return Mayi_curl_content($url, $opts);
    }else{
        $context = stream_context_create($opts);
        $result = file_get_contents("compress.zlib://".$url, false, $context);
        $result = gzdecode(chunked_decode($result));
        return $result;
    }
}

function Mayi_curl_content($requestUrl, $headr){
    //初始化
    $ch = curl_init();
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_ACCEPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_PROXY, $headr['http']['proxy']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $header['header'] = $headr['http']['header'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, CURL_HTTP_VERSION_1_1);
    //执行并获取HTML文档内容
    $output = curl_exec($ch);

    //释放curl句柄
    curl_close($ch);
    //打印获得的数据

    return $output;
}

function chunked_decode($in){
    $out = '';
    while ($in != '') {
        $lf_pos = strpos ($in, "\012");
        if ($lf_pos === false) {  $out .= $in; break;  }
        $chunk_hex = trim (substr ($in, 0, $lf_pos));
        $sc_pos = strpos ($chunk_hex, ';');
        if ($sc_pos !== false) $chunk_hex = substr ($chunk_hex, 0, $sc_pos);
        if ($chunk_hex == ''){
            $out .= substr ($in, 0, $lf_pos);
            $in = substr ($in, $lf_pos + 1);
            continue;
        }
        $chunk_len = hexdec ($chunk_hex);
        if ($chunk_len){
            $out .= substr ($in, $lf_pos + 1, $chunk_len);
            $in = substr ($in, $lf_pos + 2 + $chunk_len);
        } else {
            $in = '';
        }
    }
    return $out;
}

/**
 * 获取定义内容
 * @param string $url
 * @param array $rules
 * @return bool
 */
function queryList($url = '', $rules = []){
    if(!empty($url) && !empty($rules) && is_array($rules)){
        $data = \QL\QueryList::Query($url,$rules)->data;
        return $data;
    }
    return false;
}


//获取参数值
function getArgv($type, $argv){
    $result = '';
    if(in_array($type, $argv)){
        $key = array_search($type, $argv);
        $result = isset($argv[$key + 1]) ? $argv[$key + 1] : '';
    }
    return $result;
}

function getArgvValues($keys = [], $argv = []){
    if(!empty($keys) && !empty($argv)){
        $data = [];
        foreach((array)$keys as $value){
            $data[$value] = getArgv($value, $argv);
        }
        return $data;
    }
    return false;
}






//给渠道分配端口
function getSourcePort($name = '', $type = 'seed'){
    if(!empty($name)){
        if(isExistsStr($name, '-')){
            $data = explode('-', $name);
            $city = $data[0];
            $source = $data[1];
            $filepath = '../seedrules/city/' .$city.'/'.$source.'.class.php';
            if(file_exists($filepath)){
                return getPort($type);
            }
        }
    }
    return false;
}

function getPort($type = 'seed'){
    $redis = getRedisInit();
    $port = $redis->get('port_'.$type);
    if(!empty($port) && $port < 50000){
        $port = $redis->increment('port_'.$type);
    }else{
        $port = 9501;
        $redis->setNum('port_'.$type, $port);
    }
    return $port;
}


/**
 * @param $fileName
 * @param $data
 * @author robert
 */
function writeLog($fileName, $data, $debugFlag = true){
    if(!$debugFlag) return false;
    $log = date('Y-m-d H:i:s').PHP_EOL;
    $log .= json_encode($data).PHP_EOL.PHP_EOL;
    file_put_contents("../logs/{$fileName}.log", $log, FILE_APPEND);
}


function tmpSourceUrl($name, $url = ''){
    if(!empty($url)){
        $redis = getRedisInit();
        $redis->hset($name.'_tmp', md5($url), $url);
    }
}

function delTmpSourceUrl($name = '', $url = ''){
    if(!empty($url)){
        $redis = getRedisInit();
        $redis->hDel($name.'_tmp', md5($url));
    }
}

function settingTmpSourceUrl($name = ''){
    if(!empty($name)){
        $redis = getRedisInit();
        $data = $redis->hvals($name.'_tmp');
        foreach((array)$data as $key => $value){
            $redis->push($name.'_key',  $value);
        }
    }
}

function settingSourceStatus($source = '', $status = ''){
    if(!empty($source) && !empty($status)){
        $rediskey = $source.'_status';
        $redis = getRedisInit();
        $redis->set($rediskey, $status);
    }
}

function getSourceStatus($source = ''){
    if(!empty($source)){
        $rediskey = $source.'_status';
        $redis = getRedisInit();
        return $redis->get($rediskey);
    }
}

/**
 * 初始化爬取种子进度
 */
function initPageSourceStatus($source = ''){
    if(!empty($source)){
        $key = $source.'_page_status';
        $redis = getRedisInit();
        $redis->delete($key);
    }
}

function setPageSourceStatus($source = ''){
    if(!empty($source)){
        $key = $source.'_page_status';
        $redis = getRedisInit();
        return $redis->increment($key);
    }
}

function getCityList(){
    return [
        "beijing" => "北京",
        "shanghai" => "上海",
        "shenzhen" => "深圳",
        "wuhan" => "武汉",
        "chendu" => "成都",
        "nanjing" => "南京",
        "tianjin" => "天津",
        "hangzhou" => "杭州",
        "chongqing" => "重庆",
        "shenyang" => "沈阳",
        "dalian" => "大连",
        "qingdao" => "青岛",
        "suzhou" => "苏州",
        "sanya" => "三亚",
        "zhengzhou" => "郑州",
        "guangzhou" => "广州"
    ];
}

function getIp(){
    $getid = function (){
        return rand(1, 254);
    };
    return $getid.'.'.$getid.'.'.$getid.'.'.$getid;
}

/**
 * 更新进程信息
 * @param string $name 渠道名
 * @param string $server 服务类型(server:种子 detail_server: 内容 updetail_server: 更新
 * @param string $keyShell 服务参数
 * @param int $min 检测时间 （分钟数）
 */
function joinControl($name = '', $server = '', $keyShell = '', $min = 15){
    if(!empty($name)){
        $key = $name.'_'.$server;
    }else{
        $key = $server;
    }
    if(!empty($key)){
        $value['time'] = time();
        $value['min'] = $min*60;
        if($server == 'updetail_server'){
            $source = '"updetail_server.php"';
        }else{
            $source = '"' . $server . '.php -name '.$name.'"';
        }
        //杀死僵死的进程
        $killcli = 'ps -ef  |  grep '.$source.'  |  grep -v grep  |  cut -c 9-15  |  xargs kill -s 9';

        //重新启动进程
        $keyShell = str_replace('-all', ' ', $keyShell);
        $type = ($server == 'server') ? 'list' : $server;
        $keyShell = <<<EOD
gnome-terminal -t "$name$type" --geometry=45x15 -x bash -c "cd ../server;php $keyShell;exec bash;"
EOD;
        $keyShell = $killcli.' & '.$keyShell;
        $value['run'] = $keyShell;
        $value = implode('@', $value);
        $redis = getRedisInit();
        $redis->setNum($key, $value);
    }
    return false;
}