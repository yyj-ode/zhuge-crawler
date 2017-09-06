<?php namespace nanjing;
/**
 * @description 南京房天下整租抓取规则
 * @classname 南京房天下(k-ok)
 */

Class FangRent extends \city\PublicClass
{
    Public function house_page(){
        $distinct = array(
            268=>"江宁", 265=>"鼓楼", 262=>"白下", 271=>"栖霞", 267=>"建邺", 264=>"玄武",270=>"浦口",
            272=>"雨花", 263=>"秦淮", 266=>"下关" , 269=>"六合",274=>"溧水",275=>"高淳",13046=>"南京周边");
        //0-13
        $dis = array(
            268, 265, 262, 271, 267, 264, 270, 272, 263, 266, 269, 274 ,275 ,13046);
        $urlarr = [];
        foreach($dis as $index){
            $URLPRE = "http://zu.nanjing.fang.com/house-a0".$index."/a21-n31";
            $maxPage = $this->get_maxPage($URLPRE);
            for($page=1; $page<=$maxPage; $page++){
                $urlarr[] = ($page == 1) ? $URLPRE.'/' : $URLPRE.'-i3'.$page.'/';
            }
        }
        return $urlarr;
    }
	
   /*
	* 列表页
	*/
	public function house_list($url){
		$html = gb2312_to_utf8(getSnoopy($url));
		preg_match_all("/<dl\s*class=\"list[\x{0000}-\x{ffff}]+?<\/ul>/u", $html, $houses);
		$house_info = array();
		foreach ($houses[0] as $k=>$v){
			preg_match("/[hezu]*?[chuzhu]*?\/[_\-\w\.]*?htm/", $v, $source);
            preg_match('/(\d+)\//u',$v,$floor);
            preg_match('/(\d+)层/u',$v,$top);
            //dump($source);die;
            $house_info[] = "http://zu.nanjing.fang.com/".$source[0]."|".$floor[1]."|".$top[1];
		}
		return $house_info;
	}
	
   /*
	*获取详情页数据
	*/
	public function house_detail($url) {
        $source_url = explode("|",$url)[0];
        $floor = explode("|",$url)[1];
        $topfloor = explode("|",$url)[2];
		$html = gb2312_to_utf8(getSnoopy($source_url));
//		$house_info['off_type'] = $this->is_off($url);
        preg_match("/<script[\x{0000}-\x{ffff}]+?\/script>/u",$html,$detail);
        $info = strip_tags($detail[0]);
        //var_dump($info);die;
		if(preg_match('/comName:\s*\'-1\'/u',$html)){
		    //var里面有很多详情信息
		    preg_match("/<h1>[\x{0000}-\x{ffff}]+?<\/h1>/u", $html, $title);
		    $house_info['house_title'] = trimall(strip_tags($title[0]));
            //价格
            preg_match("/price: \'(\d+)\'/",$info,$price);
            $house_info['house_price'] = $price[1];
            //面积
            preg_match("/buildingArea: \'(\d+)\'/",$info,$area);
            $house_info['house_totalarea'] = $area[1];
            //室
            preg_match("/room: \'(\d+)\'/",$info,$room);
            $house_info['house_room'] = $room[1];
            //厅
            preg_match("/hall: \'(\d+)\'/",$info,$hall);
            $house_info['house_hall'] = $hall[1];
            //联系人
            preg_match("/agentName: \'([\x{0000}-\x{ffff}]+?)\'/u",$info,$owner_name);
            $house_info['owner_name'] = $owner_name[1];
            //电话
            preg_match("/agentMobile: \'(\d+)\'/",$info,$owner_phone);
            $house_info['owner_phone'] = $owner_phone[1];
            //城区
            preg_match("/district: \'([\x{0000}-\x{ffff}]+?)\'/u",$info,$area_id);
            $house_info['cityarea_id'] = $area_id[1];
            //商圈
            preg_match("/comarea: \'([\x{0000}-\x{ffff}]+?)\'/u",$info,$area2_id);
            $house_info['cityarea2_id'] = $area2_id[1];
            //小区
            preg_match("/projname: \'([\x{0000}-\x{ffff}]+?)\'/u",$info,$bor);
            $house_info['borough_name'] =$bor[1];
		    //图片列表
		    preg_match("/fy-img\">[\x{0000}-\x{ffff}]+?<\/div>/u", $html, $pics);
            preg_match_all("/data-src=\"(\S+?)\"/u", $pics[0], $pictures);
            $house_info['house_pic_unit'] = array();
            foreach($pictures[1] as $k=>$v){
                $house_info['house_pic_unit'][] = $v;
            }
            $house_info['house_pic_unit'] = array_unique($house_info ['house_pic_unit']);
            $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
            $house_info['house_pic_layout'] = '';
		    preg_match('/house-info\">[\x{0000}-\x{ffff}]+?<\/ul>/u',$html,$divs);
            $div = trimall(strip_tags($divs[0]));

//		    //几户合租
//		    preg_match("/(\d+)户合租/u", $div, $homes);
//		    $house_info['homes'] = empty($homes[1]) ? 0 : $homes[1];

            preg_match("/peitao = \'([\x{0000}-\x{ffff}]+?)\'/u",$info,$config);
            $house_info['house_configroom'] = str_replace(',', '#', $config[1]);
            //楼层及总楼层
            $house_info['house_floor'] = $floor;
            $house_info['house_topfloor'] = $topfloor;
		    //装修
		    preg_match("/(毛坯|简装修|精装修|豪华装修)/u", $div, $fitment);
		    $house_info['house_fitment'] = $fitment[1];
		    //朝向
		    preg_match("/(东北|西北|东南|西南|南北|东西)/u", $div, $toward);
		    if(!empty($toward[1])){
		        preg_match("/(东|西|南|北)/u", $div, $toward);
		    }
		    $house_info['house_toward'] = $toward[1];
		    
		    $house_info['house_configpub'] = '';

		    //标题中有性别的信息，暂时未做处理
		    $house_info['sex'] = '';
		    	
		    //<div class="Introduce floatr"
		    preg_match("/agent-txt-per\s*floatl\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $desc);
		    $desc = strip_tags($desc[1]);
		    $desc = str_replace(array("\t", "\n", " ","&nbsp;"), "", $desc);
		    $house_info['house_desc'] = trimall($desc);
		    
		    $house_info['into_house'] = '';
		    

		    $house_info['pay_method'] = '';
		    
		    $house_info['tag'] = '';
		    $house_info['comment'] = '';
		    $house_info['house_number'] = '';
		    	
		    $house_info['deposit'] = '';
		    $house_info['is_ture'] = '';
		    
		    $house_info['created'] = time();
		    $house_info['updated'] = time();
		    
		    $house_info['house_relet'] = '';
		    $house_info['wap_url'] = '';
		    $house_info['app_url'] = '';
		    $house_info['is_contrast'] = 2;
		    $house_info['is_fill'] = 2;
		    $house_info['source_owner'] = 3;
		    $house_info['chain_url'] = '';
		    return $house_info;
		}
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
    /*
     * 下架判断
     * */
    
    public function is_off($url,$html=''){
        return 2;
        if(!empty($url)){
            if(empty($html)){
                $html = gb2312_to_utf8(getSnoopy($url));
            }
            //抓取下架标识
            $off_type = 1;
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml','html',$url);
            if($newurl == $oldurl){
                $html = gb2312_to_utf8(getSnoopy($newurl));
                preg_match('/<div\s*class=\"titleSa\">([\x{0000}-\x{ffff}]*?)<span/u',$html,$isOff);
                $Tag['isOff'] = $isOff[1];
                preg_match('/id=\"content\">([\x{0000}-\x{ffff}]*?)<\/legend>/u',$html,$error);
                $Tag['404'] = $error[1];
                if($Tag['isOff'] == null && $Tag['404'] == null){
                    $off_type = 2;
                }
            }
            return $off_type;
        }
        return -1;
    
    }
}