<?php namespace beijing;
/**
 * @description 北京丽姿行整租房源信息抓取
 * @classname 北京丽姿行
 */

Class LandzestateRent extends \city\PublicClass
{
	/*
	* 抓取
	*/
	public function house_page(){

		$maxPage=81;
		$urlarr = [];
		for($page=1; $page<=$maxPage; $page++){
			$urlarr[] = "http://www.landzestate.com/bj/rent/p/".$page."/";

		}
		return $urlarr;
	}
	
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		$list = file_get_contents($url);
		// 	        echo $list;die;
		preg_match_all("/houseId\/([\x{0000}-\x{ffff}]*?)\"/u", $list, $hrefs);
		$hrefs = array_unique($hrefs[1]);
		$k = 0;
		//dump($hrefs);die;
		foreach($hrefs as $h){
		    $house_info[$k] = "http://www.landzestate.com/bj/rent/houseId/".$h;
		    $k++;
		}
		//dump($this->house_info);die;
		return $house_info;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		
		$house_info['source'] = 8;
		$house_info['company_name']="丽兹行";
		$detail = file_get_contents($source_url);
        //下架检测-暂未找到下架表示
        $house_info['off_type'] = $this->is_off($source_url);
		//标题
		preg_match("/\<h4([\x{0000}-\x{ffff}]*?)h4\>/u",$detail,$title);
		$house_info['house_title'] = trimall(strip_tags($title['0']));
		//房源编号
		// 	            var_dump($data[1]);
		preg_match_all("/\<span\s*class=\"ac666\s*lh30\s*ffw\">([\x{0000}-\x{ffff}]*?)span\>/u",$detail,$data);
		preg_match("/\d*室/",strip_tags($data[0][0]),$room1);
		preg_match("/\d*厅/",strip_tags($data[0][0]),$room2);
		$layout1 = str_replace("室","",$room1[0]);
		$layout2 = str_replace("厅","",$room2[0]);
		// 	            var_dump($layout);die;
		//室
		$house_info['house_room'] = $layout1;
		//厅
		$house_info['house_hall'] = $layout2;
		//面积
		$house_info['house_totalarea'] = str_replace("㎡","",strip_tags($data[0][2]));
		//经纪人名字
		preg_match("/\<p\s*style=\"color:#000\">([\x{0000}-\x{ffff}]*?)<\/p>/u",$detail,$name);
		$house_info['owner_name'] = $name['1'];
		//经纪人电话
		preg_match("/ffw\sac3f135d\spdl20\">([\x{0000}-\x{ffff}]*?)<\/div>/u",$detail,$mobile);
		$house_info['owner_phone'] = trimall($mobile[1]);
		//房屋价格
		preg_match("/<span\s*class=\"acff0000 lh30 fz24 fwb ffw\">([\x{0000}-\x{ffff}]*?)元/u",$detail,$price);
		$house_info['house_price'] = $price['1'];
		//朝向
		$house_info['house_toward'] = strip_tags($data[0][3]);
		preg_match("/(低|中|高)层/u",strip_tags($data[0][1]),$flo1);
		preg_match("/(\d+)层/",strip_tags($data[0][1]),$flo2);
		//所在楼层
		$house_info['house_floor'] = $flo1[1];
		//总楼层
		$house_info['house_topfloor'] = $flo2[1];
		//装修
		foreach($data[0] as $v){
			if(preg_match("/装/",$v,$tt)){
				$house_info['house_fitment'] = strip_tags($v);
			}
		}
		if(empty($house_info['house_fitment'])){
			$house_info['house_fitment'] ='';
		}		
		
		$city=strip_tags(Array_pop($data[0]));
		$arrcity=explode('，',$city);
		//小区名
		preg_match('/html\">([\x{0000}-\x{ffff}]*?)<\/a><\/h4>/u',$detail,$borough);
		$house_info['borough_name'] = trimall(strip_tags($borough[1]));
		//城区
		$house_info['cityarea_id'] = str_replace("区","",$arrcity[0]);
		//商圈
		$house_info['cityarea2_id'] = $arrcity[1];
		//房源描述
		//dump($detail);die;
		preg_match("/m20\sffw\sfz12\slh24\">([\x{0000}-\x{ffff}]*?)<\/div>/u",$detail,$desc);
		$house_info['house_desc'] = trimall(strip_tags($desc[1]));
		//房源图片
		
		preg_match_all("/<img\s*src=\"http:\/\/119\.254\.70\.187([\x{0000}-\x{ffff}]*?)\"/u", $detail, $picture);
		foreach($picture[1] as $k=>$v){
			$pic[]="http://119.254.70.187".$v;
		}
		$picture = implode("|",$pic);
		$house_info['house_pic_unit'] = $picture;
		//户型图
		preg_match("/<div\s*class=\"hu\">([\x{0000}-\x{ffff}]*?)<\/div>/u",$detail,$pic2);
		preg_match("/http(\S*?)jpg/i", $pic2[0], $picture2);
		$house_info['house_pic_layout'] = $picture2[0];
		$house_info['created'] = time();
		$house_info['updated'] = time();
		$house_info['house_relete'] = '';
		$house_info['house_style'] = '';
		if($house_info['house_room']){
			$temp_room = $house_info['house_room']."室";
		}
		if($house_info['house_hall']){
			$temp_hall = $house_info['house_hall']."厅";
		}
		
		if(empty($house_info['house_title'])){
			$house_info['house_title'] = $house_info['borough_name']." ".$temp_room.$temp_hall;
		}
		$house_info['source_url']=$source_url;
		return $house_info;
	}
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://www.landzestate.com/bj/rent/';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['span.fwb','text'],
	    ]);
	    return $totalNum;
	    // 	    return 0;
	}
	//下架判断
	public function is_off($url,$html=''){
	    return 2;//TODO 未找到下架房源
// 	    if(!empty($url)){
// 	        if(empty($html)){
// 	            $html = $this->getUrlContent($url);
// 	        }
// 	        //抓取下架标识
// 	        $off_type = 1;
// 	        $newurl = get_jump_url($url);
// 	        $oldurl = str_replace('shtml','html',$url);
// 	        if($newurl == $oldurl){
// 	            $Tag = \QL\QueryList::Query($html,[
// 	                "isOff" => ['.contenttop_err','text','',function ($item){
// 	                    return preg_match("/存在/",$item);
// 	                }],
// 	                ])->getData(function($item){
// 	                    return $item;
// 	                });
// 	                foreach ($Tag[0] as $key=>$value) {
// 	                    if($key == "isOff" && $value == 1){
// 	                        return 1;
// 	                    }else{
// 	                        return 2;
// 	                    }
// 	                }
// 	                return 2;
// 	        }
// 	    }
	}
	/**
	 * 获取最新的房源种子
	* @param type $num 条数
	* @return type
	*/
	public function callNewData($num = 65){
		$url = 'http://www.landzestate.com/bj/rent/n/5/sort/7/p/{$page}';
		$data = [];
		for($i = 1; $i <= $num; $i++){
			$data[] = str_replace('{$page}', $i, $url);
		}
		return $data;
	}
}
?>