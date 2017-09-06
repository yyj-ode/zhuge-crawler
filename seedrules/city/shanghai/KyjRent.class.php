<?php namespace shanghai;

/**
 * @description 上海快有家整租房源抓取规则
 * @classname 上海快有家整租
 */

Class KyjRent extends \city\PublicClass{
    
    Public function house_page(){
        /*
         * @author kevin
         * 快有家搜索条件的方式获取页面列表
         */
        //从首页获取最大列表页
        $url = "http://sh.kuaiyoujia.com/zufangs/house/quyu";
        $html = file_get_contents ( $url );
        preg_match("/<em\sclass=\"colff6600\">[\x{0000}-\x{ffff}]*?<\/em>/u", $html, $pages);
        $pages = strip_tags($pages[0]);
        $url = [];
        for($page = 1; $page <= $pages; $page ++) {
            $url[] = "http://sh.kuaiyoujia.com/zufangs/house/quyu-i".$page;
        }
        return $url;
    }
	
    /*
     * 列表页
    */
    public function house_list($url){
//        $url = 'http://shanghai.kuaiyoujia.com/zufang/9085778.html';
        $house_info = \QL\QueryList::run("Request",[
                "target"=>$url,
            ])->setQuery([
                'link'=>["div.liebiao > ul:nth-child(1) > a:nth-child(1)",'href','',function($href){
                    $url =  "http://sh.kuaiyoujia.com".$href;
                    return $url;
                }],
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
            'house_price' => ['.sp1 > em:nth-child(1)', 'text', '', function($price){
                preg_match('/(\d+)/',$price,$pr);
                return $pr[1];
            }],

            'house_totalarea' => ['li.w2:nth-child(5)', 'text', '', function($house_totalarea){
                preg_match('/(\d+)/',$house_totalarea,$tol);
                return $tol[1];
            }],

            'house_room' => ['li.w2:nth-child(4)', 'text', '-span', function($house_room){
                preg_match("/(\d+)室/", $house_room, $hr);
                return $hr[1];
            }],

            'house_hall' => ['li.w2:nth-child(4)', 'text', '', function($house_hall){
                preg_match("/(\d+)厅/", $house_hall, $hh);
                return $hh[1];
            }],

            'house_kitchen' => ['li.w2:nth-child(4)', 'text', '', function($house_kitchen){
                preg_match("/(\d+)厨/", $house_kitchen, $hk);
                return $hk[1];
            }],

            'house_toilet' => ['li.w2:nth-child(4)', 'text', '', function($house_toilet){
                preg_match("/(\d+)卫/", $house_toilet, $ht);
                return $ht[1];
            }],

            'house_toward' => [''],

            'house_floor' => ['li.w1:nth-child(3)', 'text', '', function($house_floor){
                preg_match('/第(\d+)\//',$house_floor,$hf);
                return $hf[1];
            }],

            'house_topfloor' => ['li.w1:nth-child(3)', 'text', '', function($house_topfloor){
                preg_match('/共(\d+)/',$house_topfloor,$htf);
                return $htf[1];
            }],

            'owner_name' => [''],

            'owner_phone' => [],

            'house_pic_unit' => ['.pt1_l_img > img:nth-child(1)', 'src', ''],

            'house_pic_layout' => [],

            'house_fitment' => ['li.w1:nth-child(6)','text','',function($item){
                preg_match('/(毛坯|简装修|精装修|豪华装修)/',$item,$fitment);
                return $fitment[1];
            }],

            'borough_name' => ['.dd3 > ul:nth-child(1) > li:nth-child(1)', 'text', '',function($item){
                return explode("：",explode("(",trimall($item))[0])[1];
            }],

            'house_desc' => ['.fyxq > dd:nth-child(2) > p:nth-child(1)','text',''],

            'house_number' => ['p.clearfix > span:nth-child(1)', 'text', '', function($item){
                preg_match('/(\d+)/',$item,$id);
                return trimall($id[1]);
            }],

            'house_type' => [],

            'house_built_year' => [''],

            'house_relet' => [],

            'house_style' => [],
            'cityarea_id'=>['.dd3 > ul:nth-child(1) > li:nth-child(1)','text','',function($item){
                return explode("-",explode("(",trimall($item))[1])[0];
            }],
            'cityarea2_id'=>['.dd3 > ul:nth-child(1) > li:nth-child(1)','text','',function($item){
                return explode(")",explode("-",trimall($item))[1])[0];
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
        return $house_info[0];
    }
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            preg_match("/已租出/",$html,$is_off);
            if ($is_off){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }

    }
}
