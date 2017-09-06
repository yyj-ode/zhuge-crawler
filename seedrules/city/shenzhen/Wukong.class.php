<?php namespace shenzhen;
/**
 * @description 深圳悟空找房二手房抓取规则
 * @classname 深圳 =======《悟空找房》=======深圳
 */

class Wukong extends \baserule\Wukong
{
    public $city = 'shenzhen';
    public function getHtmlQueryData($url){
        $queryData = [];
        sleep(1);
        $html = getHtml($url);
        //$html = $this -> getUrlContent($url);

        \QL\QueryList::Query($html, [
            'totalnum' => ['.searchFilterbox > .filterfr > span', 'text', '', function($data)use(&$queryData){
                preg_match('/(\d+)/', $data, $match);
                $queryData['totalnum'] = $match[1];
            }],

            'dis' => ['div.searchFilter > dl:eq(0) > dd:eq(0) > a', 'href', '', function($data)use(&$queryData){
                preg_match('/esf\/(.*)/', $data, $match);
                if($match[1]) $queryData['dis'][] = $match[1];
            }],

            'plate' => ['div.searchFilter > dl:eq(0) > dd:eq(1) > a', 'href', '', function($data)use(&$queryData){
                preg_match('/esf\/.+-(.*)/', $data, $match);
                if($match[1]) $queryData['plate'][] = $match[1];
            }],

            // 列表
            'list' => ['li.clearfix:nth-child > a:nth-child(1)', 'href','', function($data)use(&$queryData){
                $queryData['list'][] = $this -> host . $data;
            }],

        ])->getData();

//        var_dump($queryData);die;
        return $queryData;
    }


}
