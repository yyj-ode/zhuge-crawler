<?php namespace haerbin;
/**
 * @description 北京58同城业主个人整租抓取规则
 * @classname 北京58同城业主个人
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class YiFang extends \city\PublicClass{
	// 抓取page最大页数
	public function maxpage(){
		$url = "http://www.0451fang.cn/sale/";
		$html = file_get_contents($url);
		preg_match('/<b\s*class=\"red\">(.*?)<\/b>/', $html,$maxpage);
		// var_dump($maxpage);die;
		return ceil($maxpage[1]/12);

	}
	public function house_page(){
		 $maxpage = $this -> maxpage();
		 $urlarr=array();
		for($page=1;$page<=$maxpage;$page++){
			$urlarr[]='http://www.0451fang.cn/sale/?page='.$page;
		}
		// var_dump($maxpage);die;
    	return $urlarr;
	}
	
	
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		return \QL\QueryList::run('Request', [
			'target' => $url,
		])->setQuery([
			'link' => ['.hlist h3 a', 'href', '', function($content){
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
		$house_info['company'] = "哈尔滨易房房地产经纪有限公司";
		preg_match("/<div\s*class=\"fr\">发布：(.*?)<\/div>/",$html,$public_time);
		$house_info['public_time']=strtotime($public_time[1]);
		preg_match("/<div\s*class=\"dtl_fl\">\s*.*<h1>([\x{0000}-\x{ffff}]+?)<\/h1>/u",$html,$title);
		//标题
		
		// var_dump($public_time);die;
		$house_info['house_title'] = $title[1];
		//房源描述
		preg_match_all("/<img\s*data-src=\"(.*?)\"\s*[\x{0000}-\x{ffff}]+?>/u",$html,$house_pic_unit);
		// var_dump($house_pic_unit);die;
		$house_info['house_pic_unit'] = '';
		for($i =0;$i<count($house_pic_unit[1]);$i++){
			$house_info['house_pic_unit'] .= $house_pic_unit[1][$i].'|';
		}
		
		
	    //价格
		preg_match("/<span\s*class=\"red\"><b>([\x{0000}-\x{ffff}]+?)<\/b>万<\/span>/u",$html,$house_price);
		//$house_price = str_replace('万','',$public_time[1][1]);
		
	    $house_info['house_price'] = $house_price[1];
	    //面积
	    preg_match("/<td\s*width=\"\d*\">面积：(.*?)平米<\/td>/",$html,$house_totalarea);
	   // $house_totalarea = str_replace('平方米', '', $public_time[1][5]);
	    $house_info['house_totalarea'] = $house_totalarea[1];
		//厨房默认值==1
	    $house_info['house_kitchen']= 1;
	    //朝向
	    preg_match("/<td>朝向：(.*?)&nbsp;&nbsp;<\/td>/",$html,$house_toward);
	    //$house_toward = str_replace('向', '', $public_time[1][9]);
	    $house_info['house_toward'] = $house_toward[1];
	    //小区
	    //$house_borough_name = str_replace('', replace, subject)
	    preg_match("/<td\s*colspan=\"2\">小区：<a\s*href=\".*\"\s*title=\".*\"\s*class=\"bal\">(.*?)<\/a><\/td>/",$html,$borough_name);
	   
	    $house_info['borough_name'] = $borough_name[1];
	    // $house_info['borough_id'] = '';
	    //城区商圈
        
		//装修
		preg_match("/<td>装修：(.*?)<\/td>/",$html,$house_fitment);
		// var_dump($house_fitment[1]);die;
		$house_info['house_fitment'] = trim($house_fitment[1],'&nbsp;');
		//建筑年代
		//$house_built_year = str_replace('年', '',$public_time[1][12]);
		preg_match("/<td>房龄：(.*?)年&nbsp;<\/td>/",$html,$house_built_year);
		$house_info['house_built_year'] = $house_built_year[1];

        //户型
        preg_match("/<td>户型：(.*?)室(.*?)厅(.*?)卫<\/td>/",$html,$house_lay);
       // preg_match('/(\d+)[居|室]/u',$public_time[1][7],$room);
        //若没有阿拉伯数字匹配，考虑汉字！
        $house_info['house_room'] = $house_lay[1];
        $house_info['house_hall'] = $house_lay[2];
        $house_info['house_toilet'] = $house_lay[3];
        if(empty($house_lay)){
        	preg_match("/<td>户型：(.*?)室(.*?)厅<\/td>/",$html,$house_lay);
        	$house_info['house_room'] = $house_lay[1];
        	$house_info['house_hall'] = $house_lay[2];
        	$house_info['house_toilet'] = '';
        }

        //楼层
      
        preg_match("/<td>楼层：第(.*?)层\/总(.*?)层<\/td>/",$html,$house_floor);
        $house_info['house_floor'] = $house_floor[1];
        $house_info['house_topfloor'] = $house_floor[2];
		//类型
		preg_match("/<td>类型：<a\s*href=\".*\">(.*?)<\/a><\/td>/",$html,$house_type);
	    $house_info['house_type'] = $house_type[1];
	    

	    //联系人
	    // preg_match("/<div\s*class=\"detail_b_right_name\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$name);
	    // $house_info['owner_name'] = trimall(strip_tags($name[1]));
	    
	    //联系电话
	    preg_match("/<div\s*class=\"dtl_mobile\">\r\n<b>([\x{0000}-\x{ffff}]+?)<\/b>/u",$html,$phone);
	    
	    $house_info['owner_phone'] = $phone[1];
	
	    return $house_info;
	}
	
}

?>