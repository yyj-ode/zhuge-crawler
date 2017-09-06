<?php
namespace beijing;
/**
 * @description 北京爱屋吉屋 整租抓取规则
 * @classname 北京爱屋吉屋整租
 */

class IwjwRent extends \city\PublicClass{
	public $PRE_URL = 'http://www.iwjw.com/chuzu/beijing/';
	private $current_url = '';
	/*
	 * 抓取
	*/
	public function house_page(){
		//区域
		$dis = array(
	
				'g1id12439' => '朝阳区',
				'g1id12440' => '丰台区',
				'g1id12441' => '海淀区',
				'g1id12442' => '东城区',
				'g1id12443' => '西城区',
				'g1id12444' => '石景山区',
				'g1id12445' => '门头沟区',
				'g1id12446' => '房山区',
				'g1id12447' => '通州区',
				'g1id12448' => '顺义区',
				'g1id12449' => '昌平区',
				'g1id12450' => '大兴区',
				'g1id12451' => '怀柔区',
				'g1id12452' => '平谷区',
				'g1id12455' => '密云县',
				'g1id12456' => '延庆县',
		);
		//价格
// 		$p = array(
// 				'ip1' => '40万以下',
// 				'ip2' => '40-60万',
// 				'ip3' => '60-90万',
// 				'ip4' => '90-120万',
// 				'ip5' => '120-150万',
// 				'ip6' => '150-200万',
// 				'ip7' => '200-300万',
// 				'ip8' => '300-500万',
// 		);
	
		$urlarr = [];
	    foreach ($dis as $k1 => $v1){
// 	        foreach ($p as $k2 => $v2){
	            $this->current_url = $this->PRE_URL.$k1;
	            $url = \QL\QueryList::run('Request', [
	                'target' => $this->current_url,
	            ])->setQuery([
	                'link' => ['.relative-num','text', '', function($total){
	                    $maxPage = ceil($total/30);
                        $link = [];
	                    for($Page = 1; $Page <= $maxPage; $Page++){
	                    	if($Page==1){
	                    		$link[] = $this->current_url;
	                    	}else{
	                    		$link[] = $this->current_url.'p'.$Page;
	                    	}
                            
	                    }
	                    return $link;
	                }],
	                ])->getData(function($item){
	                    return $item['link'];
	                });
                $urlarr = array_merge($urlarr,$url[0]);
// 	           }
	        }
	    return $urlarr;
	
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
						'link' => ['.ol-border > li > div:nth-child(2) > h4:nth-child(1) > b:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
							return $u;
						}],
						])->getData(function($item){
							return "http://www.iwjw.com".$item['link'];
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
						'house_title' => ['.detail-title-h1', 'text', '-i', function($title){
							return str_replace(array("\t", "\n", "\r", " "),"", $title);
						}],
														
						'house_price' => ['.g-fence > span:nth-child(1) > i:nth-child(1)', 'text', '', function($price){
							return $price;
						}],
						'cityarea_id' => ['a.mod-detail-nav-a:nth-child(3)', 'text', '', function($cityarea_id){
							return str_replace('租房','',$cityarea_id);
						}],
							
						'cityarea2_id' => ['a.mod-detail-nav-a:nth-child(4)', 'text', '', function($cityarea2_id){
							return str_replace('租房','',$cityarea2_id);
						}],
						'house_totalarea' => ['span.thin:nth-child(3) > i:nth-child(1)', 'text', '', function($house_totalarea){
							return str_replace(' m²', '', $house_totalarea);
						}],
	
						'house_room' => ['span.thin:nth-child(2) > i:nth-child(1)', 'text', '', function($house_room){
// 							preg_match("/(\d+)室/", $house_room, $hr);
							return $house_room;
						}],
	
						'house_hall' => ['span.thin:nth-child(2) > i:nth-child(2)', 'text', '', function($house_hall){
							return $house_hall;
						}],
						'house_toilet' => ['span.thin:nth-child(2) > i:nth-child(3)', 'text', '', function($house_toilet){
							return $house_toilet;
						}],
							
	
						'house_toward' => ['div.item-infos:nth-child(4) > p:nth-child(1)', 'text', '-i',function($house_toward){
							return $house_toward;
						}],
						'house_floor' => ['div.item-infos:nth-child(1) > p:nth-child(1)', 'text', '-i', function($house_floor){
							$temp_topfloor1 = explode("/",$house_floor);
							return str_replace('层', '', $temp_topfloor1[0]);
						}],
						'house_topfloor' => ['div.item-infos:nth-child(1) > p:nth-child(1)', 'text', '-i', function($house_topfloor){
							$temp_topfloor2 = explode("/",$house_topfloor);
							return trimall(str_replace('层', '', $temp_topfloor2[1]));
						}],
	
// 						'owner_name' => ['.mr-t', 'text','',function ($owner_name){
// 							return $owner_name;
// 						}],
	
// 						'owner_phone' => ['.house-broker-tel','text','-a', function ($owner_phone){
// 							return trimall($owner_phone);
// 						}],
						'house_pic_unit' => ['li.img-li > img:nth-child(1)', 'data-src', '', function($house_pic_unit){
							return $house_pic_unit;
						}],
	
						'house_pic_layout' => [],
	
						'house_fitment' => ['div.item-infos:nth-child(5) > p:nth-child(1)','text','-i'],
						'borough_name' => ['a.mod-detail-nav-a:nth-child(5)', 'text', '', function($borough_name){
							return str_replace('租房','',$borough_name);
						}],
	
						'house_type' => [],
	
						'house_built_year' => ['div.infos-mods:nth-child(2) > p:nth-child(3) > span:nth-child(1)','text','-i',function($house_built_year){
				   return str_replace('年', '', $house_built_year);
						}],
	
						'house_relet' => [],
	
						'house_style' => [],
						])->getData(function($data) use($source_url){
							$data['company_name'] = '爱屋吉屋';
							$data['source']= 6;
							$data['source_url'] = $source_url;
							//下架检测
							$data['off_type'] = $this->is_off($source_url);
							return $data;
						});
						foreach((array)$house_info as $key => $value){
							if(isset($house_info[$key]['house_pic_unit'])){
								if(!strstr($house_info[$key]['house_pic_unit'],'http:')){
									$house_pic_unit[] = "http:".$house_info[$key]['house_pic_unit'];
								}else{
									$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
								}
								
							}
						}
						$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
						
						if(strstr($house_pic_unit[0],"layout")){
							$house_info[0]['house_pic_layout'] = $house_pic_unit[0];
							unset($house_pic_unit[0]);
						}
						$html = file_get_contents($source_url);
						preg_match("/朝向：([\x{0000}-\x{ffff}]*?)<\/p>/u", $html, $toward);
						preg_match("/装修：([\x{0000}-\x{ffff}]*?)<\/p>/u", $html, $fit);
						if(strstr($toward[1],"—")){
							$toward[1]="";
						}else{
							$toward[1] = str_replace("</i>","",$toward[1]);
						}
						if(strstr($fit[1],"—")){
							$fit[1]="";
						}else{
							$fit[1]=str_replace("</i>","",$fit[1]);
						}
						$house_info[0]['house_toward'] = $toward[1];
						$house_info[0]['house_fitment'] = $fit[1];
						$house_info[0]['content']=$this->getUrlContent($source_url);
						return $house_info[0];
	}
	
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://nj.58.com/ershoufang/';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['#Order > dt:nth-child(1) > span:nth-child(1)','text'],
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
                    "isOff" => ['a.sellBtnView1:nth-child(3)','text','',function($text) {
                        if (preg_match('/已租出/',$text)){
                            return $off_type = 1;
                        }
                    }],
                    "404" => ['.img-404','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]['isOff']){
                    return $off_type;
                }else {
                	return 2;
                }
            }
            
        }
        return -1;
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100){
        $url = 'http://www.iwjw.com/chuzu/beijing/o1p{$page}/';
        $data = [];
        for($i = 1; $i <= $num; $i++){
        	if($i==1){
        		$data[] = "http://www.iwjw.com/chuzu/beijing/o1/";
        	}else{
        		$data[] = str_replace('{$page}', $i, $url);
        	}
            
        }
        return $data;
    }
}