<?php namespace haerbin;
/**
 * @description 哈尔滨房天下合租房源抓取规则
 * @classname 哈尔滨房天下
 * @author lp
 * @version 1
 * @since 2016-03-25
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","4000M");
ini_set('max_execution_time', '0');

Class FangPersonal extends \city\PublicClass{
    /*
     * 抓取
     */
    //http://esf.hrb.fang.com/house/a211-c220-d225-h316-l3100/
    public $URLPRE_1 = "http://esf.hrb.fang.com/house/a211-";
    public $URLPRE_2 = "-h316";
    public $host = 'http://esf.fang.com/house/a211-h316-i3';

    Public function house_page(){
        $url=[];
        for($price_c=0;$price_c <= 145;$price_c+=5){
            $price_d=$price_c+5;
            $current_url = $this->URLPRE_1.'c2'.$price_c.'-d2'.$price_d.$this->URLPRE_2.'-l3100';
            sleep(mt_rand(5,10));
            $max = $this->get_maxPage($current_url);
//            echo $max;
            for($page = 1;$page <= $max;$page++){
                $url[] = $this->URLPRE_1.'c2'.$price_c.'-d2'.$price_d.$this->URLPRE_2.'-i3'.$page.'-l3100';
            }
        }
        $current_url = $this->URLPRE_1.'c2150-l3100';
        $max = $this->get_maxPage($current_url);
        sleep(mt_rand(5,10));
        for($page = 1;$page <= $max;$page++){
            $url[] = $this->URLPRE_1.'c2150'.$this->URLPRE_2.'-i3'.$page.'-l3100';
        }
		return $url;
    }


	/*
	 * 列表页
	*/
    public function house_list($url){
//    [0] => http://esf.hrb.fang.com/house/a211-c2140-d2145-h316-i31-l3100
//    [1] => http://esf.hrb.fang.com/house/a211-c2140-d2145-h316-i32-l3100
        sleep(mt_rand(5,10));
        $html = $this->getHtml2($url);
        preg_match("/class=\"houseList\"([\x{0000}-\x{ffff}]+?)每页显示/u", $html, $houses);;
        preg_match_all("/a target=\"_blank\" href=\"\/chushou\/([\x{0000}-\x{ffff}]+?)\">/u", $houses[1], $hl);
//        var_dump($hl);die;
        foreach ($hl[1] as $v){

            $house_info[] = "http://esf.hrb.fang.com/chushou/".$v;
        }
        return $house_info;
    }
	
	/*
	 *获取详情页数据
	*/
	public function house_detail($source_url) {
//		  [0] => http://esf.hrb.fang.com/chushou/16_223749.htm
//        [1] => http://esf.hrb.fang.com/chushou/16_223451.htm
//        [2] => http://esf.hrb.fang.com/chushou/16_223265.htm
//        [3] => http://esf.hrb.fang.com/chushou/16_223240.htm
//        [4] => http://esf.hrb.fang.com/chushou/16_223113.htm
//        [5] => http://esf.hrb.fang.com/chushou/16_223111.htm
//        [6] => http://esf.hrb.fang.com/chushou/16_222877.htm
//        [7] => http://esf.hrb.fang.com/chushou/16_222766.htm
		//详情页拿信息
        sleep(mt_rand(5,10));
        $html = $this->getHtml2($url);
//        var_dump($html);die;
        //下架检测
//         $house_info['off_type'] = $this->is_off($source_url,$html);
        //详细信息

        //标题
        preg_match("/class=\"title\"\>([\x{0000}-\x{ffff}]+?)<\/h1>/u", $html, $title);
        $house_title = explode('>',trimall(strip_tags($title[0])));
        $house_info['house_title'] =$house_title[1] ;
        preg_match("/house-info\">([\x{0000}-\x{ffff}]+?)<\/div>/u", $html, $info);
        //图片列表
        preg_match("/<div\s*class=\"slider\"[\x{0000}-\x{ffff}]+?<\/div>/u", $html, $pics);
        $info = trimall($info[0]);
        preg_match("/[\x{0000}-\x{ffff}]*?小区/u", $info, $p);
        preg_match("/小区([\x{0000}-\x{ffff}]*?)交通/u", $info, $b);
        preg_match("/房屋[\x{0000}-\x{ffff}]*?配套/u", $info, $a);
        preg_match("/配套设施:([\x{0000}-\x{ffff}]+?)评分/u", $info, $config);
        //var houseInfo对象元素抓取
        preg_match("/配套[\x{0000}-\x{ffff}]+/u", $info, $config);
        //面积?为啥出不来？
        preg_match("/建筑面积：(\d+)/", $html, $totalarea);
//        var_dump($totalarea);die;
        $house_info['house_totalarea'] = $totalarea[1];
        //小区名称和编号
        preg_match('/小区：([\x{0000}-\x{ffff}]*?)<\/a>/u', $html, $bor);
        $bor = explode(">",$bor[1]);
        $house_info['borough_name'] = $bor[1];
//        preg_match('/houseid\:\s\'(\d+)\'/u', $html,$borough_id);
        $house_info['borough_id'] = $borough_id[1];
        //房屋类型
//        preg_match('/purpose\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $house_type);
//        $house_info['house_type'] = $house_type[1];
        //价格
        preg_match("/售价：([\x{0000}-\x{ffff}]*?)万/u", $html, $price);
        preg_match("/>(.*)</", $price[1], $price_x);
        $house_info['house_price'] = $price_x[1];
        //城区商圈
        preg_match('/href=\"\/house\/\">哈尔滨二手房(.*?)二手房/', $html, $cityarea);
        $cityarea = explode('">',$cityarea[1]);
//        var_dump($cityarea[2]);die;
        preg_match('/href=\"\/house\/\">哈尔滨二手房([\x{0000}-\x{ffff}]*?)<\/div>/u', $html, $cityarea_2);
        $cityarea_2 = explode('>',$cityarea_2[1]);
        $cityarea_2 = explode('<',$cityarea_2[8]);
        $cityarea_2 = str_replace('二手房','',$cityarea_2[0]);
//        var_dump($cityarea_2[0]);die;
//        if (empty($cityarea_2)){    //另一版面无法从houseInfo中提取到商圈信息，在以下字段抓取
//            preg_match("/区\：([\x{0000}-\x{ffff}]*?)\]/u",$html,$Pre_cityarea2);
//            $cityarea_2 = explode('/',strip_tags($Pre_cityarea2[1]));
//        }
        $house_info['cityarea_id'] = $cityarea[2];
        $house_info['cityarea2_id'] = $cityarea_2;
        //owner名称电话
        preg_match('/yzdp([\x{0000}-\x{ffff}]*?)<\/b>/u', $html, $owner);
        $owner = explode('<b>',$owner[1]);
        $house_info['owner_name'] = trimall($owner[1]);
        if(empty($house_info['owner_name'])){
            preg_match('/class=\"name\"([\x{0000}-\x{ffff}]*?)<\/a>/u', $html, $owner);
            $owner = explode('业主',$owner[1]);
            $house_info['owner_name'] = trimall($owner[1]);
            if($house_info['owner_name']=='电话'||$house_info['owner_name']=='人'){
                $house_info['owner_name'] = "暂无资料";
            }
        }
//        var_dump($house_info['owner_name']);die;
        preg_match('/yezhu400big\">([\x{0000}-\x{ffff}]*?)<\/strong>/u', $html, $phone_1);
        preg_match('/yezhu400min\">([\x{0000}-\x{ffff}]*?)<\/strong/u', $html, $phone_2);
        $phone = trimall($phone_1[1]).','.trimall($phone_2[1]);
//        $house_info['owner_phone'] = $phone;
//        if (empty($phone[1])){
//            preg_match('/class\=\"tel\sred\sfloatl\">([\x{0000}-\x{ffff}]*?)<\/div>/u', $html, $phone);
//            $house_info['owner_phone'] = str_replace('-', '', str_replace("转", ",", trimall(strip_tags($phone[1]))));
//        }
        //楼层及总楼层
        preg_match("/楼层：(.*?)层（共(.*?)层）<\/dd>/", $html, $floor);
//        var_dump($floor);die;
        $house_info['house_floor'] = $floor[1];
        $house_info['house_topfloor'] = $floor[2];
        preg_match("/房源编号：(.*) </", $html, $num);
        $house_info['house_number'] = $num[1];
//        if(empty($floor)){
//            preg_match("/(高|中|低)层/", $info, $house_floor);
//            preg_match("/共(\d+)层/", $info, $house_topfloor);
//            $house_info['house_floor'] = $house_floor[1];
//            $house_info['house_topfloor'] = $house_topfloor[1];
//        }
//        if(empty($floor)){   //无法从详情页抓取楼层信息时，从列表页抓取
//            $house_info['house_floor'] = $split[1];
//            $house_info['house_topfloor'] = $split[2];
//        }

        //朝向
        preg_match('/(东西|南北|东北|东南|西北|西南)/u',$html,$toward);
        if(empty($toward[1])){
            preg_match('/朝向：([东南北西]*)/u',$html,$toward);
        }
        $house_info['house_toward'] = $toward[1];

        //室厅卫厨
        preg_match("/(\d+)室/", $html, $r);
        $house_info['house_room']=empty($r)?0:$r[1];
        preg_match("/(\d+)厅/", $html, $h);
        $house_info['house_hall']=empty($h)?0:$h[1];
        preg_match("/(\d+)卫/", $html, $t);
        $house_info['house_toilet']=empty($t)?0:$t[1];
        preg_match('/(毛坯|简装修|精装修|豪华装修)/u',$info,$fitment);
        $house_info['house_fitment'] = $fitment[1];

        if(preg_match("/暂无资料/", $config[1])){
            $house_info['house_configroom'] = '';
        }else{
            $c = str_replace("/", ",", $config[1]);
            $c = explode(',', $c);
            $house_info['house_configroom'] = implode('#', $c);
        }
//        $house_info['house_configpub'] = '';
        //图片
        preg_match("/class=\"fy-img\"[\x{0000}-\x{ffff}]*?<\/div>/u",$html, $pics);
        preg_match_all("/src=\"(\S+?)\"/u", $pics[0], $pictures);
        preg_match("/id=\"BoxMainPic\" src2=\"([\x{0000}-\x{ffff}]+?)\"/u",$html, $thumb);
//        preg_match_all("/src=\"(\S+?)\"/u", $thumb[0], $thumbs);
        $house_info['house_pic_unit'] = array();
        $house_info['house_pic_unit'][] = $thumb[1];
        foreach($pictures[1] as $k=>$v){
            $house_info['house_pic_unit'][] = $v;
        }
        $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
        $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
//        $house_info['house_pic_layout'] = '';

        //标题中有性别的信息，暂时做以下处理
        preg_match("/(限女生|限男生|男女不限)/", $html, $sex);
//        $house_info['sex'] = $sex[1];

        //<div class="Introduce floatr"
        preg_match("/<p class=\"cmtC\">([\x{0000}-\x{ffff}]*?)<\/p>/u", $html, $desc);
//        var_dump($desc);die;
//        if (empty($desc)){
//            preg_match("/class\=\"agent-txt([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $desc);
//        }
        $desc = $desc[1];
//        $desc = str_replace(array("\t", "\n", " ",'&nbsp;','&nbsp；'), "", $desc);
        $house_info['house_desc'] = trimall($desc);
//        $house_info['into_house'] = '';
        //主卧、次卧、单间
//        preg_match('/ext:\s*\'(次卧|主卧|单间)\'/u',$html,$style);
//        $house_info['house_style'] = $style[1];
        $pay_type = explode('[', $p[0]);
        $pay_type = explode(']', $pay_type[1]);
        $house_info['pay_type'] = trimall($pay_type[0]);
//        $house_info['pay_method'] = '';
//
//        $house_info['tag'] = '';
//        $house_info['comment'] = '';
//        $house_info['house_number'] = '';
//
//        $house_info['deposit'] = '';
//        $house_info['is_ture'] = '';

        $house_info['created'] = time();
        $house_info['updated'] = time();
//        $house_info['service_phone'] = '';
//        $house_info['source_url'] = '';

        $house_info['source_name'] = '搜房网';

//        $house_info['house_relet'] = '';
//        $house_info['wap_url'] = '';
//        $house_info['app_url'] = '';
        $house_info['is_contrast'] = 2;
        $house_info['is_fill'] = 2;
//        $house_info['chain_url'] = '';
        $house_info = array_merge($house_info);

//        var_dump($house_info);die;
		return $house_info;
	}
	
	//统计官网数据
	public function house_count(){
// 	    $PRE_URL = 'http://zu.hrb.fang.com/hezu-a0';
// 	    $totalNum = $this->queryList($PRE_URL, [
// 	        'total' => ['span.pull-right > span:nth-child(1)','text'],
// 	    ]);
// 	    return $totalNum;
        return 0;
	}
	
	/*
	 * 获取搜索条件下的最大页
	 */
	Public function get_maxPage($url){
	    $html = $this->getHtml2($url);
	    preg_match('/txt\">共(\d+?)页/u',$html,$page);
	    $maxPage = $page[1];
	    //如果最大页抓空，返回0
	    if(!empty($maxPage)){
	        return $maxPage;
	    }else{
	        return 0;
	    }
	}
	
	
	public function getHtml2($url){
		$html = gb2312_to_utf8(getHtml($url));
		if(empty($html)){
			sleep(1);
			$html = gb2312_to_utf8(getHtml($url));
		}
		return $html;
	}
	
    public function is_off($url,$html){
        return 2;
        if(!empty($url)){
            $html = $this->getHtml2($url);
            if(preg_match("/searchNoInfo/", $html)){
                return 1;
            }elseif(preg_match("/sellAll/", $html)){
                return 1;
            }elseif(preg_match("/ico-wrong/", $html)){
                return 1;
            }elseif(preg_match("/guoqi\.gif/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }

    }

    public function callNewData(){;
        $data = [];
        for($i = 1; $i <= 100; $i++){
            $data[] = $this -> host . $i;
        }
        return $data;
    }
}