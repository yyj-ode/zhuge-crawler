<?php namespace beijing;
/**
 * @description 北京嗨住 整租房抓取规则
 * @classname 北京嗨住
 */


Class HaizhuRent  extends \city\PublicClass{
    Public $URl = 'http://hizhu.com/beijing/roomlist.html';
	Public function house_page(){
		$urls = \QL\QueryList::run('Request', [
			'target' => $this->URl,
		])->setQuery([
			'link' => ['p.common-minwidth > span:nth-child(1)','text', '', function($total){
			    
				$maxPage = intval($total/10);
				for($minPage = 1; $minPage <= $maxPage; $minPage++){
					$urlarr[] = 'http://hizhu.com/Home/House/scrollinfo.html?num='.$minPage;
				}
				return $urlarr;
			}],
		])->getData(function($item){
			return $item['link'];
		});
		return $urls[0];
	}

	/*
	 * 获取列表页
	 */
	public function house_list($url){
	    $house_info = \QL\QueryList::run('Request', [
	        'target' => $url,
	    ])->setQuery([
	        'link' => ['li.cf > div:nth-child(1) > a:nth-child(1)', 'href', '',],
	        'tag' => ['li.cf > div:nth-child(2) > p:nth-child(5) > span:nth-child(1)','text','',]
	        ])->getData(function($item){
	            if($item['tag'] == "整租"){
	                return "http://hizhu.com/beijing/".$item['link'];
	            }
	        });
		return array_filter($house_info);
	}
	
	/*
	 * 获取详情页
	 *
	 *  */
	public function house_detail($url){
		$html = file_get_contents($url);
		if (preg_match("/愿，住的好一点/", $html)) {
			$house_info['content']=$html;
			$house_info['house_title']="下架";
			return $house_info;
		}
		
       $house_info = \QL\QueryList::run('Request',[
			'target' => $url,
		])->setQuery([
			'house_title' => ['.container-header > div:nth-child(1) > h1:nth-child(2)', 'text', '', function($title){
				return str_replace(array("\t", "\n", "\r", " "),"", $title);
			}],
			'house_price' => ['.rent', 'text', '-span', function($price){
				return $price;
			}],

			'house_totalarea' => ['.house-info > li:nth-child(2)', 'text', '', function($house_totalarea){
				return str_replace('m²', '', $house_totalarea);
			}],

			'house_room' => ['.house-mesg-main > p:nth-child(2) > span:nth-child(1)', 'text', '', function($house_room){
				preg_match("/(\d+)室/", $house_room, $house_room);
				return $house_room[1];
			}],

			'house_hall' => ['.house-mesg-main > p:nth-child(2) > span:nth-child(1)', 'text', '', function($house_hall){
				preg_match("/(\d+)厅/", $house_hall,$house_hall);
				return $house_hall[1];
			}],

			'house_toilet' => ['.house-mesg-main > p:nth-child(2) > span:nth-child(1)', 'text', '', function($house_toilet){
				preg_match("/(\d+)卫/", $house_toilet, $house_toilet);
				return $house_toilet[1];
			}],
			
			'house_kitchen' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(5) > td:nth-child(2)', 'text', '-span', function($house_kitchen){
			    preg_match("/(\d+)厨/", $house_kitchen, $house_kitchen);
			    return $house_kitchen[1];
			}],

			'house_toward' => ['.house-mesg-main > p:nth-child(4) > span:nth-child(1)', 'text', ''],

			'house_floor' => ['.house-mesg-main > p:nth-child(3) > span:nth-child(1)', 'text', '-span', function($house_floor){
				$house_floor = explode('/', $house_floor);
				return str_replace('层', '', $house_floor[0]);
			}],

			'house_topfloor' => ['.house-mesg-main > p:nth-child(3) > span:nth-child(1)', 'text', '', function($house_topfloor){
				$house_topfloor = explode('/', $house_topfloor);
				return str_replace('层', '', $house_topfloor[1]);
			}],

			'owner_name' => ['', '', ],

			'owner_phone' => ['.tel-text', 'text', '' ,function ($owner_phone){
			    $owner_phone = str_replace('-','',$owner_phone);
			    return trimall(str_replace('转',',',$owner_phone));
			}],

			'house_pic_unit' => ['#list > img', 'src', '', function($house_pic_unit){
				return $house_pic_unit;
			}],

			'house_pic_layout' => [],

			'house_fitment' => [],

            'borough_name' => ['.container-header > div:nth-child(1) > h1:nth-child(2)', 'text', '', function($borough_name){
                return str_replace(array("\t", "\n", "\r", " "),"", $borough_name);
            }],

			'house_desc' => ['.house-other > p:nth-child(2)', 'text', '',function ($house_desc){
			    return trimall($house_desc);
			}],

			'house_number' => ['.house-mesg-main > p:nth-child(5) > span:nth-child(1)', 'text', '',],

			'house_type' => ['span.last:nth-child(3)','text',''],

			'house_built_year' => [],

			'house_relet' => [],

			'house_style' => [],
            'cityarea_id'=>['.house-address > span:nth-child(1)','text','',],
            'cityarea2_id'=>['.house-address > span:nth-child(2)','text','',]
		])->getData(function($data){
			$data['company_name'] = '嗨住';
			return $data;
		});
		foreach((array)$house_info as $key => $value){
			if(isset($house_info[$key]['house_pic_unit'])){
				$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
			}
		}
		$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
		$house_info[0]['off_type'] = $this->is_off($url);
        $house_info = $house_info[0];
		preg_match('/<div\s*class=\"house\-fac\-main\">[\x{0000}-\x{ffff}]+?<\/div>/u', $html, $result);
		preg_match_all('/<span[\x{0000}-\x{ffff}]+?span>/u', $result[0], $r);
		foreach($r[0] as $k=>$v) {
			if(strpos($v, 'opacity')) {
			}else {
				preg_match('/<span[^<>]+?>([^<>]+?)</u', $v, $resu);
				$config.= $resu[1]."#";
			}
		}
		//房屋配置
		$house_info['house_configroom'] = $config;

		
		$chengqu = array('朝阳','海淀','丰台','东城','西城','崇文','宣武','石景山','昌平','通州','大兴','顺义','怀柔','房山','门头沟','密云','平谷','延庆','周边');
		if (!(in_array($house_info['cityarea_id'], $chengqu))){
			return false;
		}
		$house_info['source_url']=$url;
		$house_info['content']=$html;
		return $house_info;
		
		
		
	}
	//下架判断
	public function is_off($url,$html=''){
	    return 2;//TODO 数据库无下架数据
	    if(!empty($url)){
	        if(empty($html)){
	            $html = $this->getUrlContent($url);
	        }
	        //抓取下架标识
	        $off_type = 1;
	        $Tag = \QL\QueryList::Query($html,[
	            "isOff" => ['.view','text','',function($item){
	                return preg_match("/出租/",$item);
	            }],
	            //                    "404" => ['.nopage','class',''],#zreserve
	        ])->getData(function($item){
	            return $item;
	        });
	        if($Tag[0]['isOff']==NULL){
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
		$this->house_page();
	}
}