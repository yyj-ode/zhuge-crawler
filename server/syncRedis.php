<?php
include_once '../common/common.php';
$citys = getCitys();
$num = empty($_GET) ? 2000 : $_GET;
$redis = getRedisInit(); //本地redis
$alizhonyunredis = new redisInit($config['alizhonyunredis']); //阿里云redis
foreach((array)$citys as $key => $value){
    $path = '../seedrules/city/'.$value.'/';
    $files = getFiles($path);
    foreach((array)$files as $k => $v){
        if(isExistsStr($v, '.class.php')){
            if(str_replace('.class.php', '', $v) != 'PublicClass'){
                $sourcename = str_replace('.class.php', '', $v);
                $rediskey = $value.'-'.$sourcename;
                $data = [];
                for($i = 0; $i < $num; $i++){
                    $d = $redis->pop($rediskey);
                    if(!empty($d)){
                        $data[] = $d;
                    }else{
                        break;
                    }
                }
                //传到阿里的redis中
                foreach((array)$data as $v){
                    $alizhonyunredis->push($rediskey, $v);
                }
            }
        }
    }
}
$redis->close();
$alizhonyunredis->close();
