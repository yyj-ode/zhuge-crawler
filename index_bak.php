<?php
 die('test');
header("Content-type: text/html; charset=utf-8");
header("Content-type: text/html; charset=utf-8");
$URL="http://bj.lianjia.com/ershoufang/";
$html = file_get_contents($URL);
preg_match('/区域：([\x{0000}-\x{ffff}]+?)筛选/u',$html,$message);
preg_match_all('/option\-list([\x{0000}-\x{ffff}]+?)<\/dl>/u',$message[1],$condition);
$condition = $condition[1];

//城区搜索条件
preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[0],$dis);
preg_match_all('/<a\shref=\"\/ershoufang\/([\x{0000}-\x{ffff}]+?)\//u',$dis[1],$dis);
foreach ($dis[1] as $v){
    $url=$URL.$v;
    $html = file_get_contents($url);
    preg_match('/区域：([\x{0000}-\x{ffff}]+?)筛选/u',$html,$message);
    preg_match_all('/sub\-option\-list([\x{0000}-\x{ffff}]+?)<\/dd>/u',$message[1],$condition);
    $condition = $condition[1];
    preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[0],$sdis);
    preg_match_all('/<a\shref=\"\/ershoufang\/([\x{0000}-\x{ffff}]+?)\//u',$sdis[1],$sdis);
    foreach ($sdis[1] as $vv){
        $surl=$URL.$vv;
        $html = file_get_contents($surl);
        preg_match('/totalPage\":(\d+)/u',$html,$page);
        $page=$page[1];
        for($i=1;$i<=$page;$i++){
            $uurl=$surl."/pg".$i."/";
        }
    }
}


// 浏览器友好的变量输出
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}
?>
