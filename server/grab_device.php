<?php
include_once '../common/common.php';
$num = isset($_GET['num']) ? $_GET['num'] : 100; //默认100
$i = 0;

for($i = 0; $i < $num; $i++){
    if($info = getUrl()){
        $source = $info['source'];
        $source_url = $info['source_url'];

        echo date('Y-m-d H:i:s') . '----' . '开始抓取: '.$source_url.'渠道：'.$source."\r\n";
        echo '-------------------------------------------'."\r\n";
        if($res = checkContent($source, $source_url)){
            $t = microtime();
            echo $source_url.'获取数据时间'.(microtime() - $t)."\r\n";
            /************/
            $sourcetag = getSourceUrlTag();
            if(isExistsStr($source_url, $sourcetag)){  //判断是否自定义渠道url
                $source_url = explode($sourcetag, $source_url);
                $source_url = $source_url[1];
            }
            $res['source'] = $source;
            $res['source_url'] = $source_url;
            /************/
            $t = microtime();
            saveContent($res, $source);
            echo '写入时间：'.(microtime()-$t).'毫秒'."\r\n";
        }else{
            //记录不良种子数量
            illegalSeed($source);
        }
    }else{
        break;
    }
}