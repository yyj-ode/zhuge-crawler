<?php namespace nanjing;
/**
 * @description 南京Q房整租房
 * @classname 南京Q房
 */

Class QfangRent extends \city\PublicClass{
	
    /*
     * 抓取
     */
    public function house_page(){
        $pre_url = 'http://nanjing.qfang.com/rent/h1';
        $html = file_get_contents($pre_url);
        preg_match('/<span\s*class=\"dib\">(\d+)<\/span>/',$html,$total);
//         var_dump($total);die;
        $maxPage = ceil(trimall($total[1])/30);
        $url = [];
       // $maxPage = 1;
        for ($page = 1 ; $page <= $maxPage ; $page++){
            $url[] = $pre_url.'/f'.$page;
        }
        return $url;
    }
    
	/*
	 * 获取列表页
	 */
	public function house_list($url){
	    $house_info = \QL\QueryList::run('Request', [
			'target' => $url,
		])->setQuery([
			'link' => ['#cycleListings > ul:nth-child(1) > li > div:nth-child(1) > div:nth-child(2) > div:nth-child(1) > h3:nth-child(1) > a:nth-child(1)', 'href', '', function($content){
				return 'http://nanjing.qfang.com'.$content;
			}],
		])->getData(function($item){
			return $item['link'];
		});
		
		return $house_info;
	}
	
	/*
	 * 获取详情
	 */
	public function house_detail($url){
		$house_info = \QL\QueryList::run('Request',[
			'target' => $url,
		])->setQuery([
			'house_title' => ['.text_of', 'text', '', function($title){
				return str_replace(array("\t", "\n", "\r", " "),"", $title);
			}],
			'house_price' => ['.total-price > b:nth-child(1)', 'text', '', function($price){
				return $price;
			}],

			'house_totalarea' => ['.average-price', 'text', '', function($house_totalarea){
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

			'house_toilet' => ['.header-field-list > li:nth-child(3) > span:nth-child(2) > b:nth-child(1)', 'text', '', function($house_toilet){
				preg_match("/(\d+)卫/", $house_toilet, $toilet);
                return trimall($toilet[1]);
			}],

			'house_toward' => ['.header-field-list > li:nth-child(6) > span:nth-child(2)', 'text', ''],

			'house_floor' => ['.header-field-list > li:nth-child(4) > span:nth-child(2)', 'text', '', function($house_floor){
				$house_floor = explode('/', $house_floor);
				return trimall(str_replace('层', '', $house_floor[0]));
			}],

			'house_topfloor' => ['.header-field-list > li:nth-child(4) > span:nth-child(2)', 'text', '', function($house_topfloor){
				$house_topfloor = explode('/', $house_topfloor);
				return str_replace('层', '', $house_topfloor[1]);
			}],

			'owner_name' => ['.broker-basic-name > span:nth-child(1)', 'text', ],

			'owner_phone' => ['.tel-num > span:nth-child(2)', 'text', '',function($owner_phone){
                return trimall($owner_phone);
            } ],

			'house_pic_unit' => ['#hsPics > ul:nth-child(1) > li > a:nth-child(1) > img:nth-child(1)','src','',function ($item){
			    return $item;
			}],

			'house_pic_layout' => [],

            'house_fitment' => ['.header-field-list > li:nth-child(5) > span:nth-child(2)','text','',function($house_fitment){
                preg_match("/(毛坯|普通装修|精装修|豪华装修)/u", $house_fitment, $fitment);
                return $fitment[1];
            }],

            'borough_name' => ['.field-garden-name > a:nth-child(1)', 'text', ''],

			'house_desc' => ['#hsEvaluation', 'text', '',function($item){
                return trimall($item);
            }],

			'house_number' => ['body > section.home_content.clearfix > div.hc_right.clearfix > p', 'text', '-a -br', function($house_number){
//				preg_match("/<input\s*id=\"HouseID\"\s*type=\"hidden\"\s*value=\"(\w+?)\"/u", $house_number, $number);
				$house_number = explode('：', $house_number);
				preg_match("/\w+/", $house_number[1], $number);
				return $number[0];
			}],

			'house_type' => [],

            'house_built_year' => ['.field-garden-name > em:nth-child(2)','text','',function($house_built_year){
                return str_replace('年建','',$house_built_year);
            }],

			'house_relet' => [],

			'house_style' => [],
		])->getData(function($data) use($url){

			return $data;
		});
		foreach((array)$house_info as $key => $value){
			if(isset($house_info[$key]['house_pic_unit'])){
				$house_pic_unit[] = trimall($house_info[$key]['house_pic_unit']);
			}
		}
		$house_info[0]['house_pic_unit'] = implode('|', array_unique($house_pic_unit));
        $house_info = $house_info[0];
        preg_match('/guide\-alink\-inner([\x{0000}-\x{ffff}]*?)<\/div>/u',file_get_contents($url),$ci);
        preg_match_all('/<i([\x{0000}-\x{ffff}]*?)<\/a>/u',$ci[1],$cii);
        
        $house_info['cityarea_id'] = str_replace('租房','',trimall(strip_tags($cii[0][1])));
        $house_info['cityarea2_id'] = str_replace('租房','',trimall(strip_tags($cii[0][2])));
        $house_info['company_name'] = 'Qfang';
        //下架检测
//        $house_info['off_type'] = $this->is_off($url);
		return $house_info;
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