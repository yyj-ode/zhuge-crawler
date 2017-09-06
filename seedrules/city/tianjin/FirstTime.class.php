<?php namespace tianjin;
/**
 * @description 抓取规则
 * @classname  
 * 具体模版参考哈尔滨Firsttime.class.php
 */
class Firsttime extends \city\PublicClass{
    Public function house_page() {
        $urlarr = [];
        $dis = [18,17,16,8,12,11,16,12,11,16,9,11,7,6];
        $price = ['_0-60_','_60-80_','_80-100_','_100-150_','_150-200_','_200-300_','_300-500_','_500-1000_','_1000-0_'];
        $sum = 350;
        $html = 'http://tj.01fy.cn/sale/list_2_53';
        for ($i=0; $i <=13 ; $i++) { 
            $str = $html.($i+36);
            for ($j=1; $j <= $dis[$i]; $j++) { 
                for ($k=0; $k <= 8; $k++) { 
                    for ($l=1; $l <= 7; $l++) { 
                        $urlarr[] = $str.'_5'.$sum.$price[$k].'0_0-0_0_2_0_'.$l.'_.html';
                    }
                }
                $sum++;
            }
        }
        return $urlarr;
    }

    /*
     * 获取列表页
     */
    Public function house_list($url){
        //遍历详情页href
        $rules = array('text' => array('#list ul li .div01 a','href'));
        $data = \QL\QueryList::Query($url,$rules)->data;
        //循环列表页
        $link = [];
        foreach ($data as $key => $value) {
            $link[] = 'http://tj.01fy.cn/sale/'.$value['text'];
        }
        return $link;
    }
    /*
     * 获取详情
     */

    public function house_detail($source_url){
        $html = file_get_contents($source_url);
        $rules = array('text' => array('#content > h1','text'));
        $house_info['house_title'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $rules = array(
            'text' => array('.l_fy p','text','', function($total){
                    preg_match('/\d+/',$total,$id);
                    return $id[0];
                }),
        );
        $house_info['house_number'] = \QL\QueryList::Query($source_url,$rules)->data[0]['text'];
        preg_match('/人：<\/dt>[^\/]+/',$html,$broker);
        preg_match('/<dd>([^<]+)/',$broker[0],$info);
        $house_info['owner_name'] = $info[1];

        $rules = array('text' => array('.blues b','text'));
        $house_info['house_price'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $rules = array('text' => array('.cr_left dd:frist','text','',function($total){
            preg_match('/\((\d+)/',$total,$str);
            return $str[1];
        }));
        $house_info['house_unitprice'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];

        $rules = array('text' => array('.des','text','-strong',function($data){
            $info = preg_replace('/\s+/','',$data);
            return $info;
        }));
        $house_info['house_desc'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];

        // $rules = array('text' => array('.house-title','text'));
        // $house_info['house_title'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $rules = array('text' => array('.position a:eq(2)','text','',function($total){
            $str = str_replace('二手房','',$total);
            return $str;
        }));
        $house_info['cityarea_id'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        if ($house_info['cityarea_id']=='个人房源') {
            $house_info['cityarea_id'] = '';
        }
        
        $rules = array('text' => array('.position a:eq(3)','text','',function($total){
            $str = str_replace('二手房','',$total);
            return $str;
        }));
        $house_info['cityarea2_id'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        if ($house_info['cityarea2_id']=='个人房源') {
            $house_info['cityarea2_id'] = '';
        }
        
        $rules = array('text' => array('.cr_left dl:eq(5) dd','text','',function($total){
            preg_match('/(\S+)\/\D+(\d+)/',$total,$info);
            return $info;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_floor'] = $info[1];
        $house_info['house_topfloor'] = $info[2];
        $rules = array('text' => array('#content .cr_left dl:eq(1) dd','text','',function($total){
            preg_match('/(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+/',$total,$data);
            return $data;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_room'] = $info[1];
        $house_info['house_hall'] = $info[2];
        $house_info['house_toilet'] = $info[3];
        $house_info['house_totalarea'] = $info[4];
        if (empty($house_info['house_totalarea'])) {
            $rules = array('text' => array('#content .cr_left dl:eq(1) dd','text','',function($total){
                preg_match('/\d+/',$total,$data);
                return $data[0];
            }));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['house_totalarea'] = $info;
        }
        $rules = array('text' => array('.telephone','text','',function($data){
            $data = preg_replace('/\s+/','',$data);
            return $data;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['owner_phone'] = $info;

        $rules = array('text' => array('.l_fy p','text','',function($data){
            $data = preg_replace('/[^@]+：/','', $data);
            return $data;
        }));

        $house_info['created'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];

        $house_info['created'] = time();
        $house_info['updated'] = time();
        $house_info['source_name'] = '第一时间';
        $house_info['source_url'] = $source_url;
        $rules = array('text' => array('#content .cr_left dl:eq(4) dd','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_type'] = $info;
        if (preg_match('/\-/',$info)) {
            preg_match('/([^(]+)[^-]+\-\s+(\S+)/',$info,$toty);
            $house_info['house_toward'] = $toty[1];
            $house_info['house_type'] = $toty[2];
        }
        $rules = array('text' => array('#content .cr_left dl:eq(2) dd','text','',function($total){
                preg_match('/\S+/',$total,$info);
                return $info[0];
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['borough_name'] = $info;

        $rules = array('src' => array('.pic-list img','data-original'));
        $img1 = \QL\QueryList::Query($source_url,$rules)->data;
        $img = [];
        foreach ($img1 as $key => $value) {
            $img[] = $value['src'];
        }
        $imgstr = implode('|',$img);
        $house_info['house_pic_unit'] = $imgstr;
        return $house_info;
    }
	//统计官网数据
	public function house_count(){}
    //下架判断
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getSnoopy($url);
            }
            //抓取下架标识
            $off_type = 1;
            preg_match('/已下架/', $html,$Tag);
            if(empty($Tag)){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100){
        return $this->house_page();
    } 
}
