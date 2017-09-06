<?php namespace beijing;
/**
 * @description 北京链家Wap整租抓取规则
 * @classname 北京Wap链家
 */
use QL\QueryList;

header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class LianjiaRentWap extends \city\PublicClass{
	public $PRE_URL = 'http://bj.lianjia.com/zufang/';
	private $current_url = '';
	
	/**
	 * 获取列表分页
	 */
	public function house_page(){
		$this->current_url = $this->PRE_URL;
		$url = \QL\QueryList::run('Request', [
				'target' => $this->current_url,
				])->setQuery([
						'link' => ['.list-head > h2:nth-child(1) > span:nth-child(1)','text', '', function($total){
							$maxPage = floor($total/20);
							$link = [];
							for($Page = 0; $Page <= $maxPage; $Page++){
								$temp =20*$Page;
								$link[] = "http://m.api.lianjia.com/house/zufang/search?channel=zufang&city_id=110000&limit_count=20&limit_offset=".$temp."&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271";
							}
							return $link;
						}],
						])->getData(function($item){
							return $item['link'];
						});
						return $url[0];
	}
	
	/*
	 * 获取列表页
	* */
	Public function house_list($url = ''){
	
	
		$json_2 = json_decode(getSnoopy($url), 1);
		foreach ($json_2['data']['list'] as $arr){
			if(!(strstr($arr['title'],"卧")||strstr($arr['title'],"合租")||$arr['is_ziroom']==1)){
				$house_info[] = "http://m.api.lianjia.com/house/zufang/detail?house_code=".$arr['house_code']."&share_agent_ucid=&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271&city_id=110000";
			}
		}
		return $house_info;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		$json = json_decode(getSnoopy($source_url), 1);
		$json2 = $json['data'];
		$house_info['source'] = 1;
		$house_info['company'] = "链家官网";
		//标题
		$house_info['house_title'] =trimall($json2['title']);
	
		$house_info['borough_name']=$json2['community_name'];
		$house_info['cityarea2_id'] =$json2['bizcircle_name'];
		$house_info['cityarea_id'] =$json2['district_name'];
	
	
		$house_info['house_price']=$json2['price'];
	
		//总面积
		$house_info['house_totalarea']=$json2['area'];
	
		//室
		$house_info['house_room']=$json2['blueprint_bedroom_num'];
		//厅
		$house_info['house_hall']=$json2['blueprint_hall_num'];
	
		//朝向
		$house_info['house_toward']=$json2['orientation'];
	
		preg_match("/(高|中|低)楼层/", $json2['floor_state'], $floor);
		preg_match("/\/(\d+?)层/", $json2['floor_state'], $topfloor);
		//楼层
		$house_info['house_floor']=$floor[1];
		$house_info['house_topfloor']=$topfloor[1];
		//建造年份
		$house_info['house_built_year']=$json2['building_finish_year'];
	
		$house_info['owner_phone'] = $json2['agent']['mobile_phone'];
	
		$house_info['owner_name'] =$json2['agent']['name'];
	
		$house_info['house_number'] =$json2['house_code'];
		$house_info['house_fitment'] =$json2['decoration'];
		foreach ($json2['picture_list'] as $pic){
			if($pic['type']=='blueprint'){
				$house_pic_layout[]=$pic['url'];
			}else{
				$house_pic_unit[]=$pic['url'];
			}
		}
		
	
		//匹配到的图片title待以后扩展使用
		$house_info['house_pic_unit']= implode("|", $house_pic_unit);
		$house_info['house_pic_layout']= implode("|", $house_pic_layout);
		$house_info['house_desc']= trimall($json2['agent_house_comment'][0]['content']);
		
		if($json2['agent']['agent_level']=="自如管家" || strstr($house_info['house_desc'],"合租")|| strstr($house_info['house_desc'],"自如")){
			unset($house_info);
			$house_info="";
		}
		$house_info['source_url']=$_SERVER['config']['source_url_tag'].$json2['m_url'];
		
		return $house_info;
	}
	
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://bj.lianjia.com/zufang/';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['.list-head > h2:nth-child(1) > span:nth-child(1)','text'],
	    ]);
	    return $totalNum;
	    // 	    return 0;
	}
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
                    "isOff" => ['.shelves','class',''],
//                     "404" => ['.sub-tle','text',''],
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
    /*
     * 抓取房源对应标签
     */
    public function getTags($web_url,$html=''){
        if(empty($html)){
            $html = $this->getUrlContent($web_url);
        }
        $Tags = \QL\QueryList::Query($html,[
            "school" => ['.fang05-ex > span:nth-child(1)','text',''],
            "subway" => ['.fang-subway-ex > span:nth-child(1)','text',''],
            //集中供暖
            "heating"=> ['.heating-ex > span:nth-child(1)','text',''],
            //独立阳台
            "balcony"=>['.independentBalcony-ex > span:nth-child(1)','text',''],
            //独卫
            'bathroom'=>['.privateBathroom-ex > span:nth-child(1)','text',''],
            //随时看房
            'haskey'=>['.haskey-ex > span:nth-child(1)','text',''],
        ])->getData(function($item){
            return $item;
        });
        return implode("#",$Tags[0]);
    }
}