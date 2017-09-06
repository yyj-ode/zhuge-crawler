<?php namespace guangzhou;
/**
 * @description 广州Qfang合租抓取规则
 * @classname 广州Qfang合租(k-ok)
 */


Class QfangHezu extends \city\PublicClass  {
    /*
     * 抓取
     */
    Public function house_page(){
        $maxPage=1423;
        $urlarr = [];
        $url_pre="http://guangzhou.qfang.com/rent/h2-f";
        for ($page=1; $page<=$maxPage; $page++){
            $urlarr[] = $url_pre.$page;
        }
        return $urlarr;
    }
    /*
     * 列表页
    */
    public function house_list($url){
        $house_info = \QL\QueryList::run('Request',[
            'target' => $url,
        ])->setQuery([
            'link' => ['#cycleListings ul li div:nth-child(1) > div:nth-child(2) > div:nth-child(1) > h3:nth-child(1) > a', 'href', '', function($href){
                return "http://guangzhou.qfang.com".$href;
            }]
        ])->getData(function($data){
            return $data['link'];
        });
    	return $house_info;
    }
    /*
     *获取详情页数据
    */
    public function house_detail($source_url) {
        $house_info = \QL\QueryList::run('Request',[
            'target' => $source_url,
        ])->setQuery([
            'house_title' => ['.text_of', 'text', '', function($title){
                return str_replace(array("\t", "\n", "\r", " "),"", $title);
            }],
            'house_price' => ['.total-price > b:nth-child(1)', 'text', ''],

            'house_room_totalarea' => ['.average-price', 'text', '', function($house_totalarea){
                return str_replace('㎡', '', $house_totalarea);
            }],

            'house_room' => ['.header-field-list > li:nth-child(3) > span:nth-child(2) > b:nth-child(1)', 'text', '', function($house_room){
                preg_match("/(\d+)房/", $house_room, $room);
                return trimall($room[1]);
            }],

            'house_hall' => ['.header-field-list > li:nth-child(3) > span:nth-child(2) > b:nth-child(1)', 'text', '', function($house_hall){
                preg_match("/(\d+)厅/", $house_hall, $hall);
                return trimall($hall[1]);
            }],

            'house_kitchen' => ['.header-field-list > li:nth-child(3) > span:nth-child(2) > b:nth-child(1)', 'text', '-i', function($house_toward){
                preg_match("/(\d+)卫/", $house_toward,$toward);
                return trimall($toward[1]);
            }],

            'house_toward' => ['.header-field-list > li:nth-child(6) > span:nth-child(2)', 'text', ''],

            'house_floor' => ['.header-field-list > li:nth-child(4) > span:nth-child(2)', 'text', '', function($floor){
                preg_match('/(低|中|高)/u',$floor,$house_floor);
                return $house_floor[1];
            }],

            'house_topfloor' => ['.header-field-list > li:nth-child(4) > span:nth-child(2)', 'text', '-i', function($floor){
                preg_match('/(\d+)/u',$floor,$topfloor);
                return $topfloor[1];
            }],

            'owner_name' => ['.broker-basic-name > span:nth-child(1)', 'text', ],

            'owner_phone' => ['.tel-num > span:nth-child(2)', 'text', '' ,function($phone){
                return trimall($phone);
            }],

            'house_pic_unit' => ['li.seled a img', 'src', '', function($house_pic_unit){
                return $house_pic_unit;
            }],

            'house_pic_layout' => [],

            'house_fitment' => ['.header-field-list > li:nth-child(5) > span:nth-child(2)','text',''],

            'borough_name' => ['.field-garden-name > a:nth-child(1)', 'text', ''],

            'house_desc' => ['#hsEvaluation', 'text', 'strong',function($desc){
                return trimall($desc);
            }],

            'house_number' => ['.houseNum', 'text', '', function($house_number){
//				preg_match("/<input\s*id=\"HouseID\"\s*type=\"hidden\"\s*value=\"(\w+?)\"/u", $house_number, $number);
                $house_number = explode('：', $house_number);
                preg_match("/\w+/", $house_number[1], $number);
                return $number[0];
            }],

            'house_type' => [],

            'house_built_year' => ['.field-garden-name > em:nth-child(2)','text','',function($div){
                preg_match('/(\d{4})/',$div,$year);
                return $year[1];
            }],

            'cityarea_id' => ['.guide-alink-inner > a:nth-child(5)','text','',function($cityarea){
                return str_replace('租房','',$cityarea);
            }],

            'cityarea2_id' => ['.guide-alink-inner > a:nth-child(7)','text','',function($cityarea2){
                return str_replace('租房','',$cityarea2);
            }],

            'house_relet' => [],

            'house_style' => [],
        ])->getData(function($data) use($source_url){
            $data['company_name'] = 'Q房';
            //下架检测
//            $data['off_type'] = $this->is_off($source_url);
            return $data;
        });
        //房源图片
        $html = $this->getUrlContent($source_url);
        preg_match("/hsPics([\x{0000}-\x{ffff}]*?)<\/div>/u",$html,$pic_list);
        preg_match_all("/data-src\=\"([\x{0000}-\x{ffff}]*?)\"/u",$pic_list[0],$pics);
        $house_pic_unit = trimall($pics[1][0]);
        foreach($pics[1] as $k=>$p){
            if($k!=0){
                $house_pic_unit = $house_pic_unit.'|'.trimall($p);
            }
        }
        $house_info[0]['house_pic_unit'] = $house_pic_unit;
        return $house_info[0];
    
    }
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $off_type = 1;
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml','html',$url);
            if($newurl == $oldurl){
                $Tag = \QL\QueryList::Query($html,[
                    "isOff" => ['.remove_over','class',''],
                    "404" => ['.error-404','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if(empty($Tag)){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }
}