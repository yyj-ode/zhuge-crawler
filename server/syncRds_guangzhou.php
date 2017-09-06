<?php
include_once '../common/common.php';
include_once '../common/Mysql.php';

//$citys = getCitys();
//foreach((array)$citys as $key => $value){
//
//}
$city = 'guangzhou';
$config['mysql']['dbname'] = $config['mysql'][$city.'_dbname'];
$mysql = new washData($config['mysql']);
//$sql = "select source_url,source,source_name from house_sell_gov where house_pic_unit = '' ";
$sql = "select source_url,source,source_name from house_sell_gov ORDER BY updated ";
$list = $mysql->getData($sql);
$data = $list[0];
$status = $list[1];
execRedis($data);
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