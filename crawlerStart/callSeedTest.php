<?php
include_once '../common/common.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
callSeedTest($_GET['source'], $_GET['func'], $_GET['url']);
/**
 * 调用生成种子
 * @param $source
 * @param $func
 * @param $url
 */
function callSeedTest($source = '', $func = '', $url = ''){
    if(!empty($source) && !empty($func)){
        $citynamelist = getCityList();
        
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
        $classname = $city.'\\'.$class;
        if(file_exists($filepath)){
            include_once $filepath;
            if(class_exists($classname)){
                $sourceclass = new $classname();
                if(!is_callable([$sourceclass, $func])){
                    $title = getSourceName($city.'/'.$class).$func.'方法不存在！';
                    $content = $title;
                    die(json_encode(['title' => $title, 'content' => $content]));
    //                sendMail('tony@zhugefang.com', $content, $title);
                }
            }else{
                $title = getSourceName($city.'/'.$class).$class.'类不存在！';
                $content = $title;
                die(json_encode(['title' => $title, 'content' => $content]));
            }
        }
    }
}



/**
 * 获取渠道名称
 */
function getSourceName($source = ''){
    if(!empty($source)){
        $sourcepath = '../seedrules/city/'.$source.'.class.php';
        if(file_exists($sourcepath)){
            $content = file_get_contents($sourcepath);
            $sourceinfo = getApiName($content);
            return $sourceinfo['classname'];
        }
    }
    return false;
}

function getApiName($code) {
    $data = explode('/**', $code);
    $temp = explode('*/', $data[1]);
    return analysisDesc($temp[0]);
}

function analysisDesc($str){
    $arr = explode("\n", $str);
    $d = array();
    foreach ((array) $arr as $key => $value) {
        if(isExistsStr($value, '@description')){
            $d['description'] = trim(str_replace('* @description', '', $value));
        }
        if(isExistsStr($value, '@classname')){
            $d['classname'] = trim(str_replace('* @classname', '', $value));
        }
    }
    return $d;
}