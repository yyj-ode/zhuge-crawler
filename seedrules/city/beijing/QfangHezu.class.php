<?php namespace beijing;
/**
 * @description 北京Qfang 合租房抓取规则
 * @classname 北京Qfang
 */

Class QfangHezu extends \city\PublicClass{
	Public function house_page(){
		$maxPage=242;
		$urlarr =array();
		$url_pre="http://beijing.qfang.com/rent/h2-f";
		for ($page=1; $page<=$maxPage; $page++){
			$urlarr[]= $url_pre.$page;
			}
//        $urlarr[] = 'http://beijing.qfang.com/rent/h2-f1';
		return $urlarr;
	}

	/*
     * 列表页
    */
    public function house_list($url){
    	$list = $this->getUrlContent($url);
        $house_info = [];
    	preg_match("/id=\"cycleListings\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $list, $div);
    	//拿到每个li标签
    	preg_match_all("/<li([\x{0000}-\x{ffff}]*?)<\/li>/u", $div[1], $lis);
    	foreach($lis[0] as $k=>$li){
    		//tags
//    		preg_match_all("/house\-tags\-itme\sclearfix\">([\x{0000}-\x{ffff}]*?)<\/p>/u",$li,$tags);
//    		$tagstr = trimall(strip_tags(implode('#',$tags[1])));
//    		$house_info['tag'] = $tagstr;
    		preg_match("/href=\"\/rent\/(\d*)\">/", $li, $href);
    		$house_info[] = 'http://beijing.qfang.com/rent/'.$href[1];
    		//房源编号
    		//$house_info[] = $href[1];
    	}
		return $house_info;
    }
    /*
     *获取详情页数据
    */
    public function house_detail($url) {
        //$url = "http://beijing.qfang.com/rent/5276687";
    	$details = $this->getUrlContent($url);
        //下架检测
        $house_info['off_type'] = $this->is_off($url,$details);
    	preg_match('/text_of\">([\x{0000}-\x{ffff}]*?)<\/h2>/u',$details,$title);
    	//标题
    	$house_info['house_title'] = trimall($title[1]);
    	//城区
    	preg_match('/地址\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$details,$area);
    	$areaarr = explode('&nbsp;',trimall(strip_tags($area[1])));
    	$house_info['cityarea_id'] = str_replace('[','',$areaarr['0']);
    	//商圈
    	$house_info['cityarea2_id'] = str_replace(']','',$areaarr['1']);
    	//小区id
    	$house_info['borough_id'] = '';
    	//小区名
    	preg_match('/区\：<\/span>([\x{0000}-\x{ffff}]*?)<\/a>/u',$details,$borough);
    	$borough_name = trimall(strip_tags($borough[1]));
    	$house_info['borough_name'] = $borough_name;
        //修建年代
        preg_match('/区\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$details,$years);
        preg_match('/(\d*)年建/',trimall(strip_tags($years[1])),$house_built_year);
        $house_info['house_built_year'] = $house_built_year[1];
    	//总面积
    	$house_info['house_totalarea'] = '';
    	//出租间面积
    	preg_match('/(\d*)㎡/u',$details,$tota);
//    	$total = trimall(strip_tags(str_replace('㎡','',$tota[1])));
    	$house_info['house_room_totalarea'] = $tota[1];
    	//朝向
    	preg_match('/朝向\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$details,$tow);
    	$toward = trimall(strip_tags($tow[1]));
    	$house_info['house_toward'] = $toward;
    	//室
    	preg_match('/户型\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$details,$room);
    	preg_match('/(\d*)房/u',$room[1],$rooms);
    	$house_info['house_room'] = $rooms[1];
    	//厅
    	preg_match('/(\d*)厅/',$room[1],$hall);
    	$house_info['house_hall'] = $hall[1];
    	//卫生间
    	$house_info['house_toilet'] = 1;
    	//厨房
    	$house_info['house_kitchen'] = '';
    	//装修
    	preg_match('/装修\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$details,$fit);
    	$fitment = trimall(strip_tags($fit[1]));
    	$house_info['house_fitment'] = $fitment;
    	//房源类型
        preg_match('/朝向\：<\/span>([\x{0000}-\x{ffff}]*?)小区/u',$details,$Pre_type);
    	preg_match('/类型\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$Pre_type[1],$type);
    	$house_type = trimall(strip_tags($type[1]));
    	$house_info['house_type'] = $house_type;
    	//所在楼层
    	preg_match('/(低|中|高)层/u',$details,$floor);
    	$house_info['house_floor'] = $floor[1];
    	//总楼层
    	preg_match('/楼层\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u',$details,$topfloor);
        preg_match("/\/(\d+)层/u",trimall(strip_tags($topfloor[1])),$house_topfloor);
    	$house_info['house_topfloor'] = $house_topfloor[1];
    	//联系人姓名
    	preg_match('/broker-basic-name\">([\x{0000}-\x{ffff}]*?)<\/p>/u',$details,$name);
    	$house_info['owner_name'] = trimall(strip_tags($name[1]));
    	preg_match('/store\_info\"\>([\x{0000}-\x{ffff}]+?)\<\/p\>/u',$details,$tags);
    	$house_info['company'] = $tags[1];
    	//联系人电话
    	preg_match('/brokerMobile\"\s*value\=\"([\x{0000}-\x{ffff}]*?)\"/u',$details,$tel);
    	$house_info['owner_phone'] = trimall(strip_tags($tel[1]));
    	//房源描述
    	preg_match('/hsEvaluation\"\s>([\x{0000}-\x{ffff}]*?)<\/div>/u',$details,$desc);
    	$house_desc = str_replace('&nbsp;','',trimall(strip_tags($desc[1])));
    	$house_info['house_desc'] = $house_desc;
    	//卧室类型
    	$house_info['house_style'] = '';
    	//是否为转租
    	$house_info['house_relet'] = 2;
    	//来源
    	$house_info['source'] = 5;
    	//业主来源
    	$house_info['source_owner'] = '';
    	//appurl
    	$house_info['app_url'] = '';
    	//wap端url
    	$house_info['wap_url'] = '';
    	//1:限男生  2:限女生   3：男女不限(默认值)
    	$house_info['sex'] = 3;
    	//入住时间
    	$house_info['into_house'] = '';
    	//付款方式
    	$house_info['pay_method'] = '';
    	//付款类型
    	preg_match("/租赁类型\：<\/span>([\x{0000}-\x{ffff}]*?)<\/span>/u",$details,$pay);
    	$house_info['pay_type'] = trimall(strip_tags(str_replace(array('合租','&nbsp'),'',$pay[1])));
    	//评论
    	$house_info['comment'] = '';
    	//押金
    	$house_info['deposit'] = '';
    	//合租户数
    	$house_info['homes'] = '';
    	//真实度
    	$house_info['is_ture'] = '';
    	//室友信息
    	$house_info['friend_info'] = '';
    	//价格
    	preg_match("/price\sfl\"><b>(\d*)<\/b>/u",$details,$price);
    	$house_info['house_price'] = $price[1];
    	//创建时间
    	$house_info['created'] = time();
    	//更新时间
    	$house_info['updated'] = time();
    	//居室配置
    	$house_info['house_configroom'] = '';
    	//房屋公共配置
    	preg_match("/<div\sclass\=\"house\_advantage\_item\">[\x{0000}-\x{ffff}]*?<\/div>/u",$details,$config);
    	preg_match_all("/<i\s*class=\"icons\_saledetails\"><\/i>\s*<span>([\x{0000}-\x{ffff}]*?)<\/span>/u",$config[0],$conf);
    	$configstr = trimall(strip_tags(implode("#",$conf[1])));
    	$house_info['house_configpub'] = $configstr;
    	//外链，例如爱直租的数据是从58上抓来的，此字段存储58上的URL
    	$house_info['chain_url'] = '';
        //户型图
        preg_match("/hsPics([\x{0000}-\x{ffff}]*?)<\/div>/u",$details,$pic2);
        preg_match_all("/data-src=\"([\x{0000}-\x{ffff}]*?)\"/u",$pic2[0],$allpic2);
        $picarr2 = str_replace('"','',trimall($allpic2[1]));
        $picstr2 = implode('|',array_unique($picarr2));
        $house_info['house_pic_unit'] = $picstr2;
    	//户型图
    	preg_match("/<\!\-\-布局图 \-\->[\x{0000}-\x{ffff}]*?<\!\-\-\s小区图片\s\-\->/u",$details,$pic2);
    	preg_match_all("/\-src=\"([\x{0000}-\x{ffff}]*?)\"/u",$pic2[0],$allpic2);
    	$picarr2 = str_replace('"','',trimall($allpic2[1]));
    	$picstr2 = implode('|',array_unique($picarr2));
    	$house_info['house_pic_layout'] = $picstr2;
    	$house_info['pub_time'] = '';
    	$house_info['is_contrast'] = 2;
    	$house_info['is_fill'] = 2; 	
    	$house_info = array_merge($house_info);
		return $house_info;
    }
    //统计官网数据
    public function house_count(){
        $PRE_URL = 'http://beijing.qfang.com/rent/h2';
        $totalNum = $this->queryList($PRE_URL, [
            'total' => ['.dib','text'],
        ]);
        return $totalNum;   
        // 	    return 0;
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
                "isOff" => ['.remove_over','class',''],
//                    "404" => ['.error-404','class',''],
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
?>