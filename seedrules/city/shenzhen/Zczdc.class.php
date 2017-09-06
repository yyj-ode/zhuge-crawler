<?php namespace shenzhen;
/**
 * @description 深圳中诚致二手房抓取规则
 * @classname 深圳中诚致 (May-OK)
 */

Class Zczdc extends \city\PublicClass{

    public function house_page(){
        //从端口抓取
        //翻页规律：$Parameters ["start"]=0,1,2…..  $Parameters ["length"]=10(每一页抓取的数量可自定义)
        $minPage = empty ( $minPage ) ? 1 : $minPage;
        $maxPage = empty ( $maxPage ) ? 7 : $maxPage;
        $urlarr = [];
        for($page=$minPage; $page<=$maxPage; $page++){
            $urlarr[] = "http://www.zczdc.com/hourselist.aspx?a=b&page=".$page;
        }
        return $urlarr;
    }

	/*
	 * 获取列表页
	 */
	public function house_list($url){
	    $html = $this->getUrlContent($url);
        $house_info = [];
	    preg_match_all("/class=\"contenttext\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $link);	   
	    foreach ($link[1] as $res){
	        preg_match("/href=\'([\x{0000}-\x{ffff}]*?)\'/u", $res, $sublink);
	        $house_info[]="http://www.zczdc.com/". $sublink[1];
	    }
	    //dump($this->house_info);die;
        return $house_info;
	}
	
   /*
	* 获取详情页
	*/
	public function house_detail($source_url){
	    //$source_url = "http://www.zczdc.com/hoursedetail.aspx?id=57";
	    $html = $this->getUrlContent($source_url);
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url);
	    //标题
	    preg_match("/class=\"hoursetitled\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $title);
	    $house_info['house_title'] = trimall(strip_tags($title[1]));
	   /* //房源id
	    preg_match("/编号：([\x{0000}-\x{ffff}]*?)发布/u", $tit[1], $houseId);
	    $house_info['house_id'] = trimall($houseId[1]);*/
	    //详情
	    preg_match("/ class=\"hoursedinfo\">([\x{0000}-\x{ffff}]*?)<div\sclass=\"flotclear\">/u", $html, $detail);
	    $info = trimall(strip_tags($detail[1]));
	    //价格
	    preg_match("/(\d+)万/u", $info, $price);
	    $house_info['house_price'] = $price[1];
	    //面积
	    preg_match("/(\d+)㎡/u", $info, $area);
	    $house_info['house_totalarea'] = $area[1];
	    //室
	    preg_match("/(一室|二室|三室|四室|五室|五室以上)/u", $info, $room);
	    switch($room[1]){
	        case "一室":
	            $rom = 1;break;
	        case "二室":
	            $rom = 2;break;
	        case "三室":
	            $rom = 3;break;
	        case "四室":
	            $rom = 4;break;
	        case "五室":
	            $rom = 5;break;
	        default:
	            $rom = '5以上';
	    }
	    $house_info['house_room'] = $rom;
	    //厅
	    //preg_match("/(\d+)厅/u", $info, $hall);
	    $house_info['house_hall'] = '';
	    $house_info['house_toilet'] = '';
	    $house_info['house_kitchen'] = '';
	    //住房类型
	    preg_match("/类型：([\x{0000}-\x{ffff}]*?)建造/u", $info, $type);
	    //$house_info['house_type'] = $type[1];
	    //楼层
	    preg_match("/(\d+)楼/u", $info, $floor);
	    $house_info['house_floor'] = $floor[1];
	    //preg_match("/(\d+)层/u", $info, $topfloor);
	    $house_info['house_topfloor'] =  '';
	    //朝向
	    preg_match("/朝向：([\x{0000}-\x{ffff}]*?)所属/u", $info, $toward);
	    $house_info['house_toward'] = $toward[1];
	    //年代
	    preg_match("/(\d+)年/u", $info, $year);
	    $house_info['house_build_year'] = $year[1];
	    //装修
	    preg_match("/(毛坯|普装|精装|豪装)/u", $info, $fitment);
	    $house_info['house_fitment'] = $fitment[1];
	    //agent
	    preg_match("/(\d{11})/u", $info, $phone);
	    $house_info['owner_phone'] = $phone[1];
	    preg_match("/class=\"hoursedbname\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $agent);
	    $house_info['owner_name'] = trimall(strip_tags($agent[1]));
	    //小区名称
	    preg_match("/小区：([\x{0000}-\x{ffff}]*?)楼层/u", $info, $borough);
	    //$borough = explode("(",$borough[1]);
	    $house_info['borough_name'] = $borough[1];
	    //通过API抓取城区商圈
	    //抓取经纬度
	    preg_match('/房源地址：([\x{0000}-\x{ffff}].*)/u',$info,$address);
	    $Map = $this->getUrlContent("http://api.map.baidu.com/geocoder/v2/?address=".$address[1]."&output=json&ak=aqLgbABLabxT9csGOEhrjDFM");
	    $map = json_decode($Map,1);
        $lng = $map['result']['location']['lng'];//经度
        $lat = $map['result']['location']['lat'];//纬度
        $Map = $this->getUrlContent("http://api.map.baidu.com/geocoder/v2/?location=".$lat.",".$lng."&output=json&ak=aqLgbABLabxT9csGOEhrjDFM");
        $map = json_decode($Map,1);
	    $cityarea_id = str_replace("区","",$map['result']['addressComponent']['district']);
	    $cityarea2_id = explode(",",$map['result']['business'])[0];
	    $house_info['cityarea_id'] = $cityarea_id;
	    $house_info['cityarea2_id'] = $cityarea2_id;
	    //图片
	    preg_match("/class=\"hoursedcontent\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $picss);
	    preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\"/u", $picss[1], $pics);
	    $pics = array_unique($pics[1]);
	    foreach($pics as $key=>$res){
	        $pics[$key] = "http://www.zczdc.com".$res;
	    }
	    $house_info['house_pic_unit'] = implode('|',$pics);
	    $house_info['house_pic_layout'] = '';
	    //desc
	    preg_match("/class=\"hoursedcontent\">([\x{0000}-\x{ffff}]*?)<hr\s\/>/u", $html, $desc);
	    $desc = trimall(strip_tags($desc[1]));
	    $house_info['house_desc'] = $desc;
	    $house_info['app_url'] = '';
	    $house_info['borough_id'] = '';
	    $house_info['source'] = 17;
	    //source_owner 区分业主来源  1,房主儿网 2，爱直租
	    $house_info['source_owner'] = '';
	    //$house_info['sex'] = '';
	    //$house_info['into_house'] = '';
	    //$house_info['pay_method'] = $result['rentPayType'];
	    //$house_info['tag'] = '';
	    //$house_info['comment'] = '';
	    //$house_info['house_number'] = '';
	    //$house_info['deposit'] = '';
	    // $house_info['is_ture'] = '';
	    $house_info['created'] = time();
	    $house_info['updated'] = time();
	    $house_info['wap_url'] = '';
	    $house_info['is_contrast'] = 2;
	    $house_info['is_fill'] = 2;
        return $house_info;
	}

    //下架判断
    public function is_off($url){
        //共53套房源-无下架标识
        return 2;
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            //暂未找到下架页面
            if(preg_match("/sellBtnView1/", $html)){
                return 1;
            }elseif(!preg_match("/售价/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}