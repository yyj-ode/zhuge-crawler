<?php namespace haerbin;
/**
 * Created by PhpStorm.
 * User: zhangjg
 * Date: 2016/8/9
 * Time: 14:23
 */


class Hjt58 extends \city\PublicClass
{
    protected $log = false; // 是否开启日志
    public $city_name = 'hrb';
    public $dis = array(
        'nangang' => 59,
        'daoli' => 52,
        'daowai' => 50,
        'xiangfang' => 44,
        'hrbjiangbei' => 13,
        'hrbkaifaqu' => 8,
        'hebyilan' => 0,
        'hebfangz' => 0,
        'hebbinxian' => 0,
        'hebbayan' => 0,
        'hebmulan' => 0,
        'hebtonghe' => 0,
        'haerbin' => 8
    );
    /**
     * 获取种子分页
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_page()
    {
        $urlarr = [];
        $urlstr = 'http://' . $this->city_name . '.58.com/{$cityarea}/ershoufang/1/';
        foreach ($this->dis as $k => $v) {
            //遍历城区
            $url = str_replace('{$cityarea}', $k, $urlstr);
            $html = file_get_contents($url);
            //找出商圈url
            $rules = array('href' => array('div.subarea a','href'));
            $data = \QL\QueryList::Query($url,$rules)->data;
            //遍历商圈循环页数
            foreach ($data as $key => $value) {
                $urlstr1 = 'http://' . $this->city_name . '.58.com' . $value['href'] . 'pn';
                for ($i=1; $i <= 70; $i++) { 
                    $urlarr[] = $urlstr1 . $i . '/?key=%2525u597D%2525u5BB6%2525u5EAD%2525u623F%2525u5730%2525u4EA7&ClickID=1';
                }
            }
        }
        return $urlarr;
        // for ($i = 1; $i <= 70; $i++) {
        //     $house_info[] = 'http://' . $this->city_name . '.58.com/ershoufang/0/pn' . $i . '/?PGTID=0d30000c-0000-110b-940a-229a430108e3&ClickID=1';
        // }
        // if (!$house_info)
        //     writeLog('Five8Personal' . __FUNCTION__, ['url' => $house_info], $this->log);
        // return $house_info;
    }

    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    Public function house_list($url = '')
    {
        $rules = array(
            'text' => array('#infolist td.t > p.bthead > a','href'),
        );
        $data = \QL\QueryList::Query($url,$rules)->data;
        //循环列表页
        $link = [];
        foreach ($data as $key => $value) {
            if (strlen($value['text'])>40) {
                $link[] = $value['text'];
            }
        }
        return $link;
    }

    /**
     * 获取详情页信息
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_detail($source_url)
    {
        #$html = $this->getUrlContent($source_url_m);
        $urlphone = $source_url;
        $html = file_get_contents($source_url);
        $house_info = [];

        $rules = array('text' => array('.time','text'));
        $house_info['created'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['created'] = strtotime($house_info['created']);
        $rules = array(
            'text' => array('.bigtitle','text','',function($total){
                $data = preg_replace('/\s+/','',$total);
                return $data;
            }),
        );
        $house_info['house_title'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $rules = array('text' => array('.suUl li:eq(0) .su_con span','text'));
        $house_info['house_price'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $rules = array('text' => array('.suUl li:eq(0) .su_con','text','-span',function($total){
                preg_match('/\d+/', $total,$data);
                return $data[0];
        }));
        $house_info['house_unitprice'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $rules = array('text' => array('.suUl > li:eq(3) > .su_con','text','',function($total){
                $sign = strpos($total,'㎡');
                $total = substr($total,0,$sign);
                // var_dump($total);
                preg_match('/(\d+)\D+((\d+)\D+)?((\d+)\D+)?(\d+)/',$total,$data);
                // var_dump($data);die;
                return $data;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_room'] = $info[1];
        $house_info['house_hall'] = $info[3];
        $house_info['house_toilet'] = $info[5];
        $house_info['house_fitment'] = '';
        $house_info['house_built_year'] = '';
        $house_info['house_totalarea'] = $info[6];
        if (empty($house_info['house_room'])) {
            $rules = array('text' => array('.suUl > li:eq(2) > .su_con','text','',function($total){
                    $sign = strpos($total,'㎡');
                    $total = substr($total,0,$sign);
                    // var_dump($total);
                    preg_match('/(\d+)\D+((\d+)\D+)?((\d+)\D+)?(\d+)/',$total,$data);
                    // var_dump($data);die;
                    return $data;
            }));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['house_room'] = $info[1];
            $house_info['house_hall'] = $info[3];
            $house_info['house_toilet'] = $info[5];
            $house_info['house_fitment'] = '';
            $house_info['house_built_year'] = '';
            $house_info['house_totalarea'] = $info[6];
        }
        if (empty($house_info['house_totalarea'])) {
            $house_info['house_toilet'] = 0;
            $house_info['house_totalarea'] = $info[4];
        }

        $rules = array('text' => array('.suUl li:eq(3)','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        if (preg_match('/位置/', $info)==0) {
            $rules = array('text' => array('.suUl li:eq(4)','text'));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        }
        preg_match('/\s+(\S+)-\s+(\S+)-\s+(\S+)/', $info,$data);
        $house_info['cityarea_id'] = $data[1];
        $house_info['cityarea2_id'] = $data[2];
        $house_info['borough_name'] = $data[3];
        

        $rules = array('text' => array('.suUl .su_con > span > a[rel="nofollow"]','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['owner_name'] = $info;
        if (empty($house_info['owner_name'])) {
            $rules = array('text' => array('.jjreninfo_des_jjr span:eq(0)','text'));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['owner_name'] = $info;
        }
        $house_info['owner_name'] = str_replace('>','',$house_info['owner_name']);
        // $urlphone = $_SERVER['REQUEST_URI'];
        preg_match('/(\d{14})_0/', $urlphone,$data);
        if (empty($data)) {
            preg_match('/(\d{14})/', $urlphone,$data);
        }
        $phoneurl = 'http://m.58.com/hrb/ershoufang/'.$data[1].'x.shtml';
        $rules = array('text' => array('.contact li:eq(1)','text'));
        $info = \QL\QueryList::Query($phoneurl,$rules)->data[0]['text'];
        $house_info['owner_phone'] = $info;

        $rules = array('text' => array('.des_table li:eq(0) ul li:eq(1)','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_type'] = $info;
        $rules = array('text' => array('.des_table li:eq(0) ul li:eq(3)','text','-a'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_fitment'] = $info;

        $rules = array('text' => array('.des_table>li:eq(2)>ul>li:eq(1)','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_built_year'] = $info;
        $rules = array('text' => array('.des_table>li:eq(2)>ul>li:eq(3)','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        preg_match('/([^\(]+)\D+(\d+)/', $info,$data);
        $house_info['house_floor'] = $data[1];
        if (!(preg_match('/层|地/', $house_info['house_floor']))) {
            $house_info['house_floor'] = '';
        }
        $house_info['house_topfloor'] = $data[2];

        $rules = array('text' => array('.des_table>li:eq(3)>ul>li:eq(3)','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_toward'] = $info;

        $rules = array('text' => array('.descriptionBox .description_con','text','-.mb20',function($total){
                $data = preg_replace('/\s+/', '', $total);
                return $data;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_desc'] = $info;
        if (empty($house_info['house_desc'])) {
            $rules = array('text' => array('.descriptionBox .description_con p:eq(1) span','text'));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['house_desc'] = $info;
        }
        $rules = array('src' => array('.descriptionImg img','src'));
        $info = \QL\QueryList::Query($html,$rules)->data;
        $imgstr = '';
        foreach ($info as $key => $v) {
            $imgstr .= $v['src'].'|';
        }
        $house_info['house_pic_unit'] = $imgstr;
        preg_match('/[^\?]+/', $source_url,$data);
        $house_info['source_url'] = $data[0];
        $house_info['source_name'] = '好家庭房地产';
        return $house_info;
    }

    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function callNewData()
    {
        for ($i = 0; $i <= 30; $i++) {
            $house_info[] = 'http://' . $this->city_name . '.58.com/ershoufang/1/pn' . $i . '/?key=%2525u597D%2525u5BB6%2525u5EAD%2525u623F%2525u5730%2525u4EA7&ClickID=1';
        }
        if (!$house_info)
            writeLog('Five8Personal' . __FUNCTION__, ['url' => $house_info], $this->log);
        return $house_info;

    }

    /*
    * 抓取房源对应标签
    */
    public function getTags($html)
    {
        $tags = [];
        \QL\QueryList::Query($html, [
            'tag' => ['.infor-keyword > li', 'text']
        ])->getData(function ($item) use (&$tags) {
            $item['tag'] && $tags[] = $item['tag'];
            return $item;
        });
        return implode("#", $tags);
    }
}