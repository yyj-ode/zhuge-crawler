<?php namespace shanghai;
/**
 * @description 上海酷房整租房源抓取规则
 * @classname 上海酷房整租
 */

Class KufangRent extends \city\PublicClass{
    Public function house_page() {
        /*
         http://beijing.koofang.com/rent/c1/t8/pg1
         */
    
        $dis = array(10,11,12,13,14,15,16,17,18,19,20,21,22,24);
        //0-12
        $index =empty($_GET['dis'])?0:$_GET['dis'];
    
        //$type=array(10,11,12,13,14,15,16,17,18,9=>20,21,22,12=>24);
        $max=array(30,13,1,29,15,1,3,1,5,1,37,3,1,2);
    
        $url = [];
        for($page = 1; $page <= $max[$index]; $page ++) {
            $url[] = "http://shanghai.koofang.com/rent/c".$dis[$index]."/pg".$page."/";
        }
        return $url;
    }
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		$html = file_get_contents($url);
    	preg_match_all("/<div\s*class=\"fangyuan\">[\x{0000}-\x{ffff}]+?target=\"\_blank\">/u", $html, $houses);
    	$house_info = array();
    	foreach ($houses[0] as $k=>$v){
    	    //dump($v);
    		preg_match("/<a\s*href=\"([\x{0000}-\x{ffff}]*?)\"/u", $v, $source);  
    		//dump($source);die;
    		$house_info[] = $source[1];
    		
    	}
    	return $house_info;
	}
	
	/*
	 * 获取详情
	 */
	public function house_detail($source_url){
		//详情页拿信息
//        $source_url = 'http://shanghai.koofang.com/rent/d-1000484992.html';
		$html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
		//详细信息
		//标题
    	preg_match("/<dt\s*title=([\x{0000}-\x{ffff}]*?)<\/dt>/u", $html, $title);
    	$title = strip_tags ( $title[0]);
    	$title = trimall($title);
    	//echo $title;
    	$house_info['house_title'] =$title;
    	$house_info['source'] = 3;
//      	preg_match("/举报房源([\x{0000}-\x{ffff}]*?)配套设施/u", $html, $details);
//      	$details = strip_tags ( $details);    	
//      	$details = trimall($details);
//      	dump($details);die;
//     	$d = strip_tags ( $html);
//     	$d = trimall($d);
//     	$d = SBC_DBC($d);
//     	$d = str_replace("&nbsp;","",$d);
    	preg_match("/更新时间([\x{0000}-\x{ffff}]*?)进入店铺/u", $html, $details);
    	$details = strip_tags ( $details[1]);
    	$details = trimall($details);
    	$details = SBC_DBC($details);
    	$details = str_replace("&nbsp;","",$details);
    	//echo $details;die;
    	//价格
    	preg_match("/租价:([\x{0000}-\x{ffff}]*?)户型/u", $details, $p); 	
    	$p = str_replace(array("租价:","元/月户型"),"",$p[0]);
    	$house_info['house_price'] =$p;
    	//面积
    	//echo $p;die;
    	//室
    	preg_match("/户型:([\x{0000}-\x{ffff}]*?)室/u", $details, $r);
    	$r = str_replace(array("户型:","室"),"",$r[0]);
    	$house_info['house_room'] =$r;
    	//厅
    	preg_match("/室([\x{0000}-\x{ffff}]*?)厅/u", $details, $h);
    	$h = str_replace(array("室","厅"),"",$h[0]);
    	$house_info['house_hall'] = $h;
    	//厨
    	preg_match("/厅([\x{0000}-\x{ffff}]*?)厨/u", $details, $c);
    	$c = str_replace(array("厅","厨"),"",$c[0]);
    	$house_info['house_kitchen'] = $c;
    	//卫
    	preg_match("/厨([\x{0000}-\x{ffff}]*?)卫/u", $details, $t);
    	$t = str_replace(array("厨","卫"),"",$t[0]);
    	$house_info['house_toilet'] = $t;
    	preg_match("/<meta\s*name=\"description\"\s*content=\"([\x{0000}-\x{ffff}]+?)\"/u", $html, $content);
    	$content = SBC_DBC($content[1]);
    	 
    	//圣世一品，600万元，2室2厅2卫，产证面积：105平米，单价：57143元/平米，无租约，第12层/共25层,房龄：2008年，东北朝向，精装修-找更多北京圣世一品二手房信息就到北京酷房网
    	//$details = explode(',', $content);
    	 
    	//$house_info['borough_name'] = $details[0];
    	 //
    	//面积
    	preg_match("/(\d+\.?\d*)㎡/", $details, $area);
    	$house_info['house_totalarea'] = $area[1];
    	//楼层信息
    	//所在楼层
    	preg_match("/楼层:([\x{0000}-\x{ffff}]+?)楼层/u", $details, $floor);
    	$house_info['house_floor'] = $floor[1];
    	//总楼层
    	preg_match("/共(\d+)层/u", $details, $topfloor);
    	$house_info['house_topfloor'] = $topfloor[1];
    	//建造年份
    	preg_match("/(\d+)年/", $details, $year);
    	$house_info['house_built_year'] = $year[1];
    	 
    	//朝向
    	preg_match("/(东北|东南|西北|西南|南北)/", $details, $toward);
    	if(empty($toward)){
    		preg_match("/(东|南|西|西)/", $details, $toward);
    	}
    	$house_info['house_toward'] = $toward[1];
    	//装修情况
    	preg_match("/(毛坯|普通装修|中等装修|精装修|豪华装修)/", $details, $fitment);
    	$house_info['house_fitment'] = $fitment[1];
    	
    	$house_info['created'] = time();
    	$house_info['updated'] = time();
    	 
    	//联系方式及联系人
    	preg_match("/<span\s*class=\"fixed_span1\">([\x{0000}-\x{ffff}]+?)<\/span>/u", $html, $con);
    	$con = trimall($con[1]);
    	$con = explode('：', $con);
    	//发布人姓名
    	$house_info['owner_name'] = trimall($con[0]);
    	//发布人电话
    	$house_info['owner_phone'] = trimall($con[1]);
    	 
    	//城区和商圈
    	preg_match("/<span\s*class=\"fixed_span2\">[\x{0000}-\x{ffff}]+?<\/span>/u", $html, $city);
    	$city = strip_tags ( $city[0]);
    	$city = str_replace(array("&nbsp;","\n"),"",$city);
    	$city = explode(' ', $city);
    	$house_info['cityarea_id'] = $city[2];
    	$house_info['cityarea2_id'] = $city[3];
    	$house_info['borough_name'] = $city[4];
    	 
    	//房源图片
    	preg_match_all("/Details_Page_five_down_a([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $divs);
    	$pics = array();
    	foreach($divs[1] as $v){
    		preg_match("/href=\"(\S*?)\"/", $v, $img);
    		$pics[] = $img[1];
    	}
    	$house_info['house_pic_layout'] = empty($pics[0])?'':$pics[0];
    	for($j=1; $j<count($pics); $j++){
    		 
    		$house_info['house_pic_unit'][] = $pics[$j];
    	}
    	$house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
    	$house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
    	 
    	//房源详情
        preg_match("/century21_desrcibe_jieshao\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $Tag_desc);
        $Pre_desc = trimall(strip_tags($Tag_desc[1]));
        $desc = str_replace('&nbsp;','',$Pre_desc);
    	$house_info['house_desc'] = $desc;
    	//类型
    	preg_match("/>(普通住宅|别墅)</u", $html, $type);
    	$house_info['house_type'] = trim($type[1]);
    	 
    	//来源
    	$house_info['company_name']='酷房网';
    	return $house_info;
	}
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $off_type = 1;
            $Tag = \QL\QueryList::Query($html,[
                "isJump"=> ['.Details_Page_two_l_h > dl:nth-child(1) > dt:nth-child(1)','title',''],//详情页标题项，若没有，则发生了跳转
                "isOff" => ['.big_qvkuai_top','class',''],
                "404" => ['.contenttop_err','class',''],
            ])->getData(function($item){
                return $item;
            });
            if($Tag[0]['isJump'] != NULL && $Tag[0]['isOff'] == NULL && $Tag[0]['404'] == NULL){
                $off_type = 2;
                return $off_type;
            }
            return $off_type;
        }
        return -1;
    }
}