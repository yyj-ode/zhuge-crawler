<?php namespace beijing;
/**
 * @description 北京麦田二手房
 * @classname 北京麦田
 */

class Mai extends \city\PublicClass{
    public $URL = 'http://bj.maitian.cn';

	/**
	 * 获取列表分页
	 */
	public function house_page(){
		$urls = \QL\QueryList::run('Request', [
			'target' => $this->URL.'/esfall',
		])->setQuery([
			'link' => ['.screening p span','text', '', function($total){
				$maxPage = intval($total/10);
				for($minPage = 1; $minPage <= $maxPage; $minPage++){
					$urlarr[] = $this -> URL."/esfall/PG".$minPage;
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
		return \QL\QueryList::run('Request', [
			'target' => $url,
		])->setQuery([
			'link' => ['.list_title h1 a', 'href', '', function($content){
				return $this->URL.$content;
			}],
		])->getData(function($item){
			return $item['link'];
		});
	}


	/*
	 * 获取详情
	*/
	public function house_detail($source_url){

		$house_info = \QL\QueryList::run('Request',[
			'target' => $source_url,
		])->setQuery([
			'house_title' => ['.clearfix .clearfix samp', 'text', '-div', function($title){
				return str_replace(array("\t", "\n", "\r", " "),"", $title);
			}],
			'house_price' => ['table tr td', 'text', '', function($price){
				$price = str_replace('售价：', '', $price);
				if(isExistsStr($price, '亿')){
					$price = trim(str_replace('亿元', '', $price));
					$price = $price*10000;
				}else{
					$price = trim(str_replace('万元', '', $price));
				}
				return $price;
			}],

			'house_totalarea' => ['body > section.home_content.clearfix > div.hc_left.clearfix > table > tbody > tr:nth-child(5) > td:nth-child(1)', 'text', '-span', function($house_totalarea){
				return str_replace('㎡', '', $house_totalarea);
			}],

			'house_room' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(5) > td:nth-child(2)', 'text', '-span', function($house_room){
				preg_match("/(\d+)室/", $house_room, $house_room);
				return $house_room[1];
			}],

			'house_hall' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(5) > td:nth-child(2)', 'text', '-span', function($house_hall){
				preg_match("/(\d+)厅/", $house_hall,$house_hall);
				return $house_hall[1];
			}],

			'house_toilet' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(5) > td:nth-child(2)', 'text', '-span', function($house_toilet){
				preg_match("/(\d+)卫/", $house_toilet, $house_toilet);
				return $house_toilet[1];
			}],
			
			'house_kitchen' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(5) > td:nth-child(2)', 'text', '-span', function($house_kitchen){
			    preg_match("/(\d+)厨/", $house_kitchen, $house_kitchen);
			    return $house_kitchen[1];
			}],

			'house_toward' => ['body > section.home_content.clearfix > div.hc_left.clearfix > table > tbody > tr:nth-child(6) > td:nth-child(1)', 'text', '-span'],

			'house_floor' => ['body > section.home_content.clearfix > div.hc_left.clearfix > table > tbody > tr:nth-child(6) > td:nth-child(2)', 'text', '-span', function($house_floor){
				$house_floor = explode('/', $house_floor);
				return str_replace('楼层', '', $house_floor[0]);
			}],

			'house_topfloor' => ['body > section.home_content.clearfix > div.hc_left.clearfix > table > tbody > tr:nth-child(6) > td:nth-child(2)', 'text', '', function($house_topfloor){
				$house_topfloor = explode('/', $house_topfloor);
				return str_replace('层', '', $house_topfloor[1]);
			}],

			'owner_name' => ['body > section.home_content.clearfix > div.hc_right.clearfix > dl > dd.top_jl > span > a', 'text', ],

			'owner_phone' => ['body > section.home_content.clearfix > div.hc_right.clearfix > ul', 'text', '-i' ],

			'house_pic_unit' => ['#myGallery li img', 'src', '', function($house_pic_unit){
				return $house_pic_unit;
			}],

			'house_pic_layout' => [],

			'house_fitment' => [],

            'borough_name' => ['td.one:nth-child(1) > a:nth-child(1) > label:nth-child(1) > span:nth-child(2)', 'text', ''],

			'house_desc' => ['body > section.home_content.clearfix > div.hc_left.clearfix > table > tbody > tr:nth-child(8) > td > table > tbody > tr > td:nth-child(2) > label:nth-child(2)', 'text', '-i'],

			'house_number' => ['body > section.home_content.clearfix > div.hc_right.clearfix > p', 'text', '-a -br', function($house_number){
//				preg_match("/<input\s*id=\"HouseID\"\s*type=\"hidden\"\s*value=\"(\w+?)\"/u", $house_number, $number);
				$house_number = explode('：', $house_number);
				preg_match("/\w+/", $house_number[1], $number);
				return $number[0];
			}],

			'house_type' => [],

			'house_built_year' => [],

			'house_relet' => [],

			'house_style' => [],
            'cityarea_id'=>['.crumbs > ul:nth-child(1) > a:nth-child(3)','text','',function($item){
                return str_replace("二手房",'',$item);
            }],
            'cityarea2_id'=>['.crumbs > ul:nth-child(1) > a:nth-child(4)','text','',function($item){
                return str_replace("二手房",'',$item);
            }]
		])->getData(function($data){
			$data['company_name'] = '麦田';
			return $data;
		});
		foreach((array)$house_info as $key => $value){
			if(isset($house_info[$key]['house_pic_unit'])){
				$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
			}
		}
		$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
        $house_info = $house_info[0];
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url);
		return $house_info;
	}
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://maitian.cn/esfall';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['.screening > p:nth-child(3) > span:nth-child(1)','text'],
	    ]);
	    return $totalNum;
	    // 	    return 0;
	}//下架判断
    public function is_off($url,$html=''){
        return 2;
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
                    //"isOff" => [],
                    "isOff" => ['.upgrade','class',''],
//                     "404" => ['.upgrade','class',''],
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


	/**
	 * 获取最新的房源种子
	 * @param type $num 条数
	 * @return type
	 */
	public function callNewData($num = 100){
		$urls = \QL\QueryList::run('Request', [
			'target' => $this->URL.'/esfall',
		])->setQuery([
			'link' => ['.screening p span','text', '', function($total){
				$maxPage = 100;
				for($minPage = 1; $minPage <= $maxPage; $minPage++){
					$urlarr[] = $this -> URL."/esfall/PG".$minPage;
				}
				return $urlarr;
			}],
		])->getData(function($item){
			return $item['link'];
		});
		return $urls[0];
	}
}