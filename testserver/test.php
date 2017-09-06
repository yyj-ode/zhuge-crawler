<?php
/**
 * Created by PhpStorm.
 * User: Tony
 * Date: 16/3/18
 * Time: 下午8:13
 */


$url = 'http://123.57.76.91:8000/Etl/ETL/run?params=%27%27';



$content = (array)json_decode('{"fd":1,"data":[{"source_url":"http:\/\/bj.lianjia.com\/ershoufang\/BJXC91791261.shtml","source":1,"owner_phone":"15010205802","company":"\u94fe\u5bb6\u5b98\u7f51","house_title":"\u516d\u94fa\u7095\u4e00\u533a2\u5ba41\u5385620\u4e07","house_price":"620","house_totalarea":"51","house_room":"2","house_hall":"1","house_toward":"\u5357","house_floor":"\u9ad8","house_topfloor":"5","house_built_year":"1975","borough_name":"\u516d\u94fa\u7095\u4e00\u533a","cityarea2_id":"\u516d\u94fa\u7095","cityarea_id":"\u897f\u57ce","service_phone":"4008861382,2580","owner_name":"\u6ee1\u6587\u4eae","house_pic_unit":""}],"key":"beijing"}');




var_dump(curl_post($url, ['params' => serialize(['sdfsd' => 'fdsfdsfdsfds', 'fdsfdsafds'])]));





//参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
function curl_post($url, $post = '', $cookie = '', $returnCookie = 0){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, ' ');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://xxx");
    if($post){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if($cookie){
        foreach((array)$cookie as $key => $value){
            $cookies = $key.'='.$value.';';
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if ($returnCookie) {
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie'] = substr($matches[1][0], 1);
        $info['content'] = $body;
        return analyJson($info);
    } else {
        var_dump($data); exit;
        return analyJson($data);
    }
}

/**
 * 解析json串
 * @param type $json_str
 * @return type
 */
function analyJson($json_str) {
    $json_str = str_replace('＼＼', '', $json_str);
    $out_arr = array();
    preg_match('/{.*}/', $json_str, $out_arr);
    if (!empty($out_arr)) {
        $result = json_decode($out_arr[0], TRUE);
    } else {
        return FALSE;
    }
    return $result;
}