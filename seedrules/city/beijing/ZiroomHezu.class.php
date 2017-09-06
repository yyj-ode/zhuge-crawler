<?php namespace beijing;
/**
 * @description 北京自如 合租房抓取规则
 * @classname 北京自如
 */


Class ZiroomHezu  extends \city\PublicClass{
	Public function house_page(){
		//从端口抓取
		//翻页规律：$Parameters ["start"]=0,1,2…..  $Parameters ["length"]=10(每一页抓取的数量可自定义)
		$maxPage =626;
		$urlarr =array();
		for($page = 1; $page <= $maxPage; $page ++) {
			//将页码拼接到url中
			$urlarr[]="http://www.ziroom.com/?_p=api_mobile&_a=searchHouse|".$page;
		}
		return $urlarr;
	}
	/*
	 * 获取列表页
	 */
	public function house_list($url){
		$source_url = explode("|",$url)[0];
		$page = explode("|",$url)[1];
		$Parameters = array ();
		$Parameters ["start"] = $page;
		$Parameters ["length"] = 10;
		$Parameters ["house_tags"] = 0;
		//合租为0整租为1
		$Parameters ["house_type"] = 0;
		$Parameters ["house_huxing"] = 0;
		$Parameters ["house_keywords"] = 0;
		$Parameters ["max_area"] = 0;
		$Parameters ["max_lat"] = 0;
		$Parameters ["max_lng"] = 0;
		$Parameters ["max_rentfee"] = 0;
		$Parameters ["min_area"] = 0;
		$Parameters ["min_lat"] = 0;
		$Parameters ["min_lng"] = 0;
		$Parameters ["min_rentfee"] = 0;
		$Parameters ["subway_station_name"] = 0;
		$Parameters ["timestamp"] = 1460708055;
		$Parameters ["uid"] = 0;
		$Parameters ["sign"] = 'eb1c9847b8c6b52e94937322849af02a';
		$data = $this->getUrlContent($source_url, ['post' => $Parameters, 'referrer' => 'http://www.ziroom.com/']);
	    $json = json_decode ( $data );
		//print_r($json);
		$result=objarray_to_array($json)['data'];
	    foreach ($result as $k=>$res){
	        $house_info[]="http://www.ziroom.com/z/vr/". $res['house_code'].".html";
	    }
        //var_dump($house_info);die;
		return $house_info;
	}
	/*
	 * 获取详情页
	 *
	 *  */
	public function house_detail($url){
        preg_match('/(\d+)/',$url,$house_code);
        $postUrl = "http://interfaces.ziroom.com/index.php?_p=api_mobile&_a=detailShow";
        $Parameters ["house_code"] =$house_code[1];
        $Parameters ["sign"] = "b2da900074742543e44e3ceeefbb4c68";
        $Parameters["timestamp"] = "1460708232";
        $Parameters["city_code"] = "110000";
        $Parameters["uid"] = "0";
        $html = $this->getUrlContent($postUrl, ['post' => $Parameters, 'referrer' => 'http://www.ziroom.com/']);
        $web_html = file_get_contents($url);//$this->getUrlContent无法正确读取页面内容
        $result = objarray_to_array(json_decode( $html ))['data'][0];
        //下架检测
        $house_info['off_type'] = $this->is_off($url,$web_html);
	    //标题
	    $house_info['house_title'] = $result['house_name'];
	    //价格
	    $house_info['house_price'] = $result['house_price'];
	    //室
	    $house_info['house_room'] = $result['huxing'];
	    //厅
        preg_match("/(\d+)厅/",$web_html,$hall);
	    $house_info['house_hall'] = $hall[1];
	    //卫
	    $house_info['house_toilet'] = '';
	    //厨房
	    $house_info['house_kitchen'] = '';
	    //面积
	    $house_info['house_room_totalarea'] = $result['house_area'];
	    //朝向
	    $house_info['house_toward'] = $result['house_toward'];
	    //装修
	    $house_info['house_fitment'] = '';
	    //楼层
	    $house_info['house_floor'] = $result['cengshu'];
        preg_match("/\/(\d+)层/",$web_html,$topfloor);
	    $house_info['house_topfloor'] = $topfloor[1];
	    //通过API抓取城区商圈
	    //抓取经纬度
	    $lat = explode(",",$result['latitude_and_longitude'])[0];//经度
        $lng = explode(",",$result['latitude_and_longitude'])[1];//经度
	    $Map = file_get_contents("http://api.map.baidu.com/geocoder/v2/?location=".$lng.",".$lat."&output=json&ak=aqLgbABLabxT9csGOEhrjDFM");
	    $map = json_decode($Map,1);
	    $cityarea_id = str_replace("区","",$map['result']['addressComponent']['district']);
	    $cityarea2_id = explode(",",$map['result']['business'])[0];
	    $house_info['cityarea_id'] = $cityarea_id;
	    $house_info['cityarea2_id'] = $cityarea2_id;
	    //小区名称
	    $house_info['borough_name'] = $result['resblock_name'];
	    $house_info['borough_id'] = '';
	    $house_info['app_url'] ='';
	    $house_info['house_type'] = '';
	    $house_info['owner_name'] = $result['steward_name'];
        preg_match("/class\=\"tel\">([\x{0000}-\x{ffff}]*?)<\/div>/u",$web_html,$tel);
	    $house_info['owner_phone'] = str_replace('-',',',trimall(strip_tags($tel[1])));
	    $house_info['house_desc'] = $result['room_evaluation'].$result['house_evaluation_circum'].$result['house_evaluation_traffic'];
	    $house_info['house_style'] = '';
	    $house_info['source'] = 11;
	    //source_owner 区分业主来源  1,房主儿网 2，爱直租
	    $house_info['source_owner'] = '';
	    $house_info['sex'] = '';
	    $house_info['into_house'] = '';
	    $house_info['pay_method'] = '';
	    $house_info['tag'] = '';
	    $house_info['comment'] = '';
	    $house_info['deposit'] = '';
	    $house_info['is_ture'] = '';
	    $house_info['created'] = time();
	    $house_info['updated'] = time();
	    $house_info['house_configroom'] = implode("#",explode("、",$result['room_config']['room']));
	    $house_info['house_configpub'] =implode("#",explode("、",$result['room_config']['public']));;
	    //图片
	    $house_info['house_pic_unit'] = implode('|',array_unique($result['house_photos_big']));
	    $house_info['house_totalarea'] = '';
	    $house_info['house_relet'] = 2;
	    $house_info['wap_url'] = '';
	    $house_info['pub_time'] = '';
	    $house_info['is_contrast'] = 2;
	    $house_info['is_fill'] = 2;
		return $house_info;
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
                "isOff" => ['.view','text','',function($item){
                    return preg_match("/出租/",$item);
                }],
//                    "404" => ['.nopage','class',''],#zreserve
            ])->getData(function($item){
                return $item;
            });
            if($Tag[0]['isOff']==NULL){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
}