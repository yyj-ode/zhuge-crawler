<?php namespace beijing;
/**
 * @description 北京我爱我家 合租房抓取规则
 * @classname 北京我爱我家
 */


Class WiwjHezu extends \city\PublicClass
{
	Public function house_page(){
		$dis = 9;//共10个城区
		$PRE_URL = 'http://bj.5i5j.com/rent/';
		$urlarr =array();
		for($index = 0;$index<=$dis;$index++){
			//获取搜索条件
			$this->get_condition($index,$PRE_URL);
			$num = count($this->url_list);
			for($n=0;$n<$num;$n++){
				//获取当前搜索条件的最大页
//                echo $this->url_list[$n]."\r\n";
				$maxPage = $this->get_maxPage($this->url_list[$n]);
				for ($page=1;$page<$maxPage;$page++) {
					$urlarr[] = $this->url_list[$n] . "n" . $page;
				}
			}
		}
		return $urlarr;
	}
	/*
	 * 获取列表页
	 */
	public function house_list($url){
		$html = $this->getUrlContent($url);
		preg_match_all('/a\shref=\"(\/rent\/\d+)\"/u',$html,$ids);
        $house_info = array();
		foreach(array_unique($ids[1]) as $v){
			$house_info[] = 'http://bj.5i5j.com'.$v;
		}
	    return $house_info;
	}
	
	/*
	 * 获取详情页
	 *
	 *  */
	public function house_detail($url){
	    //$source_url = "http://bj.5i5j.com/rent/122122815";
	    $ch = curl_init(); 
		$timeout = 5; 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->opts);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
		$contents = curl_exec($ch);
        //下架检测
        $house_info['off_type'] = $this->is_off($url,$contents);
		//部分抓到source_url会有整租房源
		if(preg_match('/>合租</u',$contents)){
		    //title
		    preg_match('/house\-tit\">([\x{0000}-\x{ffff}]+?)<div/u',$contents,$title);
		    
		    $house_info['house_title'] = trimall(strip_tags($title[1]));
		    //价格
		    preg_match('/font\-price\">(\d+\.?\d*)/u',$contents,$price);
		    $house_info['house_price'] = $price[1];
		    
		    preg_match("/<ul\s*class=\"house\-info\">[\x{0000}-\x{ffff}]+?<\/ul>/u", $contents, $ul);
		    $info = trimall(strip_tags($ul[0]));
		    //总面积
		    preg_match("/面积：(\d+)平米/u", $info, $totalarea);
		    $house_info['house_room_totalarea'] = $totalarea[1];
// 		    var_dump($totalarea[1]);die;
		    //室厅卫
		    preg_match('/(\d+)室/u',$info,$room);
		    $house_info['house_room']=$room[1];
		    
		    preg_match('/(\d+)厅/u',$info,$hall);
		    $house_info['house_hall']=$hall[1];
		    
		    preg_match('/(\d+)卫/u',$info,$toilet);
		    $house_info['house_toilet'] = $toilet[1];
		    
		    preg_match("/(精装|简装|毛坯|豪华)/u", $info, $fitment);
		    $house_info['house_fitment'] = $fitment[1];
		    //朝向
		    preg_match('/(东北|西北|东南|西南|东西|南北)/u',$info,$toward);
		    if(empty($toward[1])){
		        preg_match('/(东|西|南|北)/u',$info,$toward);
		    }
		    $house_info['house_toward'] = $toward[1];
		    //楼层
		    preg_match("/(中|上|下)部/u", $info, $floor);
		    $t = array("上"=>"高", "中"=>"中", "下"=>"低");
		    $house_info['house_floor']=$t[$floor[1]];
		    //总楼层
		    preg_match("/(\d+)层/u", $info, $topfloor);
		    $house_info['house_topfloor'] = $topfloor[1];
		    //小区
		    preg_match('/小区：([\x{0000}-\x{ffff}]*?)<\/li>/u',$contents,$bor);
		    $house_info['borough_name']=trimall(strip_tags($bor[1]));
		    //城区商圈
		    preg_match('/id=\"location\">([\x{0000}-\x{ffff}]*?)id=\"room\">/u',$contents,$city);
		    preg_match('/\"selected\"([\x{0000}-\x{ffff}]*?)<\/option>/u',$city[1],$city1);
		    $cityarea_id = str_replace('>',"",trimall($city1[1]));
		    
		    preg_match('/dz\_i\">([\x{0000}-\x{ffff}]*?)<\/option>/u',$city[1],$city2);
		    $cityarea2_id = trimall(strip_tags($city2[1]));
		    
		    $house_info['cityarea2_id'] =$cityarea2_id;
		    $house_info['cityarea_id'] =$cityarea_id;
		    //配置
		    preg_match("/<div\s*class=\"match\">[\x{0000}-\x{ffff}]+?<\/div>/u", $contents, $match);
		    $match = str_replace('<b>', '#', $match[0]);
		    $match = strip_tags($match);
		    $match = explode('：', $match);
		    $house_info['house_configroom'] = trimall($match[1]);
		    //<p class="mr-t">
		    preg_match("/<p\s*class=\"mr\-t\">[\x{0000}-\x{ffff}]+?</u", $contents, $owner);
		    $house_info['owner_name'] = strip_tags($owner[0]);
		    preg_match("/<p\s*class=\"house\-broker\-tel\">(\d{11})</", $contents, $phone);
		    $house_info['owner_phone'] = $phone[1];
		    
		    preg_match("/auto\-loop[\x{0000}-\x{ffff}]+?<\/ul>/u", $contents, $pic);
		    preg_match_all('/data\-src=\"(\S+?)\"/', $pic[0], $p);
		    unset($pic);
		    $pic = array();
		    foreach($p[1] as $k=>$v){
                $pic[] = $v;
		    }
		    $pic = array_unique($pic);
		    $house_info['house_pic_unit'] = implode("|", $pic);
		    
		    preg_match('/<div\s*class=\"new\-broker\-3\"[\x{0000}-\x{ffff}]*?<\/dd>/u',$contents,$desc);
		    $desc = trimall(strip_tags($desc[0]));
		    $house_info['house_desc'] = str_replace(array("&nbsp;"),"",$desc);
		    //房间配置
		    preg_match_all('/<em\s*class=\"match\-icon\d\"><\/em><b>([\x{0000}-\x{ffff}]*?)<\/b>/u',$contents,$config);
		    $config = implode('#',$config[1]);
		    $house_info['house_configroom'] = $config;
		     
		    $house_info['house_kitchen'] = '';
		    $house_info['house_type'] = '';
		    $house_info['house_style'] = '';
		    $house_info['sex'] = '';
		    $house_info['into_house'] = '';
		    $house_info['pay_method'] = '';
		    $house_info['pay_type'] = '';
		    $house_info['tag'] = '';
		    $house_info['comment'] = '';
		    $house_info['deposit'] = '';
		    $house_info['house_configpub'] = '';
		    $house_info['is_ture'] = '';
		    $house_info['friend_info'] = '';
		    $house_info['created'] = time();
		    $house_info['updated'] = time();
		     
		    $house_info['house_relet'] = '';
		    $house_info['wap_url'] = '';
		    $house_info['pub_time'] = '';
		    $house_info['chain_url'] = '';
		    $house_info['is_contrast'] = 2;
		    $house_info['is_fill'] = 2;
		     
		    $house_info = array_merge($house_info);
		    usleep(5000);
		}else{
		    unset($house_info);
		}
		//dump($source_url);
		
		return $house_info;
	}
	    
	//统计官网数据
	public function house_count(){
	    $num = [];
        $url = 'http://www.ziroom.com/z/nl/z1.html';
	    $html = get_curl_post_data($url, []);
	    preg_match('/id=\"page\">([\x{0000}-\x{ffff}]*?)<\/div>/u',$html,$div);
	    preg_match('/共(\d+)页/',$div[1],$total);
	    $total = trimall(strip_tags($total[1]));
        $num['beijing-ZiroomRent'] = $total * 10;
        
        $url = 'http://www.ziroom.com/z/nl/z2.html';
        $html = get_curl_post_data($url, []);
        preg_match('/id=\"page\">([\x{0000}-\x{ffff}]*?)<\/div>/u',$html,$div);
        preg_match('/共(\d+)页/',$div[1],$total);
        $total = trimall(strip_tags($total[1]));
        $num['beijing-ZiroomHezu'] = $total * 20;
	    
	    $List = array(
	        'beijing-Wiwj'=>array(
	            'url'=>'http://bj.5i5j.com/exchange/',
	            'query'=>'.font-houseNum'
	        ),
	        'beijing-WiwjHezu'=>array(
	            'url'=>'http://bj.5i5j.com/rent/w2',
	            'query'=>'.font-houseNum'
	        ),
	        'beijing-Wiwjrent'=>array(
	            'url'=>'http://bj.5i5j.com/rent/w1',
	            'query'=>'.font-houseNum'
	        ),
	        'beijing-Dingdinghezu'=>array(
	            'url'=>'http://bj.zufangzi.com/area/0-20000000000/',
	            'query'=>'span.pull-right > span:nth-child(1)'
	        ),
	        'beijing-DingdingRent'=>array(
	            'url'=>'http://bj.zufangzi.com/area/0-10000000000/',
	            'query'=>'span.pull-right > span:nth-child(1)'
	        ),
	        'beijing-Fang'=>array(
	        ),
	        'beijing-FangHezu'=>array(
	        ),
	        'beijing-FangRent'=>array(
	        ),
	        'beijing-FangzhuHezu'=>array(
	            'url'=>'http://bj.fangzhur.com/hezu/',
	            'query'=>'.result > span:nth-child(1)'
	        ),
	        'beijing-FangzhuRent'=>array(
	            'url'=>'http://bj.fangzhur.com/rent/',
	            'query'=>'.result > span:nth-child(1)'
	        ),
	        'beijing-Five8PersonalRent'=>array(
	        ),
	        'beijing-Iwjw'=>array(
	            'url'=>'http://www.iwjw.com/sale/beijing/',
	            'query'=>'#Order > dt:nth-child(1) > span:nth-child(1)'
	        ),
	        'beijing-Kufang'=>array(
	            'url'=>'http://beijing.koofang.com/sale/',
	            'query'=>'.tongji > span:nth-child(2)'
	        ),
	        'beijing-KufangHezu'=>array(
	            'url'=>'http://beijing.koofang.com/rent/c1/t8/',
	            'query'=>'.tongji > span:nth-child(2)'
	        ),
	        'beijing-Landzestate'=>array(
	            'url'=>'http://www.landzestate.com/bj/xiaoshou',
	            'query'=>'span.fwb'
	        ),
	        'beijing-LandzestateRent'=>array(
	            'url'=>'http://www.landzestate.com/bj/rent',
	            'query'=>'span.fwb'
	        ),
	        'beijing-Lianjia'=>array(
	            'url'=>'http://bj.lianjia.com/ershoufang/',
	            'query'=>'.secondcon > ul:nth-child(1) > li:nth-child(3) > span:nth-child(2) > strong:nth-child(1) > a:nth-child(1)'
	        ),
	        'beijing-LianjiaRent'=>array(
	            'url'=>'http://bj.lianjia.com/zufang/',
	            'query'=>'.list-head > h2:nth-child(1) > span:nth-child(1)'
	        ),
	        'beijing-Mai'=>array(
	            'url'=>'http://maitian.cn/esfall',
	            'query'=>'.screening > p:nth-child(3) > span:nth-child(1)'
	        ),
	        'beijing-MaiRent'=>array(
	            'url'=>'http://www.maitian.cn/zfall',
	            'query'=>'.screening > p:nth-child(3) > span:nth-child(1)'
	        ),
	        'beijing-Qfang'=>array(
	            'url'=>'http://beijing.qfang.com/sale',
	            'query'=>'.dib'
	        ),
	        'beijing-QfangHezu'=>array(
	            'url'=>'http://beijing.qfang.com/rent/h2',
	            'query'=>'.dib'
	        ),
	        'beijing-QfangRent'=>array(
	            'url'=>'http://beijing.qfang.com/rent/h1',
	            'query'=>'.dib'
	        ),
	        'beijing-Zhongyuan'=>array(
	            'url'=>'http://bj.centanet.com/ershoufang/',
	            'query'=>'.pagerTxt > span:nth-child(1) > span:nth-child(1) > em:nth-child(1)'
	        ),
	        'beijing-ZhongyuanRent'=>array(
	            'url'=>'http://bj.centanet.com/zufang/',
	            'query'=>'.pagerTxt > span:nth-child(1) > span:nth-child(1) > em:nth-child(1)'
	        ),
	    );
	    foreach ($List as $key=>$value){
	        if(empty($value)){
	            $num[$key] = '无';
	            continue;
	        }
	        $num[$key] = $this->queryList($value['url'], [
	            'total' => [$value['query'],'text'],
	        ])[0]['total'];
	    }
	    return $num;
	    // 	    return 0;
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
	            $this->url_list[] = $PRE_URL.$dis[1][$index+1].$ROOM.$AREA."w2";
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






