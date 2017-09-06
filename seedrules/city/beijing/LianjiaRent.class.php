<?php namespace beijing;
/**
 * @description 北京链家整租抓取规则
 * @classname 北京链家
 */
use QL\QueryList;

header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class LianjiaRent extends \city\PublicClass{
	public $PRE_URL = 'http://bj.lianjia.com/zufang/';
	private $current_url = '';
	
	/**
	 * 获取列表分页
	 */
	public function house_page(){
		$this->current_url = $this->PRE_URL;
		$html = getHtml($this->current_url);
		$url = \QL\QueryList::Query($html,[
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
// 		return $this->callNewData();
	}
	
	/*
	 * 获取列表页
	* */
	Public function house_list($url = ''){
	
	
// 		$json_2 = json_decode(getHtml($url), 1);
// 		foreach ($json_2['data']['list'] as $arr){
// 			if(!(strstr($arr['title'],"卧")||strstr($arr['title'],"合租")||$arr['is_ziroom']==1)){
// 				$house_info[] = "http://m.api.lianjia.com/house/zufang/detail?house_code=".$arr['house_code']."&share_agent_ucid=&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271&city_id=110000";
// 			}
// 		}
// 		return array_filter($house_info);
		if (!isExistsStr($url,"m.api.lianjia.com")) {
			$html = getHtml($url);
			$house_info = array();
			$house_info = \QL\QueryList::Query($html,[
					//获取单个房源url
					'link' => ['#house-lst > li > div:nth-child(2) > h2:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
						return $u;
					}],
					])->getData(function($item){
						return $item['link'];
					});
		} else {
			//http://bj.lianjia.com/ershoufang/101092240289.html
			$url_lj = "http://bj.lianjia.com/zufang/";
			//$wap_api = "http://m.api.lianjia.com/house/ershoufang/detail?house_code=".$arr['house_code']."&share_agent_ucid=&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271&city_id=110000";
			$json_2 = json_decode(getHtml($url), 1);
			foreach ($json_2['data']['list'] as $arr) {
				$house_info[] = $url_lj . $arr['house_code'] . ".html";
			}
		}
		return $house_info;
		
		
		
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		$house_info=array();
		$json = array();
		$tmp = explode('/', $source_url);
		$tmp1 = explode('.', $tmp[4]);
		$house_code = $tmp1[0];
		$wap_api = "http://m.api.lianjia.com/house/zufang/detail?house_code=".$house_code."&share_agent_ucid=&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271&city_id=110000";
		$json = json_decode(getHtml($wap_api), 1);
		
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
			return false;
		}else{
			$h = \QL\QueryList::run('Request',[
					'target' => $json2['m_url'],
					])->setQuery([
							'flag' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)','text','-span',function($flag){
								return trimall($flag);
							}],
							])->getData(function($data) use($source_url){
								return $data;
							});
			if($h[0]['flag']=="合租"){
				return false;
			}				
		}
		$house_info['source_url']=$json2['m_url'];
		$house_info['content']=getHtml($source_url);
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
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100){
    	$url = 'http://bj.lianjia.com/zufang/pg{$page}rco10rt1bd1/';
        $data = [];
        for($i = 1; $i <= $num; $i++){
            $data[] = str_replace('{$page}', $i, $url);
        }
        return $data;
    } 
}