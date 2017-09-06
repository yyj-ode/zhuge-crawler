<?php
include_once '../common/common.php';
$status = false;
$data = [];
define('GETTYPE', true);
$city = $_GET['city'];
$source = $_GET['source'];
$url = $_GET['url'];
if(!empty($city) && !empty($source)){
    $filepath = '../seedrules/city/' .$city.'/'.$source.'.class.php';
    if(file_exists($filepath)){
        include_once $filepath;
        $classname = $city.'\\'.$source;
        $sourceclass = new $classname();
        $status = true;
        if(!empty($url)){
            $source_data = callSeed($city.'/'.$source, 'house_list', $url);
            if(!empty($source_data)){
                foreach((array)$source_data as $k => $v){
                    if(!empty($v)){
                        saveUrl($v, $city.'/'.$source, false);
                        $data[] = $v;
                    }
                }
            }
        }else{
            if(is_callable([$sourceclass, 'callNewData'])){
                $data = $sourceclass->callNewData();
                
    //            if(!empty($urllist)){
    //                $status = true;
    //                foreach((array)$urllist as $key => $value){
    //                    $source_data = callSeed($city.'/'.$source, 'house_list', $value);
    //                    if(!empty($source_data)){
    //                        foreach((array)$source_data as $k => $v){
    //                            if(!empty($v)){
    //                                saveUrl($v, $city.'/'.$source, false);
    //                                $data[] = $v;
    //                            }
    //                        }
    //                    }
    //                    sleep(mt_rand(0, 3));
    //                }
    //            }
            }
        }
    }
}

returnJsonData($status, $data);

function returnJsonData($status, $data){
    die(json_encode(['status' => $status, 'data' => $data]));
}

