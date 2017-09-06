<?php namespace beijing;
/**
 * @description 北京58同城业主个人整租抓取规则
 * @classname 北京58同城业主个人
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class Five8PersonalRent extends \city\PublicClass{
	public function house_page(){

	    $dis = array(
			'chaoyang',
			'haidian',
			'dongcheng',
			'xicheng',
			'chongwen',
			'xuanwu',
			'fengtai',
			'tongzhouqu',
			'shijingshan',
			'fangshan',
			'changping',
			'daxing',
			'shunyi',
			'miyun',
			'huairou',
			'yanqing',
			'pinggu',
			'mentougou',
			'bjyanjiao',
			'beijingzhoubian'
		);
	    $page_list = array(
	        '100',
	        '100',
	        '50',
	        '50',
	        '50',
	        '50',
	        '100',
	        '100',
	        '100',
	        '100',
	        '100',
	        '100',
	        '50',
	        '50',
	        '50',
	        '50',
	        '50',
	        '50',
	        '100',
	        '80'
	    );
		$urlarr=array();
	    //0-20
		for($index=0;$index<count($dis);$index++){
			for($page=1;$page<=$page_list[$index];$page++){
				$urlarr[]='http://m.58.com/'.$dis[$index].'/zufang/0/pn'.$page.'?refrom=wap';
			}
		}
    	return $urlarr;
	}
	
	
	/**
	 * 获取最新的房源种子
	 * @param type $num 条数
	 * @return type
	 */
	public function callNewData($num = 800){
	    $dis = array(
			'chaoyang',
			'haidian',
			'dongcheng',
			'xicheng',
			'chongwen',
			'xuanwu',
			'fengtai',
			'tongzhouqu',
			'shijingshan',
			'fangshan',
			'changping',
			'daxing',
			'shunyi',
			'miyun',
			'huairou',
			'yanqing',
			'pinggu',
			'mentougou',
			'bjyanjiao',
			'beijingzhoubian'
		);
	    $page_list = array(
	        '10',
	        '10',
	        '5',
	        '5',
	        '5',
	        '5',
	        '10',
	        '10',
	        '10',
	        '10',
	        '10',
	        '10',
	        '5',
	        '5',
	        '5',
	        '5',
	        '5',
	        '5',
	        '10',
	        '7'
	    );
		$urlarr=array();
	    //0-20
		for($index=0;$index<count($dis);$index++){
			for($page=1;$page<=$page_list[$index];$page++){
				$urlarr[]='http://m.58.com/'.$dis[$index].'/zufang/0/pn'.$page.'?refrom=wap';
			}
		}
    	return $urlarr;
	}
	
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html=file_get_contents($url);
// 		echo $html;die;
		preg_match("/<ul\s*class=\"infoList infoL([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $out);
		preg_match_all("/infoid=\'(\w*?)\'/", $out[1], $ids);
		//原始逻辑中含有在pc端判断是否为经纪人房源以及是否为当前城市房源
		$house_info = [];
		foreach ($ids[1] as $k=>$v){
			$house_info[] = "http://m.58.com/bj/zufang/".$v."x.shtml";
			//$house_info[] = "http://bj.58.com/zufang/".$v."x.shtml";
		}
		return $house_info;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
//  		$source_url = 'http://m.58.com/bj/zufang/26430445534275x.shtml';
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html = file_get_contents($source_url);
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url,$html);

// 		echo $html;die;
		$house_info['source_url']=$source_url;
		$house_info['source'] = 10;
		$house_info['source_owner'] = 5;
		$house_info['is_contrast'] = 2;
		$house_info['company'] = "58同城个人房源";
		preg_match("/<li>发布时间\:([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$public_time);
		$house_info['public_time']=strtotime(trimall($public_time[1]));
		preg_match("/class\=\"meta-tit\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$title);
		//标题
		$title = strip_tags($title[1]);
		$title = str_replace(array("\t","\n", "\r", " "), "", $title);
		$title = SBC_DBC($title);
		$house_info['house_title'] = $title;
		//房源描述
		preg_match("/元\/月 \,([\x{0000}-\x{ffff}]+?)\"\/>/u",$html,$desc);
		$house_info['house_desc'] = trimall($desc[1]);
		
	    //价格
		preg_match("/(\d+\.?\d*)元\/月/", $html, $price);
	    $house_info['house_price'] = $price[1];
	    //面积
	    preg_match("/(\d+\.?\d*)㎡/", $html, $totalarea);
	    $house_info['house_totalarea'] = $totalarea[1];
		//厨房默认值==1
	    $house_info['house_kitchen']= 1;
	    //小区
	    preg_match("/小区\:([\x{0000}-\x{ffff}]+?)</u",$html,$borough);
	    $house_info['borough_name'] = trimall(strip_tags($borough[1]));
	    $house_info['borough_id'] = '';
	    //城区商圈
        
	    preg_match("/位置([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$area);
		// dump($area);die;
		$area_arr = explode('-',strip_tags($area[1]));
		$house_info['cityarea2_id'] =trimall($area_arr[1]);
		$house_info['cityarea_id'] =trimall($area_arr[0]);

        //户型
//        preg_match("/户型([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$layout);
        preg_match('/(\d+)[居|室]/u',$html,$room);
        //若没有阿拉伯数字匹配，考虑汉字！
        preg_match('/(\d+)厅/u',$html,$hall);
        preg_match('/(\d+)卫/u',$html,$toilet);
        $house_info['house_room'] = $room[1];
        $house_info['house_hall'] = $hall[1];
        $house_info['house_toilet'] = $toilet[1];

        //楼层
        preg_match("/houseInfo-meta([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$Pre_floors);
        preg_match("/楼层\:([\x{0000}-\x{ffff}]+?)层/u",$Pre_floors[1],$floors);
        
        $floor = explode('/',trimall($floors[1]));
        $topfloor = $floor[1];
        $house_info['house_floor'] = $floor[0];
        $house_info['house_topfloor'] = $topfloor;
		
	    $house_info['house_type'] = '';
	    
	    preg_match("/houseDetail\-fac([\x{0000}-\x{ffff}]+?)<\/ul>/u",$html,$conf);
	    $conf_str = str_replace("</li><li><i></i>","#",trimall($conf));
	    $conf_string=strip_tags($conf_str[1]);
        $conf_string = str_replace('">','',$conf_string);
	    $house_info['house_configroom'] = $conf_string;
	    
	    if($house_info['house_configroom']){
	    	$house_info['house_configroom'] = $house_info['house_configroom']."#";
	    }
	    
	    $house_info['house_configpub'] = '';

	    //联系人
	    preg_match("/profile-name\">([\x{0000}-\x{ffff}]+?)</u",$html,$name);
	    $house_info['owner_name'] = trimall(strip_tags($name[1]));
	    
	    //联系电话
	    preg_match("/person-phoneNumber\">([\x{0000}-\x{ffff}]+?)</u",$html,$phone);
	    $house_info['owner_phone'] = trimall(strip_tags($phone[1]));
	    if($house_info['owner_phone'] == $house_info['owner_name']){
	        $house_info['owner_name'] = '';
	    }
		if(empty($house_info['owner_phone'])){
			$infoid = explode('/', $source_url);
			$infoid = $infoid[count($infoid)-1];
			$temp_infoid = explode('x.',$infoid);
			$infoid = $temp_infoid[0];
			$getphoneurl = 'http://telsecret.58.com/telsecret/telsecretHttpService/getSecretPhoneByInfoId?callBackFunc=jsonp5&terminalId=2&callerId=1&browserPhone=17090123123&browserToolKey=none&browserInputCaptcha=&infoId='.$infoid;
			$phonedata = curl_get($getphoneurl);
			$house_info['owner_phone'] = $phonedata['secretPhone'];
		}

		$house_info1 = \QL\QueryList::run('Request',[
				'target' => $source_url,
				])->setQuery([
						'house_topfloor' => ['.houseInfo-meta > li:nth-child(1) > span:nth-child(2)', 'text', '', function($house_topfloor){
							return $house_topfloor;
						}],
						'house_pic_unit' => ['.image_area_new > ul:nth-child(1) > li> img:nth-child(1)', 'ref', '', function($house_pic_unit){
							return $house_pic_unit;
						}]])->getData(function($data) use($temp_url){
							//下架检测
							//			$data['off_type'] = $this->is_off($source_url);
							return $data;
						});
								
								preg_match("/共(\d+\.?\d*)层/", $house_info1[0]['house_topfloor'], $temp_floor);
								$house_info['house_topfloor']=$temp_floor[1];
								preg_match("/(低|中|高)层/u",$house_info1[0]['house_topfloor'],$temp_floor1);
								
								$house_info['house_floor']=$temp_floor1[1];
							foreach((array)$house_info1 as $key => $value){
								if(isset($house_info1[$key]['house_pic_unit'])){
									$house_pic_unit[] = $house_info1[$key]['house_pic_unit'];
								}
							}
							$house_info1[0]['house_pic_unit'] = implode('|', $house_pic_unit);
							$house_info['house_pic_unit']=$house_info1[0]['house_pic_unit'];
        //付款方式
        preg_match("/付款\:([\x{0000}-\x{ffff}]+?)<\/span>/u",$html,$pay_type);
	    $house_info['pay_type'] = trimall($pay_type[1]);;
	    $house_info['sex'] = '';
	    $house_info['into_house'] = '';    
	    $house_info['pay_method'] = '';
	    $house_info['tag'] = '';
	    $house_info['comment'] = '';
	    $house_info['house_number'] = '';
	    $house_info['deposit'] = '';
	    $house_info['is_ture'] = '';
	    $house_info['created'] = time();
	    $house_info['updated'] = time();
	    $house_info['house_relet'] = 2;
	    $house_info['wap_url'] = '';
	    $house_info['app_url'] = '';
	    $house_info['is_contrast'] = 2;
	    $house_info['is_fill'] = 2;
	    $house_info['chain_url'] = '';
	    return $house_info;
	}
	//统计官网数据
	public function house_count(){
// 	    $PRE_URL = 'http://bj.58.com/ershoufang/';
// 	    $totalNum = $this->queryList($PRE_URL, [
// 	        'total' => ['.result > span:nth-child(1)','text'],
// 	    ]);
// 	    return $totalNum;
	    return 0;
	}
    //下架判断
    public function is_off($url,$html=''){
        return 2;
        if(!empty($url)) {
            if (empty($html)) {
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $newurl = get_jump_url($url);
            if ($newurl == $url) {
                if (preg_match("/ico_error/", $html)) {
                    return 1;
                } else {
                    return 2;
                }
            } else {
                return 1;
            }
        }
    }
}

?>