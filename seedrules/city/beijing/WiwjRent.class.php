<?php namespace beijing;
/**
 * @description 北京我爱我家 整租房抓取规则
 * @classname 北京我爱我家
 */

class WiwjRent extends \city\PublicClass{
	public $PRE_URL = 'http://bj.5i5j.com/rent/';
	private $current_url = '';	
	Public function house_page(){
		//区域
		$dis = array(
		
				'chaoyang' => '朝阳',
				'haidian' => '海淀',
				'fengtai' => '丰台',
				'dongcheng' => '东城',
				'xicheng' => '西城',
				'shijingshan' => '石景山',
				'daxing' => '大兴',
				'tongzhou' => '通州',
				'shunyi' => '顺义',
				'changping' => '昌平',
		);
		//价格
		$p = array(
				'p1' => '小于1500',
				'p2' => '1500-3000',
				'p3' => '3000-5000',
				'p4' => '5000-8000',
				'p5' => '8000-10000',
				'p6' => '10000元以上',
		);
        $urlarr = [];
	    foreach ($dis as $k1 => $v1){
	    	foreach ($p as $k2 => $v2){
	            $this->current_url = $this->PRE_URL.$k1."/w1/".$k2;
	            $url = \QL\QueryList::run('Request', [
	                'target' => $this->current_url,
	            ])->setQuery([
	                'link' => ['font.font-houseNum','text', '', function($total){
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
	      }
	    return $urlarr;
	}
	/**
	 * 获取最新的房源种子
	 * @param type $num 条数
	 * @return type
	 */
	public function callNewData($num = 800){
			for($Page = 1; $Page <= $num; $Page++){
				$link[] = 'http://bj.5i5j.com/rent/w1o6n'.$Page;
						   
			}
			return $link;
	}
	
	
	/*
	 * 获取列表页
	 */
	public function house_list($url){
       		$house_info = array();
        	$house_info = \QL\QueryList::run('Request', [
            	'target' => $url,
        	])->setQuery([
            //获取单个房源url
            'link' => ['ul.list-body > li > div:nth-child(2) > h2:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
                return $u;
            }],
        	])->getData(function($item){
            	return "http://bj.5i5j.com".$item['link'];
        	});
        	
         	return $house_info;
	}
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		$house_info = \QL\QueryList::run('Request',[
			'target' => $source_url,
		])->setQuery([
			'house_title' => ['.house-tit', 'text', '-span', function($title){
				return str_replace(array("\t", "\n", "\r", " "),"", $title);
			}],
			'house_price' => ['.font-price', 'text', '', function($price){
				return $price;
			}],
			'cityarea_id' => ['.w-full > .main:eq(1) > a:eq(2)', 'text', '', function($cityarea_id){
			     return str_replace('租房','',$cityarea_id);
			}],
			
			'cityarea2_id' => ['.w-full > .main:eq(1) > a:eq(3)', 'text', '', function($cityarea2_id){
			     return str_replace('租房','',$cityarea2_id);
			}],

			'house_totalarea' => ['.house-info-2 > li:nth-child(3)', 'text', '-b', function($house_totalarea){
				return str_replace('平米', '', $house_totalarea);
			}],

			'house_room' => ['.house-info-2 > li:nth-child(1)', 'text', '-span', function($house_room){
				 preg_match("/(\d+)室/", $house_room, $hr);
				 return $hr[1];
			}],

			'house_hall' => ['.house-info-2 > li:nth-child(1)', 'text', '-span', function($house_hall){
				preg_match("/(\d+)厅/", $house_hall, $hh);
				return $hh[1];
			}],
			'house_toilet' => ['.house-info-2 > li:nth-child(1)', 'text', '-span', function($house_toilet){
			preg_match("/(\d+)卫/", $house_toilet, $ht);
			    return $ht[1];
			}],
			

			'house_toward' => ['.house-info-2 > li:nth-child(5)', 'text', '-b',function($house_toward){
				return $house_toward;
			}],

			'house_floor' => ['li.house-info-li2:nth-child(6)', 'text', '-b', function($house_floor){
// 				preg_match('/(低楼层|中楼层|高楼层)/',$house_floor,$hf);
				$temp_floor = explode("/",$house_floor);
				return str_replace('部', '', $temp_floor[0]);
			}],

			'house_topfloor' => ['li.house-info-li2:nth-child(6)', 'text', '-b', function($house_topfloor){
				$temp_topfloor = explode("/",$house_topfloor);
				return str_replace('层', '', $temp_topfloor[1]);
			}],

			'owner_name' => ['.mr-t', 'text','',function ($owner_name){
				return $owner_name;
			}],

			'owner_phone' => ['.house-broker-tel','text','-a', function ($owner_phone){
			    return trimall($owner_phone);
			}],

			'house_pic_unit' => ['.lb-small-pic > img', 'src', '', function($house_pic_unit){
				return $house_pic_unit;
			}],

			'house_pic_layout' => [],
			'house_fitment' => ['li.house-info-li2:nth-child(2)','text','-b'],
            'borough_name' => ['.house-info > li:nth-child(3)', 'text', '-b -span'],


// 			'house_desc' => ['div.noData:nth-child(2)','text',''],

			'house_number' => ['.house-code > span:nth-child(2)', 'text', '-br', function($house_number){
				$temp_house_number = explode("房源编号：",$house_number);
				return trimall($temp_house_number[1]);
			}],

			'house_type' => [],

			'house_built_year' => ['li.house-info-li2:nth-child(4)','text','-b',function($house_built_year){
			   return str_replace('年', '', $house_built_year);
			}],

			'house_relet' => [],

			'house_style' => [],
		])->getData(function($data) use($source_url){
			$data['company_name'] = '我爱我家';
			//下架检测
			$data['off_type'] = $this->is_off($source_url);
			return $data;
		});
		foreach((array)$house_info as $key => $value){
			if(isset($house_info[$key]['house_pic_unit'])){
				$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
			}
		}
		$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
		
		$html = $this->getUrlContent($source_url,false,$context);
		$ul = explode('<body>', $html);
		preg_match("/[\x{0000}-\x{ffff}]*?<\/html>/u", $ul[1], $detail);
		$html = $detail[0];
		preg_match("/<div\s*class=\"new\-broker\-3[\x{0000}-\x{ffff}]*?<p\sclass\=\"update-time/u", $html, $desc);
		$house_info[0]['house_desc'] = trimall(HTMLSpecialChars(strip_tags($desc[0])));
		if(empty($html)){
			$html = $this->getUrlContent($source_url);
		}
		$house_info[0]['content']=$html;
		return $house_info[0];
}
	
	
	
	/*
	 * 获取各类搜索条件
	 */
	//用于存放各个搜索条件对应的列表页第一页
	private  $url_list = array();
	
	private function get_condition($index,$PRE_URL){
	    $html = $this->getUrlContent($PRE_URL);
	    preg_match('/search\-term\-list[\x{0000}-\x{ffff}]+?<\/ul>/u',$html,$allCondition);
	    //城区搜索条件
	    preg_match('/区域:([\x{0000}-\x{ffff}]+?)<\/li>/u',$allCondition[0],$Dis);
	    preg_match_all('/<a\s*href=\"\/rent\/([\x{0000}-\x{ffff}]+?)\"/u',$Dis[1],$dis);
	    //面积搜索条件
	    preg_match('/面积:([\x{0000}-\x{ffff}]+?)<\/li>/u',$allCondition[0],$Area);
	    preg_match_all('/<a\s*href=\"\/rent\/([\x{0000}-\x{ffff}]+?)\"/u',$Area[1],$area);
	    //房型搜索条件
	    preg_match('/户型:([\x{0000}-\x{ffff}]+?)<\/li>/u',$allCondition[0],$Room);
	    preg_match_all('/<a\s*href=\"\/rent\/([\x{0000}-\x{ffff}]+?)\"/u',$Room[1],$room);
	    $this->url_list = array();
	    //第一个匹配为“不限”的空值
	    unset($dis[1][0]);unset($area[1][0]);unset($room[1][0]);
	    foreach($area[1] as $AREA){
	        foreach($room[1] as $ROOM){
	            $this->url_list[] = $PRE_URL.$dis[1][$index+1].$ROOM.$AREA."w1";
	        }
	    }
	}
	/*
	 * 获取搜索条件下的最大页
	 */
	private function get_maxPage($url){
	    $html = $this->getUrlContent($url);
	    preg_match('/font\-houseNum\">(\d+)</u',$html,$houseNum);
	    //总数除以每页12条，并向下取整
	    $maxPage = ceil($houseNum[1]/12);
	    //如果最大页抓空，返回0
	    if(!empty($houseNum)){
	        return $maxPage;
	    }else{
	        return 0;
	    }
	}
    //下架判断
    public function is_off($url,$html=''){
        return 2;
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $off_type = 1;
            $Tag = \QL\QueryList::Query($html,[
                "isOff" => ['.house_updown','class',''],
//                "404" => ['.main_top','class',''],
            ])->getData(function($item){
                return $item;
            });
            if(empty($Tag)){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
}