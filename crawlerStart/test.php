<?php
include_once '../common/common.php';
if($_GET['type'] != 'api'){
    if(isExistsStr($_GET['file'], 'Deal')){
        echo "<p>小区名：borough_name</p>";
        echo "<p>成交时间：finish_time(年-月-日)</p>";
        echo "<p>成交价格：finish_price</p>";
        echo "<p>房屋面积：house_area</p>";
        echo "<p>总楼层：house_room</p>";
        echo "<p>厅：house_hall</p>";
        echo "<p>朝向：house_toward</p>";
        echo "<p>楼层：house_floor</p>";
        echo "<p>总楼层：house_topfloor</p>";
        echo "<p>经纪人姓名：broker_name</p>";
        echo "<p>成交经纪公司名：company_name</p>";
        echo "<p>楼号：building_number</p>";
    }else{
        echo "<p>小区名：borough_name</p>";
        echo "<p>房屋面积：house_totalarea</p>";
        echo "<p>房屋面积：house_room_totalarea</p>";
        echo "<p>卧室：house_room</p>";
        echo "<p>总楼层：house_topfloor</p>";
        echo "<p>出租价格：house_price</p>";
        echo "<p>城区：cityarea_id</p>";
        echo "<p>商圈：cityarea2_id</p>";
        echo "<p>联系人：owner_name</p>";
        echo "<p>电话号码：owner_phone</p>";
        echo "<p>房源图片：house_pic_unit</p>";
        echo "<p>电话号码：owner_phone</p>";
        echo "<p>下架：off_type(1 下架，2正常)</p>";
        echo "<br/>";
    }
}

$city = $_GET['city'];
if(!empty($city)){
    $path = '../seedrules/city/'.$city.'/';
    $files = getFiles($path);
    foreach((array)$files as $k => $v){
        $filename = $city.'-'.str_replace('.class.php', '', $v);
        $file[] = $filename;
        testSource($filename, true);
    }
}else{
    testSource();
}
function testSource($filename = '', $allstatus = false){
    if(empty($filename)){
        $filename = $_GET['file'];
    }
    if(!empty($_GET['filepath'])){
        $filename = 'fdsf-fds';
        if(empty($_GET['func'])){
            $_GET['func'] = 'house_page';
        }
    }
    if(!empty($filename) || !empty($_GET['filepath'])){
        $filename = str_replace('-', '/', $filename);
        if(!empty($_GET['func'])){
            if($_GET['func'] == 'house_detail'){
                $d = retryContent($filename, $_GET['url']);
            }else{
                $d = callSeedTest($filename, $_GET['func'], $_GET['url']);
            }
            if($_GET['type'] == 'api'){
                die(json_encode($d));
            }
            echo '---------------------------------------';
            dumpp($d);
            echo '---------------------------------------';
            exit;
        }
        /*****************/
        $renttype = [
            'b' => [
                'borough_name',
                'house_totalarea',
                'house_room',
                'house_topfloor',
                'house_price',
                'cityarea_id',
                'cityarea2_id',
                'off_type',
            ],
            'f' => [
                'owner_name',
                'owner_phone',
                'house_pic_unit',
            ],
        ];
        $hezutype = [
            'b' => [
                'borough_name',
                'house_room_totalarea',
                'house_room',
                'house_topfloor',
                'house_price',
                'cityarea_id',
                'cityarea2_id',
                'off_type',
            ],
            'f' => [
                'owner_name',
                'owner_phone',
                'house_pic_unit',
            ],
        ];
        $type = [
            'b' => [
                'borough_name',
                'house_totalarea',
                'house_room',
                'house_topfloor',
                'house_price',
                'cityarea_id',
                'cityarea2_id',
                'off_type',
            ],
            'f' => [
                'owner_name',
                'owner_phone',
                'house_pic_unit',
            ],
        ];
        /*****************/

        $num = !empty($_GET['page']) ? $_GET['page'] : 2;
        if($allstatus){
            $num = 1;
        }
        $crawlerpage = callSeedTest($filename, 'house_page');
        $i = 0;
        $d = [];
        $sourcecontent = 0;
        foreach((array)$crawlerpage as $key => $value){
            if($i == $num){
                break;
            }
            echo "<pre>";
            echo $filename."\r\n";
            echo date('Y-m-d H:i:s').'分页URL------['.$value."]\r\n";
            echo '---------------------------------------';
            $crawlerlist = callSeedTest($filename, 'house_list', $value);
            foreach((array)$crawlerlist as $v){
                echo '          详情URL------------['.$v."]\r\n";
                $detaildata = retryContent($filename, $v);
                echo '---------------------------------------';
                $status = [];
                $notice = [];
                if(checkDataSource($filename, 'rent')){
                    $type = $renttype;
                }elseif(checkDataSource($filename, 'hezu')){
                    $type = $hezutype;
                }

                foreach((array)$type['b'] as $k => $types){
                    if(empty($detaildata[$types])){
                        $status[] = $types;
                    }
                }
                foreach((array)$type['f'] as $k => $types){
                    if(empty($detaildata[$types])){
                        $notice[] = $types;
                    }
                }

                dumpp($detaildata);
                if(!isExistsStr($filename, 'Deal')){
                    if(!empty($status)){ //TODO 有必填为空
                        foreach((array)$status as $stat){
                            echo "<font color='red'><b>$stat</b></font>";
                            echo "<br/>";
                        }
                    }
                    if(!empty($notice)){ //TODO 有警告为空
                        foreach((array)$notice as $not){
                            echo "<font color='blue'><b>$not</b></font>";
                            echo "<br/>";
                        }
                    }
                }
                echo '---------------------------------------';
                if($allstatus){
                    if(!empty($detaildata)){
                        $sourcecontent++;
                    }
                    break;
                }
            }
            $i++;
        }
    }
}

function checkDataSource($source = '', $type = ''){
    if (!empty($source) && !empty($type)) {
        return isExistsStr(strtolower($source), strtolower($type));
    }
}


/**
 * 调用生成种子
 * @param $source
 * @param $func
 * @param $url
 */
function callSeedTest($source = '', $func = '', $url = ''){
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
        if(!empty($_GET['filepath'])){
            $filepath = '../'.$_GET['filepath'];
            $class = $_GET['class'];
            $city = $_GET['city'];
        }
        if(file_exists($filepath)){
            include_once $filepath;
            $classname = $city.'\\'.$class;
            $sourceclass = new $classname();
//            $sourceclass = new $class($city);
            return $sourceclass->$func($url);
        }
    }
}