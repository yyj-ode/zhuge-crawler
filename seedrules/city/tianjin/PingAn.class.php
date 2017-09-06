<?php namespace tianjin;
/**
 * @description 平安好房二手房抓取规则
 * 
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class PingAn extends \city\PublicClass{
	public $URL = 'http://esf.pinganfang.com/tj/list/pg1-tb2';
	//获取最大页数
	public function max_page(){
		$html = file_get_contents($this -> URL);
		preg_match('/<span>共(\d*)页<\/span>/',$html,$max_page);
		// var_dump($max_page[1]);die;
		return $max_page[1];

	}
	public function house_page(){
		$urlarr=array();
	    $house_max_page = $this -> max_page();
		for($page=1;$page<=$house_max_page;$page++){
			$urlarr[]='http://esf.pinganfang.com/tj/list/pg'.$page.'-tb2';
		}
    	return $urlarr;
	}
	
	
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		return \QL\QueryList::run('Request', [
			'target' => $url,
		])->setQuery([
			'link' => ['.lp-info-tit h2 a', 'href', '', function($content){
				return $content;
			}],
		])->getData(function($item){
			return $item['link'];
		});
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		$html = file_get_contents($source_url);
		$house_info['source_url']=$source_url;
		$house_info['company'] = "平安好房";
		// preg_match("/<div\s*class=\"fr\">发布：(.*?)<\/div>/",$html,$public_time);
		// $house_info['public_time']=strtotime($public_time[1]);
		preg_match("/<div\s*class=\"lp-name\s*clearfix\"><h1>(.*?)<\/h1>/",$html,$title);
		//标题
		
		// var_dump($public_time);die;
		$house_info['house_title'] = $title[1];
		
		//房源描述
		preg_match_all("/<li\s*class=\"album-list\s*j_albumList\"\s*data-index=\"[0-9]*\"><img\s*src=\"(.*?)\"><\/li>/",$html,$house_pic_unit);
		// var_dump($house_pic_unit);die;
		$house_info['house_pic_unit'] = '';
		// for($i =0;$i<count($house_pic_unit[1]);$i++){
		// 	$house_info['house_pic_unit'] .= $house_pic_unit[1][$i].'|';
		// }
		$house_info['house_pic_unit'] = implode('|', $house_pic_unit[1]);
		
		
	    //价格
		preg_match("/<div\s*class=\"total-price\"><strong>(.*?)<\/strong>万<\/div>/u",$html,$house_price);
		
	    $house_info['house_price'] = $house_price[1];
	   
	    //面积
	    preg_match("/<div\s*class=\"esf-key\"><span\s*class=\"cut-off\">(.*?)室(.*?)厅(.*?)卫<\/span><span\s*class=\"cut-off\">(.*?)平<\/span><span>(.*?)<\/span><\/div>/",$html,$house);
	   // $house_totalarea = str_replace('平方米', '', $public_time[1][5]);
	    $house_info['house_totalarea'] = $house[4];
	    $house_info['house_room'] = $house[1];
        $house_info['house_hall'] = $house[2];
        $house_info['house_toilet'] = $house[3];

		//厨房默认值==1
	    $house_info['house_kitchen']= 1;
	    //朝向
	    $house_info['house_toward'] = $house[5];
	    //小区
	    //$house_borough_name = str_replace('', replace, subject)
	    preg_match("/&gt;&nbsp;<a.*>(.*?)二手房<\/a>&nbsp;&gt;&nbsp;<a.*>(.*?)二手房<\/a>&nbsp;&gt;&nbsp;<span>(.*?)二手房<\/span>/",$html,$area);
	    //城区商圈
        $house_info['borough_name'] = $area[3];
		$house_info['cityarea2_id'] =$area[2];//trimall($area_arr[1]);
		$house_info['cityarea_id'] =$area[1];//trimall($area_arr[0]);
		//装修
		preg_match("/<li\s*class=\"esf-oinfo-l\s*clearfix\"><span\s*class=\"esf-oinfo-title\">.*<span>(.*?)装修<\/span><\/li>/",$html,$house_fitment);
		// var_dump($house_fitment[1]);die;
		$house_info['house_fitment'] = trim($house_fitment[1],'&nbsp;');
		//建筑年代
		preg_match("/<li\s*class=\"esf-oinfo-l\s*clearfix\"><span\s*class=\"esf-oinfo-title\">.*<span>(.*?)年建<\/span><\/li>/",$html,$house_built_year);
		$house_info['house_built_year'] = $house_built_year[1];
		//房源描述
		preg_match('/<div\s*class=\"lp-des-txt\">([\x{0000}-\x{ffff}]+?)<\/div>/u', $html,$desc);
		$house_info['house_desc'] = $desc[1];
         // var_dump($desc);die;
     
        //楼层
      
        preg_match("/<li\s*class=\"esf-oinfo-l\s*clearfix\"><span\s*class=\"esf-oinfo-title\">.*<span>(.*?)层（共\s*(.*?)\s*层）<\/span><\/li>/",$html,$house_floor);
        $house_info['house_floor'] = $house_floor[1];
        $house_info['house_topfloor'] = $house_floor[2];
        
		//类型
		preg_match("/<li\s*class=\"esf-oinfo-r\s*clearfix\"><span\s*class=\"esf-oinfo-title\">房屋类型：<\/span><span>(.*?)<\/span><\/li>/",$html,$house_type);
	    $house_info['house_type'] = $house_type[1];
	    
	    //联系人
	    preg_match("/<h3>(.*?)<em><\/em><\/h3>/",$html,$name);
	    $house_info['owner_name'] = $name[1];
	    //联系电话
	    preg_match("/<p\s*class=\"tel-num\"><strong>(.*?) - (.*?) - (.*?)<\/strong>&nbsp;转&nbsp;<strong>(.*?)<\/strong><\/p>/",$html,$phone);
	    
	    $house_info['service_phone'] = $phone[1].$phone[2].$phone[3].','.$phone[4];
		if ($house_info['service_phone'] == ',') {
			preg_match("/<p\s*class=\"tel-num\"><strong>(.*?)<\/strong><\/p>/",$html,$phone);
			$house_info['service_phone'] = '';
			$house_info['owner_phone'] = str_replace('-', '', $phone[1]);
		}
	    return $house_info;
	}
	  /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 100; $i++){
            $resultData[] = 'http://esf.pinganfang.com/tj/list/pg'.$i.'-tb2';
        }
//        dumpp($resultData);die;
        writeLog( 'Pa_'.__FUNCTION__, $resultData, $this -> _log);
        return $resultData;
    }
	
}

?>