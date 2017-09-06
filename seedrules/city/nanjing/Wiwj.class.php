<?php
namespace nanjing;
/**
 * @description 南京我爱我家地产二手房抓取规则
 * @classname 南京我爱我家
 */


class Wiwj extends \city\PublicClass
{
	public $PRE_URL = 'http://nj.5i5j.com/exchange/';
	private $current_url = '';
	private $tag = [
		'随时看房',
		'学区房',
		'业主急售',
		'全权委托',
		'地铁口旁',
		'超低价格',
		'新装婚房',
		'院子',
		'顶层露台',
		'阳光房',
		'送阁楼',
		'复式',
		'挑高',
		'车位',
		'毛坯',
	];
    /*
     * 抓取
    */
    public function house_page(){
        //区域
//		return $this->callNewData();
        $dis = array(
        		'baixia' => '白下',
        		'xiaguan' => '下关',
        		'gulou' => '鼓楼',
        		'jianye' => '建邺',
        		'qinhuai' => '秦淮',
        		'xuanwu2' => '玄武',
        		'yuhuatai' => '雨花台',
        		'qixia' => '栖霞',
        		'jiangning' => '江宁',
        		'pukou' => '浦口',
        		'liuhe' => '六合',
        );
        $urlarr = [];
	    foreach ($dis as $k1 => $v1){
			$this->current_url = $this->PRE_URL.$k1.'/u1';
			$url = \QL\QueryList::run('Request', [
				'target' => $this->current_url,
			])->setQuery([
			'link' => ['.font-houseNum','text', '', function($total){
				$maxPage = ceil($total/30);
				return $maxPage;
			}],
			])->getData(function($item){
				return $item['link'];
			});
			$maxPage = $url[0];

			$link = [];
			for($Page = 1; $Page <= $maxPage; $Page++){
				$link[] = $this->current_url.'n'.$Page;
			}
			$urlarr = array_merge($urlarr,$link);
		}
	    return $urlarr;
        
    }
	/*
	 * 获取列表页
	*/
	public function house_list($url){

		for($i = 0;$i<3;$i++){
			$house_info = \QL\QueryList::run('Request', [
				'target' => $url,
			])->setQuery([
				//获取单个房源url
				'link' => ['ul.list-body > li > div:nth-child(2) > h2:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
					return $u;
				}],
			])->getData(function($item){
				return "http://nj.5i5j.com".$item['link'];
			});
			if($house_info){
				break;
			}
			sleep(2);
		}
		if(!$house_info) writeLog( 'Wiwj_list'.__FUNCTION__, ['url'=>$url, 'msg' => '种子为空'], true);

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
			     return str_replace('二手房','',$cityarea_id);
			}],
			
			'cityarea2_id' => ['.w-full > .main:eq(1) > a:eq(3)', 'text', '', function($cityarea2_id){
			     return str_replace('二手房','',$cityarea2_id);
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

			'house_fitment' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(9)','text','-span'],
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

			'tag' => ['body > section.w-full.house-basic > div > h1.house-tit','text','',function($tag){
				$tags = [];
				$tag = trimall($tag);
				foreach((array)$this->tag as $val){
					if(isExistsStr($tag, $val)){
						$tags[] = $val;
					}
				}
				return implode('#', $tags);
			}],
		])->getData(function($data) use($source_url){
			$data['company_name'] = '我爱我家';
			$data['source'] = '4';
			$data['source_owner'] = '0';
			return $data;
		});
		foreach((array)$house_info as $key => $value){
			if(isset($house_info[$key]['house_pic_unit'])){
				if($key == 0){
					$house_info[$key]['house_pic_layout'] = $house_info[$key]['house_pic_unit'];
				}else {
					$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
				}
			}
		}
		$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
		return $house_info[0];
	}


	/**
	 * 获取最新的房源种子
	 * @author robert
	 * @return type
	 */
	public function callNewData(){
		$resultData = [];
		for($i = 1; $i <= 100; $i++){
			$resultData[] = $this->PRE_URL."o6n{$i}";
		}

		return $resultData;
	}
}