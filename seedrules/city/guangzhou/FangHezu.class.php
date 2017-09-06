<?php namespace guangzhou;
/**
 * @description 广州房天下合租房抓取规则
 * @classname 广州房天下(k-ok)
 */

Class FangHezu extends \city\PublicClass
{
    Public function house_page(){
        $distinct = array(
            "白云", "从化", "番禺", "海珠", "花都", "黄埔", "荔湾", "南沙", "天河", "越秀", "增城", "广州周边");
        $dis = array(
            76, 79, 78, 74, 639, 75, 71, 84, 73, 72, 80, 15882);

        foreach($dis as $index){
            $URLPRE = "http://zu.gz.fang.com/hezu-a0".$index."/a21";
            //获取最大页
            $maxPage = $this->get_maxPage($URLPRE);
            echo $URLPRE."\r\n";
            $urlarr = [];
            for($page=1; $page<=$maxPage; $page++){
                $urlarr[] = ($page == 1) ? $URLPRE."/" : $URLPRE.'-i3'.$page.'/';
            }
        }
        return $urlarr;
    }
	/*
	 * 列表页
	*/
	public function house_list($url){
        $html = gb2312_to_utf8($this->getUrlContent($url));
        preg_match_all("/<dl\s*class=\"list[\x{0000}-\x{ffff}]+?<\/dt>/u", $html, $houses);
        $house_info = array();
        $house_floor = array();
        $house_topfloor = array();
        preg_match_all("/info\srel([\x{0000}-\x{ffff}]+?)<\/dd>/u", $html, $floors);
        foreach($floors[1] as $f){
            preg_match("/(\d+)\/(\d+)层/u", $f, $split_floor);
            $house_floor [] = $split_floor[1];
            $house_topfloor [] = $split_floor[2];
        }
        foreach ($houses[0] as $k=>$v){
            preg_match("/[rent]*?[chuzhu]*?\/[_\-\w\.]*?htm/", $v, $source);
            $house_info[$k] = "http://zu.gz.fang.com/".$source[0].'|'.$house_floor[$k].'|'.$house_topfloor[$k];
        }
        return $house_info;
	}
	/*
	 *获取详情页数据
	*/
	public function house_detail($source_url) {
        $split = explode('|',$source_url);
        $source_url = $split[0];
		//$source_url = "http://zu.gz.fang.com/chuzu/1_50813799_-1.htm";
		//详情页拿信息
		$html = gb2312_to_utf8($this->getUrlContent($source_url));
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
        preg_match("/<script[\x{0000}-\x{ffff}]+?\/script>/u",$html,$detail);
        $info = strip_tags($detail[0]);
		//过滤经纪人房源
		if(preg_match('/comName:\s*\'-1\'/u',$html)){
            //var里面有很多详情信息
            preg_match("/<h1>[\x{0000}-\x{ffff}]+?<\/h1>/u", $html, $title);
            $house_info['house_title'] = trimall(strip_tags($title[0]));
            //价格
            preg_match("/price: \'(\d+)\'/",$info,$price);
            $house_info['house_price'] = $price[1];
            //面积
            preg_match("/buildingArea: \'(\d+)\'/",$info,$area);
            $house_info['house_room_totalarea'] = $area[1];
            //室
            preg_match("/room: \'(\d+)\'/",$info,$room);
            if (empty($room[1])){
                preg_match("/(\d+)室/",$html,$room);
            }
            $house_info['house_room'] = $room[1];
            //厅
            preg_match("/hall: \'(\d+)\'/",$info,$hall);
            if (empty($hall[1])){
                preg_match("/(\d+)厅/",$html,$hall);
            }
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
            preg_match("/(\d+)\/(\d+)/", $info, $floor);
            $house_info['house_floor'] = $floor[1];
            $house_info['house_topfloor'] = $floor[2];
            if(empty($floor)){
                preg_match("/(高|中|低)层/", $info, $house_floor);
                preg_match("/共(\d+)层/", $info, $house_topfloor);
                $house_info['house_floor'] = $house_floor[1];
                $house_info['house_topfloor'] = $house_topfloor[1];
            }
            if(empty($floor)){   //无法从详情页抓取楼层信息时，从列表页抓取
                $house_info['house_floor'] = $split[1];
                $house_info['house_topfloor'] = $split[2];
            }
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
            $house_info['house_desc'] = str_replace('联系我时，请说是在房天下上看到的，谢谢！','',trimall($desc));

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
	Public function get_maxPage($url){
	    $html = gb2312_to_utf8($this->getUrlContent($url));
	    preg_match('/txt\">共(\d+?)页/u',$html,$page);
	    $maxPage = $page[1];
	    //如果最大页抓空，返回0
	    if(!empty($maxPage)){
	        return $maxPage;
	    }else{
	        return 0;
	    }
	}
    //检测该房源是否下架
    public function is_off($url,$html){
        if(!empty($url)){
            if(empty($html)){
                $html = gb2312_to_utf8($this->getUrlContent($url));
            }
            $newurl = get_jump_url($url);
            if($newurl == $url){
                if(preg_match("/searchNoInfo/", $html)){
                    return 1;
                }elseif(preg_match("/sellAll/", $html)){
                    return 1;
                }elseif(preg_match("/ico-wrong/",$html)){
                    return 1;
                }
                else{
                    return 2;
                }
            }else{
                return 1;
            }
        }
    }
}