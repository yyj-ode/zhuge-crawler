<?php
//设置header头
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');


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
            if($func == 'house_page'){
                return '';
            }
            $classname = $city.'\\'.$class;
            $sourceclass = new $classname();
//            $sourceclass = new $class($city);
            $data =  $sourceclass->$func($url);
            if($func == 'house_page'){
                //将渠道分页前N页url缓存，当天全站抓取就一次
                $num = 20;
                $i = 1;
                $d = [];
                foreach((array)$data as $key => $value){
                    if($i > $num){
                        break;
                    }
                    $d[] = $value;
                    $i++;
                }
                //将渠道的前几页url存到redis中
                $redis = getRedisInit();
                $redis->set($source.'pageUrl', $d);
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
function getUrl(){
    $redis = getRedisInit();
    if($redis->exists('url')){
        $url = $redis->pop('url');
        if (!empty($url)) {
            $redis->push('url', $url, false); //在把url放到队尾(持久化抓取)
            $source = $redis->get(md5($url));
//        $redis->delete(md5($url));
            return ['source_url' => $url, 'source' => $source];
        }
    }
    return false;
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
function saveUrl($url = '', $source = ''){
    if(!empty($url) && !empty($source)){
        $redis = getRedisInit();
        $md5url = md5($url);
        saveSeedCount();//总爬取数量
        if(!$redis->exists($md5url)){
            $redis->push('url', $url);
            $redis->set($md5url, $source);
            saveSeed($source);  //记录渠道种子数
        }else{
            echo date('Y-m-d H:i:s').'该房源已存在：'.$url."\r\n";
        }
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
function saveContent($document, $source){
//    $status = saveMysql(json_encode($document), $source); //写入数据库
    saveSourceContent();
    $redis = getRedisInit();
    $source_url = $document['source_url'];
    $status = false;
    $key = md5($source_url.'_content');
    if($redis->exists($key)){
        if($redis->get($key) != md5(json_encode($document))){
            $status = true;
        }
    }else{
        $status = true;
    }
    if($status){
        $redis->set($key, md5(json_encode($document)));
        $status = saveRedis($document, $source);   //写入redis
        saveSourceNum($source);
        return $status;
    }else{
//        echo '房源无变化'."\r\n";
    }
}

function saveSourceContent(){
    addNum(date('Y-m-d').'-content-count'); //每天抓取总增量
}

function saveSourceNum($source){
    addNum($source); //渠道总增量
    addNum(date('Y-m-d').'-newcontent-count'); //每天抓取新内容总增量
    addNum(date('Y-m-d').'-'.$source.'-content-count'); //每天渠道新内容抓取总增量
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
    $city = $city[0];
    $redis->push($city, json_encode($document));
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
 * 获取redis初始化
 * @return redisInit
 */
function getRedisInit(){
    try{
        if(empty($_SERVER['redisobj'])){
            $ip = serverIP();
//    $ip = get_real_ip();
            $ip = explode('.', $ip);
            if ($ip[0] != '192') { //如果是阿里的服务器则直接连接阿里redis服务，不需要做中转
                $_SERVER['redisobj'] = new redisInit($_SERVER['config']['alipayredis']);
            } else {
                $_SERVER['redisobj'] = new redisInit($_SERVER['config']['redis']);
            }
        }
        return $_SERVER['redisobj'];
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
        return $CrawlerSource['content'];
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
        if($file == "."||$file == ".."){continue;}
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
    $snoopy->rawheaders["X_FORWARDED_FOR"] = "118.244.149.153"; //伪装ip
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
    $class = explode('\\',$class);
    $class = $class[count($class)-1];
    if($class == 'PublicClass'){
        include_once '../seedrules/'.$class.'.class.php';
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
        $smtpserver = "smtp.163.com";//SMTP服务器
        $smtpserverport = 25;//SMTP服务器端口
        $smtpusermail = "18311023519@163.com";//SMTP服务器的用户邮箱
        $smtpemailto = $mail;//发送给谁
        $smtpuser = "18311023519@163.com";//SMTP服务器的用户帐号
        $smtppass = "Asd17090148950";//SMTP服务器的用户密码
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