<?php
include_once '../common/common.php';
include_once '../common/Mysql.php';

//$citys = getCitys();
//foreach((array)$citys as $key => $value){
//
//}
$city = 'beijing';
$config['mysql']['dbname'] = $config['mysql'][$city.'_dbname'];
$mysql = new washData($config['mysql']);
$sql = "SELECT source_url,source,source_name FROM house_sell_gov where source != 1;";
$list = $mysql->getData($sql);
$data = $list[0];
$status = $list[1];
execRedis($data);





function execRedis($data){
    $redis = getRedisInit();
    foreach((array)$data as $key => $value){
        if(!empty($value['source_url']) && !empty($value['source_name'])){
            
//            $source_list = str_replace('/', '-', $value['source_name']).'_list';
//            $md5 = md5($value['source_url']);
//            if(!$redis->hexists($source_list, $md5)){  //插入更新队列
//                $redis->hset($source_list, $md5, $value['source_name']);
//            }
            $redis->push('up_url', $value['source_url'].'|||'.$value['source_name']);
            $ids[] = $value['id'];
        }
    }
}
var_dump($data);