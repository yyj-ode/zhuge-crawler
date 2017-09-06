<?php namespace beijing;
/**
 * @description 北京酷房 整租抓取规则
 * @classname 北京酷房
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');
Class KufangRent  extends \city\PublicClass{
	public $PRE_URL = 'http://beijing.koofang.com/rent/';
	private $current_url = '';
	private $current_url1 = '';
	
	
    public function house_page(){
        //区域
        $dis = array(
        		
         		'c10' => '朝阳',
        		'c20' => '海淀',
        		'c21' => '丰台',
        		'c22' => '东城',
        		'c23' => '西城',
        		'c25' => '宣武',
        		'c26' => '石景山',
        		'c27' => '昌平',
        		'c28' => '通州',
        		'c29' => '大兴',
        		'c30' => '顺义',
        		'c31' => '怀柔',
        		'c32' => '房山',
        		'c34' => '密云',
        		'c37' => '周边',
        );
        //价格
        $p = array(
        		'p0-1000' => '40万以下',
        		'p1000-2000' => '40-60万',
        		'p2000-3000' => '60-90万',
        		'p3000-5000' => '90-120万',
        		'p5000-8000' => '120-150万',
        		'p8000-10000' => '150-200万',
        		'p10000-15000' => '200-300万',
        		'p15000-20000' => '200-300万',
        		'p20000-25000' => '200-300万',
        		'p25000-0' => '200-300万',
        );
        
        $urlarr = [];
	    foreach ($dis as $k1 => $v1){
// 	    	foreach ($p as $k2 => $v2){
	            $this->current_url = $this->PRE_URL.$k1."/";
	            $url = \QL\QueryList::run('Request', [
	                'target' => $this->current_url,
	            ])->setQuery([
	                'link' => ['.tongji > span:nth-child(2)','text', '', function($total){
	                    $maxPage = ceil($total/30);
                        $link = [];
	                    for($Page = 1; $Page <= $maxPage; $Page++){
                            $link[] = $this->current_url.'pg'.$Page."/";
	                    }
	                    return $link;
	                }],
	                ])->getData(function($item){
	                    return $item['link'];
	                });
	                
                $urlarr = array_merge($urlarr,$url[0]);
// 	        }
	    }
	    foreach ($p as $k1 => $v1){
	    	$this->current_url1 = $this->PRE_URL."c10/".$k1;
	    	$url1 = \QL\QueryList::run('Request', [
	    			'target' => $this->current_url1,
	    			])->setQuery([
	    					'link' => ['.tongji > span:nth-child(2)','text', '', function($total){
	    						$maxPage = ceil($total/30);
	    						$link = [];
	    						for($Page = 1; $Page <= $maxPage; $Page++){
	    							$link[] = $this->current_url1.'pg'.$Page."/";
	    						}
	    						return $link;
	    					}],
	    					])->getData(function($item){
	    						return $item['link'];
	    					});
	    					$urlarr = array_merge($urlarr,$url1[0]);
	    }
	    
	    return $urlarr;
        
    }
    /*
	 * 获取列表页
	 * */
    Public function house_list($url = ''){
        $house_info = array();
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['div.fangyuan > div:nth-child(2) > p:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
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
		$house_info = \QL\QueryList::run('Request',[
			'target' => $source_url,
		])->setQuery([
			'house_title' => ['.Details_Page_two_l_h > dl:nth-child(1) > dt:nth-child(1)', 'title', '', function($title){
				return str_replace(array("\t", "\n", "\r", " "),"", $title);
			}],
			'house_price' => ['.Details_Page_two_cent_r_up > ul:nth-child(1) > li:nth-child(1) > span:nth-child(3)', 'text', '', function($price){
				return $price;
			}],
			'cityarea_id' => ['.Details_Page_one > a:nth-child(3)', 'text', '', function($cityarea_id){
			     return $cityarea_id;
			}],
			'cityarea2_id' => ['.Details_Page_one > a:nth-child(4)', 'text', '', function($cityarea2_id){
			     return $cityarea2_id;
			}],

			'house_totalarea' => ['.Details_Page_two_cent_r_up > ul:nth-child(1) > li:nth-child(3)', 'text', '-span', function($house_totalarea){
				return str_replace('㎡', '', $house_totalarea);
			}],

			'house_room' => ['.Details_Page_two_cent_r_up > ul:nth-child(1) > li:nth-child(2)', 'text', '-span', function($house_room){
				 preg_match("/(\d+)室/", $house_room, $hr);
				 return $hr[1];
			}],

			'house_hall' => ['.Details_Page_two_cent_r_up > ul:nth-child(1) > li:nth-child(2)', 'text', '-span', function($house_hall){
				preg_match("/(\d+)厅/", $house_hall, $hh);
				return $hh[1];
			}],

			'house_kitchen' => ['.Details_Page_two_cent_r_up > ul:nth-child(1) > li:nth-child(2)', 'text', '-span', function($house_kitchen){
				preg_match("/(\d+)厨/", $house_kitchen, $hk);
				return $hk[1];
			}],
			'house_toilet' => ['.Details_Page_two_cent_r_up > ul:nth-child(1) > li:nth-child(2)', 'text', '-span', function($house_toilet){
			preg_match("/(\d+)卫/", $house_toilet, $ht);
			    return $ht[1];
			}],
			
			'house_toward' => ['li.borde_das:nth-child(1) > span:nth-child(2) > span:nth-child(3)', 'text', ''],

			'house_floor' => ['li.borde_das:nth-child(2) > span:nth-child(1) > span:nth-child(3)', 'text', '', function($house_floor){
				preg_match('/(低楼层|中楼层|高楼层)/',$house_floor,$hf);
				return str_replace('楼层', '', $hf[1]);
			}],

			'house_topfloor' => ['li.borde_das:nth-child(2) > span:nth-child(1) > span:nth-child(3)', 'text', '', function($house_topfloor){
				preg_match('/共(\d+)层/',$house_topfloor,$htf);
				return $htf[1];
			}],

			'owner_name' => ['.name_wu > a:nth-child(1)', 'text', ],

			'owner_phone' => ['.phone_150', 'text', '', function ($op){
			    $op = trimall($op);
			    return $op;
			}],

			'house_pic_unit' => ['div.ddddddd > div:nth-child(1) > p:nth-child(1) > a:nth-child(1)', 'href', '', function($house_pic_unit){
				return $house_pic_unit;
			}],
// 			'house_pic_layout' => ['.Details_Page_three > div:nth-child(2) > div:nth-child(1) > div:nth-child(1) > p:nth-child(1) > a:nth-child(1)', 'href', '', function($house_pic_layout){
// 				return $house_pic_layout;
// 			}],
			'house_fitment' => ['li.borde_das:nth-child(3) > span:nth-child(1) > span:nth-child(3)','text',''],

            'borough_name' => ['.Details_Page_one > a:nth-child(5)', 'text', ''],
            				  
			'house_desc' => ['.Details_Page_four_down_l_02 > p:nth-child(2)','text',''],

			'house_configroom' => ['#limittext', 'text', '', function($house_number){
				$temp_conifig  = str_replace(array("/", " "),"#", $house_number);
				if($temp_conifig){
					return $temp_conifig."#";
				}
			}],
			'house_number' => ['.Details_Page_two_l_h > dl:nth-child(1) > dd:nth-child(2)', 'text', '-a -br', function($house_number){
				$list = explode("：",$house_number);
				return $list[1];
			}],

			'house_type' => [],

			'house_built_year' => ['li.borde_das:nth-child(1) > span:nth-child(1) > span:nth-child(3)','text','',function($house_built_year){
			    return str_replace('年', '', $house_built_year);
			}],

			'house_relet' => [],

			'house_style' => [],
		])->getData(function($data) use($source_url){
			$data['company_name'] = '酷房';
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
		$house_info[0]['content']=$this->getUrlContent($source_url);
		return $house_info[0];
	}
    //统计官网数据
    public function house_count(){
        $PRE_URL = 'http://beijing.koofang.com/sale/';
        $totalNum = $this->queryList($PRE_URL, [
            'total' => ['.tongji > span:nth-child(2)','text'],
        ]);
        return $totalNum;
        // 	    return 0;
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
                    "isOff" => ['.contenttop_err','text','',function ($item){
                        return preg_match("/不存在/",$item);
                    }],
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]['isOff']){
    					 return 1;
    			}else{
    				return 2;
    			}
                
            }
        }
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100){
    	$url1 = \QL\QueryList::run('Request', [
    			'target' => "http://beijing.koofang.com/rent/c1/sw5-0/",
    			])->setQuery([
    					'link' => ['.tongji > span:nth-child(2)','text', '', function($total){
    						return $total;
    					}],
    					])->getData(function($item){
    						return $item['link'];
    					});
    	$num = ceil($url1[0]/30);
    	$url = 'http://beijing.koofang.com/rent/c1/sw5-0pg{$page}/';
    	$data = [];
    	for($i = 1; $i <= $num; $i++){
    		$data[] = str_replace('{$page}', $i, $url);
    	}
    	return $data;
    }
}
                