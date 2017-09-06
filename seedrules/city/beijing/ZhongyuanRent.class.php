<?php namespace beijing;
/**
 * @description 北京中原 整租房抓取规则
 * @classname 北京中原
 */



Class ZhongyuanRent extends \city\PublicClass{
	Public function house_page(){
		$maxPage= 300;
		$urlarr =array();
		for ($page=1; $page<$maxPage; $page++) {
			$urlarr[]= "http://mobileapi.centanet.com/010/api/Post?keyword=&fitment=&subwayid=&stationid=&lng=0&estcode=&area=&type=&price=&room=&posttype=r&staffno=&region=&startindex=" . ($page * 10) . "&lat=0&round=0&sort=12&pagecount=10&scopeid=";
		}
		return $urlarr;
	}
	/*
	 * 获取列表页
	 */
	public function house_list($url){
	    $snoopy=getXmlJsonSnoopy($url);
	    $list_decode=json_decode($snoopy,1);
        $house_info = [];
	    $k = 0;
	    foreach($list_decode['Result'] as $v){
	        $house_info[$k]= "http://bj.centanet.com/zufang/".$v['Id'].".html";
	   		$k++;
	    }
	    return $house_info;
	}
	
	/*
	 * 获取详情页
	 *
	 *  */
	public function house_detail($url){

	    preg_match('/zufang\/([\x{0000}-\x{ffff}]*?)\./u',$url,$ID);
	    $value = $ID[1];
	    $result_temp=json_decode(getXmlJsonSnoopy("http://mobileapi.centanet.com/010/api/Post?PostId=".$value),1);
	    //一个页面同步加载延迟0.02S
	    usleep(20000);
	    $result_image_array=json_decode(getXmlJsonSnoopy("http://mobileapi.centanet.com/010/api/PostImg?PostId=".$value),1);
	    $result_image=$result_image_array['Result'];
	    $result=$result_temp['Result'];
	    $result_agen=json_decode(getXmlJsonSnoopy("http://mobileapi.centanet.com/010/api/Staff?staffNo=".$result['StaffNo']."&postId=".$value),1);
	    $house_info['app_url'] = "http://mobileapi.centanet.com/010/api/Staff?staffNo=".$result['StaffNo']."&postId=".$value;
	    //从web补充建筑年代数据、小区、城区
	    
	    //dump($result_html);die;
	    $result_html=$this->getUrlContent($url);
		preg_match("/base\-info([\x{0000}-\x{ffff}]*?)roombase\-people/u",$result_html,$nav_con1);

		$info = trimall(strip_tags($nav_con1[1]));
		preg_match('/(\d{4})年/u',$info,$built_year);
		preg_match('/小区名称：([\x{0000}-\x{ffff}]*?)小区/u',$info,$borough_name);
		preg_match('/(毛坯|简装|精装|豪装)/u',$info,$fitment);
		$cityarea = explode('【',$info);
		$cityarea_id = explode('/',$cityarea[1])[0];
		$cityarea2_id = str_replace('】','',explode('/',$cityarea[1])[1]);
	    
	    //网址
	    preg_match('/房源编号：\s<\/p><p\sclass=\"binR\">\s(.*)\s<\/p><\/li><li><p\sclass=\"binL\">\s小区地址/u',$result_html,$number);
	    //房源编号
        $house_info['off_type'] = 2;
        $house_info['content']=$result_html;
	    $house_info['house_number'] = $number[1];
	    //标题
	    $house_info['house_title']=$result['Title'];
	    //价格
	    $house_info['house_price']=$result['RentalPrice'];
	    //室
	    $house_info['house_room'] =$result['RoomCnt'];
	    //厅
	    $house_info['house_hall'] = $result['HallCnt'];
	    //卫
	    $house_info['house_toilet'] =$result['ToiletCnt'] ;

	    //面积
	    $house_info['house_totalarea']=$result['GArea'];;
	    //朝向
	    $house_info['house_toward']=$result['Direction'];
	    //所在楼层
	    $house_info['house_floor']=str_replace("层","",$result['FloorDisplay']);
	    //总楼层
	    $house_info['house_topfloor']=$result['FloorTotal'];
	    //装修情况
	    $house_info['house_fitment']=$fitment[1];
	    //房源描述
        $house_info['house_desc']=str_replace('Ď','',trimall($result['PlainDescription']));
	    //房屋类型
	    $house_info['house_type']=$result['PostList']['PropertyType'];
	    //建成年代
	    $house_info['house_built_year']=$built_year[1];
	    //城区
	    $house_info['cityarea_id'] = $cityarea_id;
	    //商圈
	    $house_info['cityarea2_id'] = $cityarea2_id;
	    //小区名
	    $house_info['borough_name'] = $borough_name[1];
	    //小区id
	    $house_info['borough_id'] = '';
	    //房源人员名字
	    $house_info['owner_name'] = $result_agen['Result']['CnName'];
	    //房源人员电话
	    $house_info['owner_phone'] = $result_agen['Result']['Mobile'];
	    //房源经纪人服务商区
	    $house_info['fuwu_shq'] = $result_agen['Result']['DepartmentName'];
	    //经纪人公司
	    $house_info['company_name']='中原';
	    
	    //厨房
	    $house_info['house_kitchen'] = '';
	    //卧室类型
	    $house_info['house_style'] = '';
	    //性别
	    $house_info['sex'] = '';
	    //入住时间
	    $house_info['into_house'] = '';
	    //付款方式
	    $house_info['pay_method'] = '';
	    //付款类型
	    $house_info['pay_type'] = '';
	    //标签房源特色
	    $house_info['tag'] = '';
	    //评论
	    $house_info['comment'] = '';
	    
	    //押金
	    $house_info['deposit'] = '';
	    //合租户数
	    $house_info['homes'] = '';
	    //配套设施
	    $house_info['house_configroom'] = '';
	    $house_info['house_configpub'] = '';
	    //真实度
	    $house_info['is_ture'] = '';
	    //室友信息
	    $house_info['friend_info'] = '';
	    //插入时间
	    $house_info['created'] = time();
	    //更新时间
	    $house_info['updated'] = time();
	    
	    $house_info['house_pic_unit']=array();
	    $house_info['house_pic_layout']=array();
	    
	    foreach($result_image as $imgK=>$imgV){
	        if($imgV['RefType'] == "UNIT"){
	            //房源图
	            $house_info['house_pic_unit'][]=$imgV['HdPath'];
	        }
	        if($imgV['RefType'] == "LAYOUT"){
	            //房型图
	            $house_info['house_pic_layout'][]=$imgV['HdPath'];
	        }
	    }
	    $house_info['house_pic_layout'] = array_unique($house_info['house_pic_layout']);
	    $house_info['house_pic_layout']=implode('|',$house_info['house_pic_layout']);
	    
	    $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
	    $house_info['house_pic_unit']=implode('|',$house_info['house_pic_unit']);
	    
	    $house_info['house_relet'] = '';
	    $house_info['wap_url'] = '';
	    $house_info['pub_time'] = '';
	    $house_info['chain_url'] = '';
	    $house_info['is_contrast'] = 2;
	    $house_info['is_fill'] = 2;
	    $house_info['source_url']=$url;
	    //来源
	    $house_info['source']=2;
	    //source_owner 区分业主来源  1,房主儿网 2，爱直租
	    $house_info['source_owner'] = '';
	    $house_info = array_merge($house_info);
		return $house_info;
	}

	/**
	 * 获取最新的房源种子
	* @param type $num 条数
	* @return type
	*/
	public function callNewData($num = 84){
		$url = "http://mobileapi.centanet.com/010/api/Post?lng=0&posttype=s&startindex=2&lat=0&round=0&sort=0&pagecount=10";
// 		        http://mobileapi.centanet.com/010/api/Post?keyword=&fitment=&subwayid=&stationid=&lng=0&estcode=&area=&type=&price=&room=&posttype=r&staffno=&region=&startindex=" . ($page * 10) . "&lat=0&round=0&sort=12&pagecount=10&scopeid=
		$snoopy = getXmlJsonSnoopy($url);
		//if(empty($snoopy)){continue;}
		$list_decode = json_decode($snoopy, 1);
		$minPage = 0;
		$maxPage = $num;
		//一共11712个数据
		$urlarr = array();
		for ($page = $minPage; $page < $maxPage; $page++) {
			$url = "http://mobileapi.centanet.com/010/api/Post?lng=0&posttype=s&startindex=" . ($page * 10) . "&lat=0&round=0&sort=0&pagecount=10";
			$urlarr[] = $url;
		}
		return $urlarr;
	}
	

}
