<?php namespace tianjin;
/**
 * @description 抓取规则
 * @classname 
 */
class JinFang extends \city\PublicClass{
    Public function house_page() {
        //循环列表页
        $link = [];
        for($i = 0; $i <= 4990; $i+=10){
            $link[] = 'http://www.jfzh.com.cn/jf/secondList?feature=&from='.$i.'&rows=10';
        }
        return $link;
    }

    /*
     * 获取列表页
     */
    Public function house_list($url){
        $html = getHtml($url);
        var_dump($html);die;
        $rules = array('href' => array('h3 a','href'));
        $data = \QL\QueryList::Query($html,$rules)->data;
        var_dump($data);die;
        //循环列表页
        $link = [];
        foreach ($data as $key => $value) {
            $link[] = 'http://www.jfzh.com.cn'.$value['text'];
        }
        return $link;
    }
    /*
     * 获取详情
     */

    public function house_detail($source_url){
        $html = getHtml($source_url);
        $house_info = [];
        \QL\QueryList::Query($html,[
            'house_title' => ['.bd div:eq(0) dl dt a', 'text', '', function($total)use(&$house_info){
                preg_match('/([^\-]+)\-([^\-]+)\-([^\-]+)\-/', $total,$data);
                $house_info['house_title'] = $data[0];
                $house_info['cityarea_id'] = $data[1];
                $house_info['cityarea2_id'] = $data[2];
                // $house_info['borough_name'] = $data[3];
            }],

            'house_price' => ['.shoujia > ul > li:eq(1) > span', 'text', '', function($data)use(&$house_info){
                $house_info['house_price'] = $data;
            }],

            'house_unitprice' => ['.shoujia > ol > li:eq(0)', 'text', '', function($data)use(&$house_info){
                preg_match('/\d+\.\d+/', $data,$total);
                $house_info['house_unitprice'] = $total[0];
            }],

            'house_room' => ['div[class="jiashou clearfix"] > ol > li:eq(0) b:eq(0)', 'text', '', function($data)use(&$house_info){
                $house_info['house_room'] = $data;
            }],

            'house_hall' => ['div[class="jiashou clearfix"] > ol > li:eq(0) b:eq(1)', 'text', '', function($data)use(&$house_info){
                $house_info['house_hall'] = $data;
            }],

            'house_toward' => ['div[class="jiashou clearfix"] > ol > li:eq(1)', 'text', '', function($data)use(&$house_info){
                preg_match('/：(\S+)/', $data,$total);
                $house_info['house_toward'] = $total[1];
            }],

            'house_floor' => ['div[class="jiashou clearfix"] > ol > li:eq(2)', 'text', '', function($data)use(&$house_info){
                preg_match('/(\d+)\/?(\d+)?/', $data,$total);
                $house_info['house_floor'] = $total[1];
                $house_info['house_topfloor'] = $total[2];
            }],

            'borough_name' => ['div[class="jiashou clearfix"] > ol > li:eq(3) > a', 'text', '', function($data)use(&$house_info){
                $house_info['borough_name'] = $data;
            }],

        ])->getData();
        // 
        $rules = array('src' => array('.selectTago > a > img','src'));
        $data = \QL\QueryList::Query($html,$rules)->data;
        foreach ($data as $k => $v) {
            $imgstr .= $v['src'].'|';
        }
        $house_info['house_pic_unit'] = $imgstr;
        return $house_info;
    }
    //统计官网数据
    public function house_count(){}
    public function is_off($url,$html=''){}
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100){
        return $this->house_page();
    } 
}
