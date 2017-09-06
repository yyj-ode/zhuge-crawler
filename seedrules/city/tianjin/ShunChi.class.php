<?php namespace tianjin;
/**
	天津顺驰房源
*/
header("Content-type: text/html; charset=utf8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class ShunChi extends \city\PublicClass{
	public $URL = 'http://tj.allfang.com/2hand/m0/';
	public function max_page(){
		$text = file_get_contents($this -> URL);
		$html = gbk_to_utf8($text);
		preg_match('/<td\s*align=\"center\"\s*valign=\"middle\"\s*class=\"syzi0088\">\d+\/(.*?)<\/td>/',$html,$max_page);
		// var_dump($html);die;
		return $max_page[1];

	}
	public function house_page(){
		$urlarr=array();
	    $house_max_page = $this -> max_page();
	    // var_dump($house_max_page);die;
		for($page=1;$page<=$house_max_page;$page++){
			$urlarr[]='http://tj.allfang.com/2hand/m0_q'.$page.'/';
		}
		
    	return $urlarr;
	}
	
	 /* 获取列表页
	*/
	public function house_list($url){
		return \QL\QueryList::run('Request', [
			'target' => $url,
		])->setQuery([
			'link' => ['.fylb-29 a', 'href', '', function($content){
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
		$text = file_get_contents($source_url);
		$html = gbk_to_utf8($text);
		// echo $html;die;
		$house_info['source_url']=$source_url;
		$house_info['company'] = "顺驰不动产";
		preg_match("/<div\s*class=\"side_pa\">(.*?)更新/",$html,$public_time);
		// var_dump($public_time);die;
		$house_info['public_time']=strtotime($public_time[1]);
		preg_match("/<div\s*class=\"ttitle\"><h1><a\s*target=\"_blank\"\s*href=.*>(.*?)<\/a><\/h1>/",$html,$title);
		//标题
		
		// var_dump($public_time);die;
		$house_info['house_title'] = $title[1];
		//房源描述
		preg_match("/<span\s*style=\"font-size:16px;\">(.*?)<\/span>/",$html,$house_desc);
		// var_dump($house_pic_unit);die;
		$house_info['house_desc'] = $house_desc[1];

		// var_dump(house_desc[1]);die;
		if(!$house_desc[1]||$house_desc[1]=='o'){
			preg_match('/<div\s*class=\"content\">\s+.*<div\s*class=\"titlem\">(.*?)<\/div>\s+.*<div\s*class=\"co\">([\x{0000}-\x{ffff}]*?)<\/div>/u', $html,$desc);
			$house_info['house_desc']  = strip_tags($desc[1]).strip_tags($desc[2]);
		}
		
		if(empty($house_desc[1])){
			preg_match("/class=\"titlem\">([^<]+)/",$html,$house_desc_temp);
			$house_info['house_desc'] = $house_desc_temp[1];
		}

		
	    //价格
		preg_match("/<span\s*class=\"num002\">(.*?)万<\/span>/",$html,$house_price);
	    $house_info['house_price'] = $house_price[1];
	    //面积
	    preg_match("/<td\s*height=\"26\"\s*colspan=\"3\"><strong\s*class=\"str\">(.*?)平米/",$html,$house_totalarea);
	    $house_info['house_totalarea'] = $house_totalarea[1];
		//厨房默认值==1
	    $house_info['house_kitchen']= 1;
	    //朝向
	    preg_match("/<li>朝.*向：<span\s*class=\"num006\">(.*?)<\/span><\/li>/",$html,$house_toward);
	    //小区
	    $house_info['borough_name'] = $title[1];
		preg_match_all('/<a\s*target=\"_blank\"\s*href=\".*\">(.*?)<\/a>/', $html,$area_arr);
		$house_info['cityarea2_id'] =$area_arr[1][1];
		$house_info['cityarea_id'] =$area_arr[1][3];\
		//建筑年限
		preg_match("/<td\s*height=\"30\"\s*width=\"173\"><span\s*class=\"cont_0001\">竣工日期：<\/span>(.*?)<\/td>/",$html,$house_built_year);
		$house_info['house_built_year'] = $house_built_year[1];

 		preg_match("/<td\s*height=\"26\"\s*colspan=\"3\"><strong\s*class=\"str\">(.*?)<\/strong><\/td>/",$html,$house_lay);
 		$room = mb_substr($house_lay[1],0,1);
 		$hall = mb_substr($house_lay[1],2,1);
        $house_info['house_room'] = $room;
        $house_info['house_hall'] = $hall;
 		// } 

        //楼层
      
        preg_match("/<span\s*class=\"num006\">第(.*?)层（共(.*?)层）<\/span>/",$html,$house_floor);
        $house_info['house_floor'] = $house_floor[1];
        $house_info['house_topfloor'] = $house_floor[2];

		//类型
		preg_match("/<li>房屋类别：<span\s*class=\"num006\">(.*?)<\/span><\/li>/",$html,$house_type);
	    $house_info['house_type'] = $house_type[1];
	    //房源图片
	    preg_match_all('/<img\s*src=\"(.*?)\".*>/', $html,$house_pic_unit);
	    preg_match_all('/<div\s+class=\"panel\"/', $html,$num);
	    // var_dump($num[0]);die; 
	    $j=0;
	    for($i =0;$i<count($house_pic_unit[1]);$i++){
	    	if($j<count($num[0])){
	    		if(strstr($house_pic_unit[1][$i],'http://img1.allfang.com/upload_files/new')){
	    			$j++;
	    		 	// $house_info['house_pic_unit'] .= $house_pic_unit[1][$i].'|';
	    			// var_dump($house_pic_unit[1][$i]);die;
	    			$arr [] =  $house_pic_unit[1][$i];
	    		}
	    	}	
	    }
	    $house_info['house_pic_unit'] = implode('|', $arr);
	    // var_dump($house_info['house_pic_unit']);die;

	    //联系人
	    preg_match("/<span\s*class=\"p\">&nbsp;&nbsp;(.*?)<\/span>/",$html,$name);
	    $house_info['owner_name'] = $name[1];
	    
	    //联系电话
	    preg_match("/<span\s*class=\"nu\">(.*?)<\/span>/u",$html,$phone);
	    
	    $house_info['owner_phone'] = $phone[1];
	
	    return $house_info;
	}


    /**
     * 获取最新的房源种子
     * @author
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 100; $i++){
            $resultData[] = 'http://tj.allfang.com/2hand/m0_q'.$i.'/';
        }
        return $resultData;
    }
	
}

?>