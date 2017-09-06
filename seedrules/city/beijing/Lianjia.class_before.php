<?php namespace beijing;
/**
 * @description 北京链家地产二手房抓取规则
 * @classname 北京链家
 */

Class Lianjia_Before extends \city\PublicClass{
	public $PRE_URL = 'http://bj.lianjia.com/ershoufang/';
	private $current_url = '';
	
	/**
	 * 获取列表分页
	 */
	public function house_page(){
		//区域
		$dis = array(
				'dongcheng' => '东城',
				'xicheng' => '西城',
				'chaoyang' => '朝阳',
				'haidian' => '海淀',
				'fengtai' => '丰台',
				'shijingshan' => '石景山',
				'tongzhou' => '通州',
				'changping' => '昌平',
				'daxing' => '大兴',
				'yizhuangkaifaqu' => '亦庄开发区',
				'shunyi' => '顺义',
				'fangshan' => '房山',
				'mentougou' => '门头沟',
				'pinggu' => '平谷',
				'huairou' => '怀柔',
				'miyun' => '密云',
				'yanqing' => '延庆',
				'yanjiao' => '燕郊',
				'yanjiaochengqu' => '燕郊城区',
		);
		//价格
		$p = array(
				'p1' => '100万以下',
				'p2' => '100-150万',
				'p3' => '150-200万',
				'p4' => '250-300万',
				'p5' => '300-500万',
				'p6' => '500-1000万',
				'p7' => '250-300万',
				'p8' => '1000万以上',
		);
		$urlarr = [];
		foreach ($dis as $k1 => $v1){
			foreach ($p as $k2 => $v2){
				$this->current_url = $this->PRE_URL.$k1.'/'.$k2;
                                $html = getHtml($this->current_url);
				$url = \QL\QueryList::Query($html,[
								'link' => ['.total > span:nth-child(1)','text', '', function($total){
									$maxPage = ceil($total/30);
									$link = [];
									for($Page = 1; $Page <= $maxPage; $Page++){
										$link[] = $this->current_url.'pg'.$Page;
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
	
	/*
	 * 获取列表页
	* */
	Public function house_list($url = ''){
                $html = getHtml($url);
		$house_info = array();
		$house_info = \QL\QueryList::Query($html,[
						//获取单个房源url
						'link' => ['.listContent > li > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
							return $u;
						}],
						])->getData(function($item){
							return $item['link'];
						});
						return $house_info;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
                $html = getHtml($source_url);
		$house_info = \QL\QueryList::Query($html,[
						'house_title' => ['.main', 'text', '', function($title){
							return str_replace(array("\t", "\n", "\r", " "),"", $title);
						}],
						'house_price' => ['span.total', 'text', '', function($price){
							return $price;
						}],
						'cityarea_id' => ['.l-txt > a:nth-child(5)', 'text', '', function($cityarea_id){
							return str_replace('二手房','',$cityarea_id);
						}],
						'cityarea2_id' => ['.l-txt > a:nth-child(7)', 'text', '', function($cityarea2_id){
							return str_replace('二手房','',$cityarea2_id);
						}],
	
						'house_totalarea' => ['div.area > div:nth-child(1)', 'text', '', function($house_totalarea){
							return str_replace('平米', '', $house_totalarea);
						}],
	
						'house_room' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_room){
							preg_match("/(\d+)室/", $house_room, $hr);
							return $hr[1];
						}],
	
						'house_hall' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_hall){
							preg_match("/(\d+)厅/", $house_hall, $hh);
							return $hh[1];
						}],
	
						'house_kitchen' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_kitchen){
							preg_match("/(\d+)厨/", $house_kitchen, $hk);
							return $hk[1];
						}],
						'house_toilet' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_toilet){
							preg_match("/(\d+)卫/", $house_toilet, $ht);
							return $ht[1];
						}],
							
	
						'house_toward' => ['.houseInfo > div:nth-child(2) > div:nth-child(1)', 'text', ''],
	
						'house_floor' => ['.room > div:nth-child(2)', 'text', '', function($house_floor){
							preg_match('/(低楼层|中楼层|高楼层)/',$house_floor,$hf);
							return str_replace('楼层', '', $hf[1]);
						}],
	
						'house_topfloor' => ['.room > div:nth-child(2)', 'text', '', function($house_topfloor){
							preg_match('/共(\d+)层/',$house_topfloor,$htf);
							return $htf[1];
						}],
	
						'owner_name' => ['div.brokerInfo:nth-child(5) > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)', 'text', ],
	
						'owner_phone' => ['div.brokerInfo:nth-child(5) > div:nth-child(2) > div:nth-child(3)', 'text', '', function ($op){
							$op = trimall($op);
							return str_replace('转',',',$op);
						}],
	
						'house_pic_unit' => ['.smallpic > li > img', 'src', '', function($house_pic_unit){
							return $house_pic_unit;
						}],
	
						'house_pic_layout' => [],
	
						'house_fitment' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(9)','text','-span'],
	
						'borough_name' => ['a.info', 'text', ''],
						
						'house_desc' => ['div.noData:nth-child(2)','text',''],
	
						'house_number' => ['.houseRecord > span:nth-child(2)', 'text', '-a -br', function($house_number){
							return trimall($house_number);
						}],
	
						'house_type' => [],
	
						'house_built_year' => ['div.area:nth-child(3) > div:nth-child(2)','text','',function($house_built_year){
							preg_match('/(\d+)年建/',$house_built_year,$hby);
							return $hby[1];
						}],
	
						'house_relet' => [],
	
						'house_style' => [],
                                                'tag' => ['#introduction > div > div.newwrap.baseinform > div.introContent.showbasemore > div.tags > div.content a', 'text']
						])->getData(function($data) use($source_url){
							$data['company_name'] = '链家';
							//下架检测
							//			$data['off_type'] = $this->is_off($source_url);
							return $data;
						});
                                                $tag = [];
						foreach((array)$house_info as $key => $value){
							if(isset($house_info[$key]['house_pic_unit'])){
								$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
							}
                                                        if(!empty($value['tag'])){
                                                            $tag[] = $value['tag'];
                                                        }
						}
                                                $tag = implode("#",$tag);
						$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
                                                $house_info[0]['tag'] = $tag;
						return $house_info[0];
	}
	
	/*
	 * 下架检测
	* */
	public function is_off($url,$html=''){
		if(!empty($url)){
			if(empty($html)){
				$html = $this->getUrlContent($url);
			}
			//抓取下架标识
			$off_type = 1;
			if($html){
				$Tag = \QL\QueryList::Query($html,[
						"isOff" => ['.main > span:nth-child(1)','text',''],
						"404" => ['.sub-tle','text',''],
						])->getData(function($item){
							return $item;
						});
						if(empty($Tag) ){
							$off_type = 2;
						}
			}
			return $off_type;
		}
		return -1;
	}
        
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://bj.lianjia.com/ershoufang/';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['.secondcon > ul:nth-child(1) > li:nth-child(3) > span:nth-child(2) > strong:nth-child(1) > a:nth-child(1)','text'],
	    ]);
	    return $totalNum;
	    // 	    return 0;
	}
        
    /*
     * 抓取房源对应标签
     */
    public function getTags($web_url,$html=''){
        if(empty($html)){
            $html = file_get_contents($web_url);
        }
        $Tags = \QL\QueryList::Query($html,[
            "school" => ['.fang05-ex > span:nth-child(1)','text',''],
            "subway" => ['.fang-subway-ex > span:nth-child(1)','text',''],
            //满五唯一
            "taxfree"=> ['.taxfree-ex > span:nth-child(1)','text',''],
            //房本满两年
            "twoyear"=>['.five-ex > span:nth-child(1)','text',''],
            //独家
            'unique'=>['.unique-ex > span:nth-child(1)','text',''],
            //有钥匙
            'haskey'=>['.haskey-ex > span:nth-child(1)','text',''],
            //不限购
            'restriction'=>['.is_restriction-ex > span:nth-child(1)','text','']
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
        $url = 'http://bj.lianjia.com/ershoufang/pg{$page}co32/';
        $data = [];
        for($i = 1; $i <= $num; $i++){
            $data[] = str_replace('{$page}', $i, $url);
        }
        return $data;
    }
}