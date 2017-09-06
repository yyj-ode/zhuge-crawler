<?php
include_once '../common/common.php';
include_once '../common/Mysql.php';

//$citys = getCitys();
//foreach((array)$citys as $key => $value){
//
//}
$city = 'shenzhen';
$config['mysql']['dbname'] = $config['mysql'][$city.'_dbname'];
$mysql = new washData($config['mysql']);
//$sql = "select source_url,source,source_name from house_sell_gov where house_pic_unit = '' ";

$sql = "select id ,source_url,source,source_name from house_sell_gov_test a where a.borough_name is NULL OR a.borough_name=''";

$list = $mysql->getData($sql);
$data = $list[0];
$status = $list[1];
execRedis($data);
$ids = [];
foreach((array)$data as $key => $value){
    $ids[] = $value['id'];
}
$mysql->delBadData($ids, 'house_sell_gov_test');
echo "update ok".PHP_EOL;



function execRedis($data){
    $redis = getRedisInit();
    foreach((array)$data as $key => $value){
        if(!empty($value['source_url']) && !empty($value['source_name'])){
            $redis->push('up_url', $value['source_url'].'|||'.$value['source_name']);
            $ids[] = $value['id'];
        }
    }
}