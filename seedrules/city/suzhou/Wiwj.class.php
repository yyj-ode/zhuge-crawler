<?php namespace suzhou;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/13
 * Time: 19:09
 */

/**
 * @description 苏州我爱我家二手房
 * @classname 苏州我爱我家
 *
 */

header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","4000M");
ini_set('max_execution_time', '0');
class Wiwj extends \city\PublicClass{

    private   $opts = array(
        'http'=>array(
            'method'=>"GET",
            'header'=>"User-Agent: Mozilla/5.0\n"
        )
    );

    public $PRE_URL = 'http://sz.5i5j.com/exchange/';
    private $current_url = '';

    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        $dis = array(
            'gongyeyuan' => '园区',
            'wuzhong' => '吴中',
            'xin' => '新区',
            'gusu' => '姑苏',
            'wujiang' => '吴江',
            'xiangcheng' => '相城',
        );
        $urlarr = [];
        foreach ($dis as $k1=> $v1) {
            sleep(mt_rand(2, 5));
            $this->current_url = $this->PRE_URL . $k1 . '/';
            $html = $this->getUrlContent($this->current_url);
            $url = \QL\QueryList::Query($html,[
                'link' => ['.font-houseNum','text', '', function($total){
                    $maxPage = ceil($total/30);
                    $link = [];
                    for($Page = 1; $Page <= $maxPage; $Page++){
                        $link[] = $this->current_url.'n'.$Page;

                    }
                    return $link;
                }],
            ])->getData(function($item){
                return $item['link'];
            });
            $urlarr = array_merge($urlarr,$url[0]);
        }
        return $urlarr;
    }

    public function house_list($url)
    {
        sleep(mt_rand(2, 5));
        $html = $this->getUrlContent($url);
        $body = explode('<ul class="list-body">', $html);
        preg_match("/[\x{0000}-\x{ffff}]*?<\/section>/u", $body[1], $list);
        preg_match_all("/<li\s*class=\"publish\">[\x{0000}-\x{ffff}]*?<\/li>/u", $list[0], $tags);
        var_dump($tags);die();
        preg_match_all("/<a\s*href=\"(\/exchange[\/\w]+)\"\s*target=\"_blank\">\s*<div/u", $list[0], $hrefs);
        $house_info = array();
        foreach($hrefs[1] as $k=>$v){
            $house_info[$k] = 'http://tj.5i5j.com'.$v;
        }
        $house_info = array_merge($house_info);
        return $house_info;
    }

    public function house_detail($source_url)
    {
        // TODO: Implement house_detail() method.
    }
}