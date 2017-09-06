<?php
include_once '../common/common.php';
//爬取种子总增量 seed-count
//每天爬取种子总增量  ｛年－月－日}seed-count
//
//渠道种子总数     {城市／渠道}seed
//每天渠道新种子总增量     ｛年－月－日}-{城市／渠道}seed
//每天新种子总增量     {年-月-日}-seed-count
//
//每天抓取内容总增量     {年-月-日}-content-count
//
//每天抓取新内容总增量     {年-月-日}-newcontent-count
//每天渠道新内容抓取总增量      {年-月-日}-{城市／渠道}-content-count
//渠道总增量     {城市／渠道}
//
//
//
//每天总增量房源   ｛年－月－日｝_count
//每天渠道总增量房源｛年－月－日｝_｛城市－渠道｝_count
//渠道房源总数  {城市－渠道｝_count
//房源总数  source_count

$reids = getRedisInit();
$content = [];
$date = date('Y-m-d');
$datename = date('Y年m月d日');
$content[] = '爬取链接总量: '.$reids->get('seed-count');
$content[] = '新增抓取链接总量: '.$reids->get('Grab-count');
$content[] = '更新队列抓取链接总量: '.$reids->get('upGrab-count');

$d = '<table border="1" class="imagetable">';
$d .= '<tr>';
$d .= '<th>城市</th>';
$d .= '<th>整租总量</th>';
$d .= '<th>合租总量</th>';
$d .= '<th>二手房总量</th>';
$d .= '</tr>';
foreach((array)getCityList() as $key => $value){
    if(!empty($config['sendMailCity'])){
        if(in_array($key, $config['sendMailCity'])){
            $d .= '<tr>';
            $d .= '<td>'.$value.'</td>';
            $d .= '<td>'.$reids->get($key.'_rent_all_count').'</td>';
            $d .= '<td>'.$reids->get($key.'_hezu_all_count').'</td>';
            $d .= '<td>'.$reids->get($key.'_sell_all_count').'</td>';
            $d .= '</tr>';
        }
    }else{
        $d .= '<tr>';
        $d .= '<td>'.$value.'</td>';
        $d .= '<td>'.$reids->get($key.'_rent_all_count').'</td>';
        $d .= '<td>'.$reids->get($key.'_hezu_all_count').'</td>';
        $d .= '<td>'.$reids->get($key.'_sell_all_count').'</td>';
        $d .= '</tr>';
    }
//    $sourcename = getSourceName($value).'('.getSourceType($value).')';
//    $content[] = '＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋';
//    $content[] = $sourcename.': ';
//    $content[] = '链接总量： '.$reids->get($value.'seed');
//    $content[] = '新增链接总量： '.$reids->get($date.'-'.$value.'seed');
//    $content[] = '新增房源： '.$reids->get($date.'-'.$value.'-content-count');
//    $content[] = '实际新增房源： '.$reids->get($date.'_'.$value.'_count');
//    $content[] = '房源总量: '.$reids->get($value.'_count');
//    $content[] = 'ETL: '.$reids->get($value.'etlnum');
//    $content[] = '＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋';

}
$content[] = $d;

$content[] = $datename.': ';
$content[] = '爬取链接: '.$reids->get($date.'seed-count');
$content[] = '新增链接: '.$reids->get($date.'-seed-count');
$content[] = '抓取链接: '.$reids->get($date.'Grab-count');
$content[] = '抓取房源: '.$reids->get($date.'-content-count');
$content[] = '新增房源: '.$reids->get($date.'-newcontent-count');
$content[] = '实际新增房源: '.$reids->get($date.'_count');
$content[] = '+++++++++++++++++++++++++++++++++++++++++++++';
$content[] = '更新队列抓取链接: '.$reids->get($date.'upGrab-count');
$content[] = '更新队列抓取房源: '.$reids->get($date.'-upcontent-count');

/*****渠道*****/
$sourcelist = getSourceAll();
$content[] = <<<EOD
<style type="text/css">
    table.imagetable {
    font-family: verdana,arial,sans-serif;
	font-size:11px;
	color:#333333;
	border-width: 1px;
	border-color: #999999;
	border-collapse: collapse;
    width: 800px;
}
table.imagetable th {
    background:#b5cfd2 url('cell-blue.jpg');
    border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}
table.imagetable td {
    background:#dcddc0 url('cell-grey.jpg');
    border-width: 1px;
	padding: 8px;
	border-style: solid;
	border-color: #999999;
}
</style>
EOD;
$d = '<table border="1" class="imagetable">';
    $d .= '<tr>';
        $d .= '<th>渠道</th>';
        $d .= '<th>链接总量</th>';
        $d .= '<th>新增链接总量</th>';
        $d .= '<th>新增房源</th>';
        $d .= '<th>实际新增房源</th>';
        $d .= '<th>房源总量</th>';
    $d .= '</tr>';
foreach((array)$sourcelist as $key => $value){
    if(!empty($config['sendMailCity'])){
        $cityname = explode('/', $value);
        $cityname = $cityname[0];
        if(in_array($cityname, $config['sendMailCity'])){
            $d .= '<tr>';
            $sourcename = getSourceName($value).'('.getSourceType($value).')';
            $d .= '<td>'.$sourcename.'</td>';
            $d .= '<td>'.$reids->get($value.'seed').'</td>';
            $d .= '<td>'.$reids->get($date.'-'.$value.'seed').'</td>';
            $d .= '<td>'.$reids->get($date.'-'.$value.'-content-count').'</td>';
            $d .= '<td>'.$reids->get($date.'_'.$value.'_count').'</td>';
            $d .= '<td>'.$reids->get($value.'_count').'</td>';
            $d .= '</tr>';
        }
    }else{
        $d .= '<tr>';
        $sourcename = getSourceName($value).'('.getSourceType($value).')';
        $d .= '<td>'.$sourcename.'</td>';
        $d .= '<td>'.$reids->get($value.'seed').'</td>';
        $d .= '<td>'.$reids->get($date.'-'.$value.'seed').'</td>';
        $d .= '<td>'.$reids->get($date.'-'.$value.'-content-count').'</td>';
        $d .= '<td>'.$reids->get($date.'_'.$value.'_count').'</td>';
        $d .= '<td>'.$reids->get($value.'_count').'</td>';
        $d .= '</tr>';
    }
    
//    $sourcename = getSourceName($value).'('.getSourceType($value).')';
//    $content[] = '＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋';
//    $content[] = $sourcename.': ';
//    $content[] = '链接总量： '.$reids->get($value.'seed');
//    $content[] = '新增链接总量： '.$reids->get($date.'-'.$value.'seed');
//    $content[] = '新增房源： '.$reids->get($date.'-'.$value.'-content-count');
//    $content[] = '实际新增房源： '.$reids->get($date.'_'.$value.'_count');
//    $content[] = '房源总量: '.$reids->get($value.'_count');
//    $content[] = 'ETL: '.$reids->get($value.'etlnum');
//    $content[] = '＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋＋';

}

$d .= '</table>';
$content[] = $d;
/*****渠道*****/
//var_dump($content); exit;
$content = implode('<br>', $content);
echo $content; exit;
$sendMail = [
    'tony@zhugefang.com',
    'vincent@zhugefang.com',
    '635847193@qq.com',
//    'david@zhugefang.com',
];
sendReportMail($sendMail, $content, $datename.'房源统计');

/**
 * 发送邮件
 * @param array $sendMail
 * @param string $content
 * @param string $title
 */
function sendReportMail($sendMail = [], $content = '', $title = ''){
    foreach((array)$sendMail as $value){
        sendMail($value, $content, $title);
    }
}




function getSourceType($source){
    if(checkDataSource($source, 'rent')){ //整租
        return '<b style="color: #FEC42F">整租</b>';
    }elseif(checkDataSource($source, 'hezu')) { //合租
        return '<b style="color: #FC6230">合租</b>';
    }else{ //二手房
        return '<b style="color: #44CCEA">二手房</b>';
    }
}

function checkDataSource($source = '', $type = ''){
    if(!empty($source) && !empty($type)){
        return isExistsStr(strtolower($source), strtolower($type));
    }
    return false;
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

