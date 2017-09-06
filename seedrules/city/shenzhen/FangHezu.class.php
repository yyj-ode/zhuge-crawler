<?php namespace shenzhen;
/**
 * @description 深圳房天下 合租房抓取规则
 * @classname 深圳房天下(k-ok-!)
 */
Class FangHezu extends \city\PublicClass{
    Public function house_page(){
        $distinct = array(
            "福田", "罗湖", "南山", "盐田", "宝安", "龙岗", "东莞", "惠州", "光明新区", "龙华新区", "坪山新区", "大鹏新区");
        $dis = array(
            85, 86, 87, 88, 89, 90, 13057, 13058, 13079, 13080, 13081, 13082);
        //每个城区列表页的最大页码
        $pagelist = array(
            100,100,100,29,100,100,14,6,24,100,17,2);
        //0-19
        $index = empty($_GET['dis'])?0:$_GET['dis'];
        $flag_page = 0;
        $URLPRE = "http://zu.sz.fang.com/hezu-a0".$dis[$index]."/h31";
        $maxPage = empty($_GET['$maxPage'])?$pagelist[$index]:$_GET['$maxPage'];
        $url = [];
        for($page=1; $page<=$maxPage; $page++){
            if($flag_page == 0){
                $page = empty($_GET['page'])?1:$_GET['page'];
                $flag_page = 1;
            }
            $url[] = ($page == 1) ? $URLPRE.'/' : $URLPRE.'-i3'.$page.'/';
        }
        return $url;
    }

	/*
	 * 列表页
	*/
	public function house_list($url){
		$html = gb2312_to_utf8(getSnoopy($url)); //$this->getUrlContent()会得到乱码
        $house_info = array();
        $house_floor = array();
        $house_topfloor = array();
		preg_match("/houseList\sstar([\x{0000}-\x{ffff}]+?)houseList\send/u", $html, $houses);
		preg_match_all("/blank\"\shref=\"([\x{0000}-\x{ffff}]+?)\">/u", $houses[1], $hl);
        preg_match_all("/info\srel([\x{0000}-\x{ffff}]+?)<\/dd>/u", $html, $floors);
        foreach($floors[1] as $f){
            preg_match("/(\d+)\/(\d+)层/u", $f, $split_floor);
            $house_floor [] = $split_floor[1];
            $house_topfloor [] = $split_floor[2];
        }
		foreach ($hl[1] as $k =>$v){
            $house_info[$k] = "http://zu.sz.fang.com".$v.'|'.$house_floor[$k].'|'.$house_topfloor[$k];
		}
        return $house_info;
	}

	/*
	 *获取详情页数据
	*/
    public function house_detail($source_url){
//        $source_url = 'http://zu.sz.fang.com/chuzu/1_51551002_-1.htm|15|31';
        $split = explode('|',$source_url);
        $source_url = $split[0];
        $html = gb2312_to_utf8(getSnoopy($source_url));  //$this->getUrlContent()会得到乱码
//        var_dump($html);exit;
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url,$html);
        //过滤经纪人房源及其他渠道房源
        //没有conName字段时默认为经纪人房源
        preg_match('/comName:\s*\'([\x{0000}-\x{ffff}]*?)\'/u',$html,$comName);
        if ($comName[1] == "-1") {
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
//		    var_dump($info);
            preg_match("/[\x{0000}-\x{ffff}]*?小区/u", $info, $p);
            preg_match("/小区[\x{0000}-\x{ffff}]*?交通/u", $info, $b);
            preg_match("/房屋[\x{0000}-\x{ffff}]*?配套/u", $info, $a);
            //var houseInfo对象元素抓取
            preg_match("/配套[\x{0000}-\x{ffff}]+/u", $info, $config);
            //面积
            preg_match("/buildingArea\:\s\'(\d+\.?\d*)\'/u", $html, $totalarea);
            $house_info['house_room_totalarea'] = $totalarea[1];
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
            if (empty($cityarea_2)){    //另一版面无法从houseInfo中提取到商圈信息，在以下字段抓取
                preg_match("/区\：([\x{0000}-\x{ffff}]*?)\]/u",$html,$Pre_cityarea2);
                $cityarea_2 = explode('/',strip_tags($Pre_cityarea2[1]));
            }
            $house_info['cityarea_id'] = $cityarea[1];
            $house_info['cityarea2_id'] = $cityarea_2[1];
            //owner名称电话
            preg_match('/agentName\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $owner);
            $house_info['owner_name'] = $owner[1];
            preg_match('/agentMobile\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $phone);
            $house_info['owner_phone'] = str_replace('-', '', str_replace("转", ",", $phone[1]));
            if (empty($phone[1])){
                preg_match('/class\=\"tel\sred\sfloatl\">([\x{0000}-\x{ffff}]*?)<\/div>/u', $html, $phone);
                $house_info['owner_phone'] = str_replace('-', '', str_replace("转", ",", trimall(strip_tags($phone[1]))));
            }
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
            preg_match('/(毛坯|简装修|中装|精装|豪华装修)/u',$info,$fitment);
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
            preg_match("/fy-img[\x{0000}-\x{ffff}]*?<\/div>/u",$html, $pics);
            preg_match_all("/src=\"(\S+?)\"/u", $pics[0], $pictures);
            $house_info['house_pic_unit'] = array();
            foreach($pictures[1] as $k=>$v){
                $house_info['house_pic_unit'][] = $v;
            }
            $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
            $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
            $house_info['house_pic_layout'] = '';

            //性别
//            preg_match("/house-info\"([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $pre_sex);
            preg_match("/(限女生|限男生|男女不限)/", $html, $sex);
            $house_info['sex'] = $sex[1];

            //房源描述
            //<div class="Introduce floatr"
            preg_match("/<div\s*class=\"Introduce([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $desc);
            if (empty($desc)){
                preg_match("/class\=\"agent-txt([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $desc);
            }
            $desc = strip_tags($desc[1]);
            $desc = str_replace(array("\t", "\n", " ",'&nbsp;','&nbsp；'), "", $desc);
            $house_info['house_desc'] = explode('>',str_replace('联系我时，请说是在房天下上看到的，谢谢！','',trimall($desc)))[1];

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

    //检测该房源是否下架
    public function is_off($url,$html){
        if(!empty($url)){
            if(empty($html)){
                $html = gb2312_to_utf8(getSnoopy($url));
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