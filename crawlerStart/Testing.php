<?php
/**
 * Created by PhpStorm.
 * User: Tony
 * Date: 2016/9/13
 * Time: 21:54
 */
//偷个懒 写的有点乱
include_once '../common/common.php';
$citylist = getCityList();
$mailList = [];
foreach((array)$citylist as $cityname => $value){
    $path = '../seedrules/city/'.$cityname.'/';
    $files = getFiles($path);
    foreach((array)$files as $k => $v){
        if(isExistsStr($v, '.class.php')){
            $file = str_replace('.class.php', '', $v);
            if($file != 'PublicClass' && !isExistsStr($file, ' ') && !isExistsStr($file, 'Rent') && !isExistsStr($file, 'Hezu')){
                $content = callSeedTest($cityname.'/'.$file, 'callNewData');
                if(!empty($content)){
                    $mailList[] = $content;
                    break;
                }
                $list_urls = callNewData($cityname.'-'.$file);
                if(empty($list_urls)){
                    $list_urls = callNewData($cityname.'-'.$file);
                    if(empty($list_urls)){
                        $list_urls = callNewData($cityname.'-'.$file);
                    }
                }
                checkListUrl($cityname.'-'.$file, $list_urls);
            }
        }
        $i++;
    }
}
var_dump($mailList);


//监测方法是否存在
function callSeedTest($source = '', $func = '', $url = ''){
    $loca = $_SERVER['SERVER_NAME'];
    $url = 'http://'.$loca.'/crawlerStart/callSeedTest.php'.'?source='.$source.'&func='.$func.'&url='.$url;
    return curl_get($url);
}

//调用liest
function callNewData($source){
    $loca = $_SERVER['SERVER_NAME'];
    $url = 'http://'.$loca.'/crawlerStart/test.php'.'?file='.$source.'&func=callNewData&type=api';
    return curl_get($url);
}

//获取详情种子
function getHouseList($source, $list_url){
    $loca = $_SERVER['SERVER_NAME'];
    $url = 'http://'.$loca.'/crawlerStart/test.php'.'?file='.$source.'&func=house_list&url='.$list_url.'&type=api';
    return curl_get($url);
}

function callDeatil($source, $url){
    $loca = $_SERVER['SERVER_NAME'];
    $url = 'http://'.$loca.'/crawlerStart/test.php'.'?file='.$source.'&func=house_detail&url='.$url.'&type=api';
    return curl_get($url);
}

function checkListUrl($source, $list_url){
    if(checkDataSource($filename, 'rent')){
        $type = 'rent';
    }elseif(checkDataSource($filename, 'hezu')){
        $type = 'hezu';
    }else{
        $type = 'ershoufang';
    }
    $pageurl = [];
    $i = 1;
    $errorurl = [];
    foreach((array)$list_url as $key => $value){
        if($i > 2){ //取前俩个list来做监测
            $url = getHouseList($value);
            if(empty($url)){
                $url = getHouseList($value);
                if(empty($url)){
                    $url = getHouseList($value);
                }
            }
            $pageurl[] = $url;
        }
        $i++;
    }
    if(!empty($pageurl)){
        $fields = getHouseTypeFields($type);
        foreach((array)$pageurl as $key => $value){
            $content = callDeatil($value);
            if(empty($content)){
                $content = callDeatil($value);
                if(empty($content)){
                    $content = callDeatil($value);
                }
            }
            $content['source_url'] = $value;
            foreach((array)$fields as $field){
                if(empty($content[$field])){
                    $errorurl[$key]['source_url'] = $content['source_url'];
                    $errorurl[$key]['content'] = $content;
                    $errorurl[$key]['emptylist'][] = $field;
                }
            }
        }
        if(!empty($errorurl)){
            if(count($errorurl) >= 10){
                //todo
                return ['title' => $source.'规则有变化', 'content' => $source.'规则有变化'];
            }
        }
    }
    return false;
}

function getHouseTypeFields($type = 'ershoufang'){
    $checkFields = [
        'ershoufang' => [
            'borough_name',
            'house_totalarea',
            'house_room',
            'house_topfloor',
            'house_price',
        ],
    ];
}


function checkDataSource($source = '', $type = ''){
    if (!empty($source) && !empty($type)) {
        return isExistsStr(strtolower($source), strtolower($type));
    }
}