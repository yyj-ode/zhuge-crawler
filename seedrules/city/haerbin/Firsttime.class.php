<?php namespace haerbin;
/**
 * @description 抓取规则
 * @classname 
 */
class Firsttime extends \city\PublicClass{
    Public function house_page() {
        $urlarr = [];
        //个人房源
        $person = 2;
        //哈尔滨城区下的商圈个数
        $dis = [33,27,21,30,9,8,8,0,0,0,0,0,0];
        //价格范围
        $price = ['0-60','60-80','80-100','100-150','150-200','200-300','300-500','500-1000','1000-0'];
        //价格范围
        $area = ['0-50','50-70','70-90','90-110','110-130','130-150','150-200','200-300','300-500','500-0'];
        $url = 'http://haerbin.01fy.cn/sale/list_{$person}_{$cityarea_id}_{$cityarea2_id}_{$price}_{$room}_{$area}_0_2_0_{$page}_.html';
        $url = str_replace('{$person}', $person, $url);

        //遍历城区
        $cityarea_id = 9604;
        //商圈递增编号
        $cityarea2_id = 9617;
        for ($i=0; $i <=12 ; $i++) {
            $url1 = str_replace('{$cityarea_id}', $cityarea_id, $url);
            //判断城区下是否有商圈
            $cityarea_id++;
            if ($dis[$i]!=0) {
                //遍历商圈
                for ($j=1; $j <= $dis[$i]; $j++) { 
                    //遍历价格范围
                    for ($p=0; $p <= 8; $p++) {
                        //遍历面积范围
                        for ($a=0; $a <= 9; $a++) { 
                            //遍历居室
                            for ($room=1; $room <= 6; $room++) { 
                                //遍历页数(有空的) 
                                for ($page=1; $page <= 7; $page++) { 
                                    $url2 = str_replace('{$cityarea2_id}', $cityarea2_id, $url1);
                                    $url2 = str_replace('{$price}', $price[$p], $url2);
                                    $url2 = str_replace('{$room}', $room, $url2);
                                    $url2 = str_replace('{$area}', $area[$a], $url2);
                                    $url2 = str_replace('{$page}', $page, $url2);
                                    $urlarr[] = $url2;
                                }
                            }
                        }
                    }
                    $cityarea2_id++;
                }
            }else{
                $cityarea2_id = 0;
                //遍历价格范围
                for ($p=0; $p <= 8; $p++) {
                    //遍历面积范围
                    for ($a=0; $a <= 9; $a++) { 
                        //遍历居室
                        for ($room=1; $room <= 6; $room++) { 
                            //遍历页数
                            for ($page=1; $page <= 7; $page++) { 
                                $url2 = str_replace('{$cityarea2_id}', $cityarea2_id, $url1);
                                $url2 = str_replace('{$price}', $price[$p], $url2);
                                $url2 = str_replace('{$room}', $room, $url2);
                                $url2 = str_replace('{$area}', $area[$a], $url2);
                                $url2 = str_replace('{$page}', $page, $url2);
                                $urlarr[] = $url2;
                            }
                        }
                    }
                }
            }
        }



        // //个人房源
        // $html = 'http://haerbin.01fy.cn/sale/list_2_9';
        // //遍历城区
        // for ($i=0; $i <=12 ; $i++) {
        //     $str = $html.($i+604);
        //     //判断城区下是否有商圈
        //     if ($dis[$i]!=0) {
        //         //商圈递增编号
        //         $sum = 617;
        //         //遍历商圈
        //         for ($j=1; $j <= $dis[$i]; $j++) { 
        //             //遍历价格范围
        //             for ($k=0; $k <= 8; $k++) {
        //                 //遍历页数(有空的) 
        //                 for ($l=1; $l <= 7; $l++) { 
        //                     $urlarr[] = $str.'_9'.$sum.$price[$k].'0_0-0_0_2_0_'.$l.'_.html';
        //                 }
        //             }
        //             $sum++;
        //         }
        //     }else{
        //         //遍历价格范围
        //         for ($k=0; $k <= 8; $k++) { 
        //             //页数
        //             for ($l=1; $l <= 7; $l++) { 
        //                 $urlarr[] = $str.'_0'.$price[$k].'0_0-0_0_2_0_'.$l.'_.html';
                        
        //             }
        //         }
        //     }
        // }
        return $urlarr;
    }

    /*
     * 获取列表页
     */
    Public function house_list($url){
        //遍历详情页url
        $rules = array('text' => array('#list ul li .div01 a','href'));
        $data = \QL\QueryList::Query($url,$rules)->data;
        //循环列表页
        $link = [];
        foreach ($data as $key => $value) {
            $link[] = 'http://haerbin.01fy.cn/sale/'.$value['text'];
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
        $house_info['owner_name'] = rtrim($info[1],'（个人）');
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
        // if ($house_info['cityarea_id']=='哈尔滨周边') {
        //     $house_info['cityarea2_id'] = '';
        // }
        $rules = array('text' => array('.cr_left dl:eq(5) dd','text','',function($total){
            preg_match('/(\S+)\/\D+(\d+)/',$total,$info);
            return $info;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_floor'] = preg_match('/\d+/', $info[1],$floor)?$floor[0]:$info[1];
        $house_info['house_topfloor'] = $info[2];
        $rules = array('text' => array('#content .cr_left dl:eq(1) dd','text','',function($total){
            preg_match('/(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+/',$total,$data);
            return $data;
        }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_room'] = $info[1];
        $house_info['house_hall'] = $info[2];
        $house_info['house_toilet'] = $info[3];
        if(empty($house_info['house_room'])||empty($house_info['house_hall'])){
            preg_match('/<meta\s*name=\"description\"\s*content=\"([\x{0000}-\x{ffff}]+?)\"/u',$html,$match);
            preg_match('/(\d+)室(\d+)厅/',$match[1],$match);
            $house_info['house_room']=$match[1];
            $house_info['house_hall'] = $match[2];
        }
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
        if(empty($house_info['owner_phone'])){
            preg_match('/<meta\s*name=\"keywords\"\s*content=\"([\x{0000}-\x{ffff}]+?)\"/u',$html,$match);
            $result=trimall($match[1]);
            $telephone=preg_match('/\d+/',$result,$match);
            $house_info['owner_phone']=$match[0];
        }
        $rules = array('text' => array('.l_fy p','text','',function($data){
            $data = preg_replace('/[^@]+：/','', $data);
            return $data;
        }));
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
