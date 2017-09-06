<?php namespace beijing;
/**
 * @description 北京房天下整租抓取规则
 * @classname 北京房天下
 * @author lp
 * @version 1
 * @since 2016-3-25

 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","4000M");
ini_set('max_execution_time', '0');


Class FangRent extends \city\PublicClass{
    /*
     * 抓取
     */
    Public function house_page(){
        $dis_name = array(
            "海淀", "朝阳", "东城", "西城", "丰台", "石景山", "房山", "门头沟", "通州" , "顺义",
            "昌平", "密云", "怀柔", "延庆", "平谷", 585=>'大兴', 987=>"燕郊",  11817=>"北京周边",
        );
        $dis = array(
            0, 1, 2, 3, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 585, 987, 11817
        );
		$urlarr=array();
        foreach ($dis as $index){
            $URLPRE = "http://zu.fang.com/house-a0".$index."/a21-n31/";
//            echo $URLPRE."\r\n";
            //获取最大页
            $maxPage = $this->get_maxPage($URLPRE);

            for($page = 1; $page<=$maxPage; $page++){
                $urlarr[] = ($page == 1) ? $URLPRE : "http://zu.fang.com/house-a0".$index."/a21-i3".$page."-n31/";
            }
        }
		return $urlarr;
    }
    /*
	 * 获取列表页
	 * */
    Public function house_list($url){
		$html = gb2312_to_utf8(getHtml($url));
		preg_match_all("#/chuzu[\d_\.\/]*?htm#", $html, $hrefs);
		$hrefs = array_unique($hrefs[0]);
		$hrefs = array_merge($hrefs);
		foreach($hrefs as $k=>$h){
			$house_info[] = "http://zu.fang.com/".$h;
		}
		print_r($house_info);die;
		return $house_info;
    }
	
	/*
	 * 获取详情
	 */
	public function house_detail($source_url){
//        $source_url = 'http://zu.fang.com/chuzu/1_59747099_-1.htm|2|6';
		//详情页拿信息
        $split = explode('|',$source_url);
        $source_url = $split[0];
		$html = gb2312_to_utf8(getSnoopy($source_url));
//        var_dump($html);
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url,$html);
		//过滤经纪人房源
		if(preg_match('/comName:\s*\'-1\'/u',$html)){
		    //详细信息
		    //标题
		    preg_match("/h1-tit\srel[\x{0000}-\x{ffff}]+?<\/h1>/u", $html, $title);
            $house_title = explode('>',trimall(strip_tags($title[0])));
		    $house_info['house_title'] =$house_title[1] ;
		    preg_match("/house-info\">([\x{0000}-\x{ffff}]+?)<\/div>/u", $html, $info);
		    //图片列表
		    preg_match("/<div\s*class=\"slider\"[\x{0000}-\x{ffff}]+?<\/div>/u", $html, $pics);
//		    $info = strip_tags($info[0]);
		    $info = trimall($info[0]);
		    preg_match("/[\x{0000}-\x{ffff}]*?小区/u", $info, $p);
		    preg_match("/小区[\x{0000}-\x{ffff}]*?交通/u", $info, $b);
		    preg_match("/房屋[\x{0000}-\x{ffff}]*?配套/u", $info, $a);
            //var houseInfo对象元素抓取
		    preg_match("/配套[\x{0000}-\x{ffff}]+/u", $info, $config);
            //面积
            preg_match("/buildingArea\:\s\'(\d+)\'/u", $html, $totalarea);
            $house_info['house_totalarea'] = $totalarea[1];
            //小区名称和编号
            preg_match('/projname\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $bor);
            $house_info['borough_name'] = $bor[1];
            preg_match('/houseid\:\s\'(\d+)\'/u', $html,$borough_id);
            $house_info['borough_id'] = $borough_id[1];
            //房屋类型
            preg_match('/purpose\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $house_type);
            $house_info['house_type'] = $house_type[1];
		    //价格
		    preg_match("/price\:\s\'(\d+)\'/u", $html, $price);
		    $house_info['house_price'] = $price[1];
            //城区商圈
            preg_match('/district\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $cityarea);
            preg_match('/comarea\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $cityarea_2);
            $house_info['cityarea_id'] = $cityarea[1];
            $house_info['cityarea2_id'] = $cityarea_2[1];
            //owner名称电话
            preg_match('/agentName\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $owner);
            preg_match('/agentMobile\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $phone);
            $house_info['owner_name'] = $owner[1];
            $house_info['owner_phone'] = str_replace('-', '', str_replace("转", ",", $phone[1]));
		    //楼层及总楼层
		    preg_match("/(\d+)\/(\d+)/", $info, $floor);
		    $house_info['house_floor'] = $floor[1];
		    $house_info['house_topfloor'] = $floor[2];
            if(empty($floor)){   //无法从详情页抓取楼层信息时，从列表页抓取
                $house_info['house_floor'] = $split[1];
                $house_info['house_topfloor'] = $split[2];
            }

		    //朝向
		    preg_match('/(东西|南北|东北|东南|西北|西南)/u',$info,$toward);
		    if(empty($toward[1])){
		        preg_match('/(东|北|南|西)/u',$info,$toward);
		    }
		    $house_info['house_toward'] = $toward[1];
		    
		    //室厅卫厨
		    preg_match("/(\d+)室/", $info, $r);
		    $house_info['house_room']=empty($r)?0:$r[1];
		    preg_match("/(\d+)厅/", $info, $h);
		    $house_info['house_hall']=empty($h)?0:$h[1];
		    preg_match("/(\d+)卫/", $info, $t);
		    $house_info['house_toilet']=empty($t)?0:$t[1];
		    preg_match("/(\d+)厨/", $info, $kitchen);
		    $house_info['house_kitchen']=empty($kitchen)?0:$kitchen[1];
		    //装修
		    preg_match('/(毛坯|简装修|精装修|豪华装修)/u',$info,$fitment);
		    $house_info['house_fitment'] = $fitment[1];
		    
		    if(preg_match("/暂无资料/", $config[0])){
		        $house_info['house_configroom'] = '';
		    }else{
		        $c = str_replace("配套设施:", "", $config[0]);
		        $c = explode(',', $c);
		        $house_info['house_configroom'] = implode('#', $c);
		    }
		    $house_info['house_configpub'] = '';
		    //图片
		    /*preg_match("/id=\"thumbnail[\x{0000}-\x{ffff}]*?<\/ul>/u",$html, $pics);
		    preg_match_all("/src=\"(\S+?)\"/u", $pics[0], $pictures);
		    $house_info['house_pic_unit'] = array();
		    foreach($pictures[1] as $k=>$v){
		        $house_info['house_pic_unit'][] = $v;
		    }
		    $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
		    $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
		    $house_info['house_pic_layout'] = '';*/
            //图片
            preg_match("/fy-img[\x{0000}-\x{ffff}]*?<\/div>/u",$html, $pics);
            preg_match_all("/data-src=\"(\S+?)\"/u", $pics[0], $pictures);
            $house_info['house_pic_unit'] = array();
            foreach($pictures[1] as $k=>$v){
                $house_info['house_pic_unit'][] = $v;
            }
            $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
            $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
            $house_info['house_pic_layout'] = '';
		    
		    //标题中有性别的信息，暂时未做处理
		    $house_info['sex'] = '';
		    
		    //<div class="Introduce floatr"
		    preg_match("/<div\s*class=\"Introduce([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $desc);
		    $desc = strip_tags($desc[0]);
		    $desc = str_replace(array("\t", "\n", " ",'&nbsp;','&nbsp；'), "", $desc);
		    $house_info['house_desc'] = str_replace('联系我时，请说是在房天下上看到的，谢谢！','',trimall($desc));
		    
		    $house_info['into_house'] = '';
		    
		    $pay_type = explode('[', $p[0]);
		    $pay_type = explode(']', $pay_type[1]);
		    $house_info['pay_type'] = trimall($pay_type[0]);
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
		    $house_info['source_owner'] = 3;
		    $house_info['chain_url'] = '';
		}else{
		    unset($house_info);
		}
		return $house_info;
	}
	
	//统计官网数据
	public function house_count(){
	    // 	    $PRE_URL = 'http://zu.fang.com/house-a0';
	    // 	    $totalNum = $this->queryList($PRE_URL, [
	    // 	        'total' => ['span.pull-right > span:nth-child(1)','text'],
	    // 	    ]);
	    // 	    return $totalNum;
	    return 0;
	}
	
	
	
	/*
	 * 获取搜索条件下的最大页
	 */
	public function get_maxPage($url){
	    $html = gb2312_to_utf8(getSnoopy($url));
	    preg_match('/txt\">共(\d+?)页/u',$html,$page);
	    $maxPage = $page[1];
	    //如果最大页抓空，返回0
	    if(!empty($maxPage)){
	        return $maxPage;
	    }else{
	        return 0;
	    }
	}
    public function is_off($url,$html){
        return 2;
        if(!empty($url)){
            if(empty($html)){
                $html = gb2312_to_utf8(getSnoopy($url));
            }

            if(preg_match("/searchNoInfo/", $html)){
                return 1;
            }elseif(preg_match("/sellAll/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }

    }
}