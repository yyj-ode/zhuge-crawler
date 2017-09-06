<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 16-4-25
 * Time: 下午11:26
 */
include_once "../common/common.php";
//$url = getUrl();
//echo $url['source_url'];
for($i = 0; $i < 1; $i++) {
    $url = 'http://bj.lianjia.com/ershoufang/BJXC91989222.html';
    $content = checkContent('beijing/Lianjia', $url);
    var_dump($content);
}
exit;
saveContent($content, 'beijing/Qfang');