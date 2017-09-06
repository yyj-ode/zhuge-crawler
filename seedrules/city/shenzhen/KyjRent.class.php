<?php namespace shenzhen;

/**
 * @description 深圳快有家整租房源抓取规则
 * @classname 深圳快有家整租(k-ok)
 */

Class KyjRent extends \city\PublicClass{
    
    Public function house_page(){
        /*
         * @author kevin
         * 快有家搜索条件的方式获取页面列表
         */
        //从首页获取最大列表页
        $url = "http://sz.kuaiyoujia.com/zufangs/house/quyu";
        $maxPage = $this->getMaxPage($url);
        $urlarr = [];
        for($page = 1; $page <= $maxPage; $page ++) {
            $urlarr[] = "http://sz.kuaiyoujia.com/zufangs/house/quyu-i".$page;
        }
        return $urlarr;
    }
	
    /*
     * 列表页
    */
    public function house_list($url){
        $house_info = \QL\QueryList::run('Request',[
        'target'=>$url
        ])->setQuery([
            'link'=>['div.liebiao: > ul:nth-child(1) > a:nth-child(1)','href','',function($item){
                return "http://sz.kuaiyoujia.com".$item;
            }]
        ])->getData(function($item){
            return $item['link'];
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
            'house_title' => ['.text_hide', 'text', '', function($title){
                return str_replace(array("\t", "\n", "\r", " "),"", $title);
            }],
            'house_price' => ['.sp1 > em:nth-child(1)', 'text', '', function($item){
                preg_match('/(\d+)/',$item,$price);
                return $price[1];
            }],

            'house_totalarea' => ['li.w2:nth-child(5)', 'text', '', function($item){
                preg_match('/(\d+)/',$item,$total);
                return $total[1];
            }],

            'house_room' => ['li.w2:nth-child(4)', 'text', '', function($item){
                preg_match("/(\d+)室/", $item, $room);
                return $room[1];
            }],

            'house_hall' => ['li.w2:nth-child(4)', 'text', '', function($item){
                preg_match("/(\d+)厅/", $item, $house_hall);
                return $house_hall[1];
            }],

            'house_kitchen' => ['li.w2:nth-child(4)', 'text', '', function($item){
                preg_match("/(\d+)卫/", $item, $kit);
                return $kit[1];
            }],

            'house_toward' => [],

            'house_floor' => ['li.w1:nth-child(3)', 'text', '', function($item){
                preg_match("/第(\d+)/", $item, $floor);
                return $floor[1];
            }],

            'house_topfloor' => ['li.w1:nth-child(3)', 'text', '', function($item){
                preg_match("/共(\d+)/", $item, $floor);
                return $floor[1];
            }],

            'owner_name' => [],

            'owner_phone' => [],

            'house_pic_unit' => ['.pt1_l_img > img:nth-child(1)', 'src', '', function($house_pic_unit){
                return $house_pic_unit;
            }],

            'house_pic_layout' => [],

            'house_fitment' => ['.pt1_l_img > img:nth-child(1)','text','',function($item){
                return explode("：",$item)[1];
            }],

            'borough_name' => ['.dd3 > ul:nth-child(1) > li:nth-child(1)', 'text', '',function($item){
            return explode("：",explode("(",trimall($item))[0])[1];
        }],

            'house_desc' => ['.fyxq > dd:nth-child(2) > p:nth-child(1)', 'text', '',function($item){
                return trimall($item);
            }],

            'house_number' => ['p.clearfix > span:nth-child(1)', 'text', '', function($house_number){
                preg_match("/(\w+)/", $house_number, $number);
                return $number[1];
            }],

            'house_type' => [],

            'house_built_year' => [],

            'house_relet' => [],

            'house_style' => [],
            'cityarea_id'=>['.dd3 > ul:nth-child(1) > li:nth-child(1)', 'text', '',function($item){
                return explode("(",explode("-",trimall($item))[0])[1];
            }],
            'cityarea2_id'=>['.dd3 > ul:nth-child(1) > li:nth-child(1)', 'text', '',function($item){
                return explode("-",explode(")",trimall($item))[0])[1];
            }]
        ])->getData(function($data) use($source_url){
            $data['company_name'] = '快有家';
            //下架检测
//            $data['off_type'] = $this->is_off($source_url);
            return $data;
        });
        foreach((array)$house_info as $key => $value){
            if(isset($house_info[$key]['house_pic_unit'])){
                $house_pic_unit[] = $house_info[$key]['house_pic_unit'];
            }
        }
        $house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
        $house_info = $house_info[0];

    	return $house_info;
    }
    protected function getMaxPage($url){
        $maxPage = \QL\QueryList::run('Request',[
            'target'=>$url
        ])->setQuery([
            'num'=>['.ay_sumfangyuan > i:nth-child(1)','text','',function($item){
                return ceil($item/10);
            }]
        ])->getData(function($item){
            return $item['num'];
        });
        return !empty($maxPage[0]) ? $maxPage[0] : 1;
    }

    //检测该房源是否下架
    public function is_off($url){
        return 2;//TODO 数据库无下架房源
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            if(preg_match("/<div\s*class=\"name\-1\sname\">\s*<\/div>/", $html)){
                return 1;
            }elseif(preg_match("/house\-shelves/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}
