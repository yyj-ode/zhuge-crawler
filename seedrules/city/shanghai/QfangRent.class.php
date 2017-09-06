<?php namespace shanghai;
/**
 * @description 上海Q房整租房源抓取规则
 * @classname 上海Q房整租
 */


Class QfangRent extends \city\PublicClass{
    
    Public function house_page(){
        $minPage=$_GET['minPage'];
        $maxPage=$_GET['maxPage'];
        $minPage=empty($minPage)?1:$minPage;
        $maxPage=empty($maxPage)?300:$maxPage;
        $url_pre="http://shanghai.qfang.com/rent/h1-f";
        $url = [];
        for ($page=$minPage; $page<=$maxPage; $page++){
            $url[] = $url_pre.$page;
        }
        return $url;
    }
    /*
     * 列表页
    */
    public function house_list($url){
       
    	$html = file_get_contents($url);
    	preg_match('/id=\"cycleListings\">([\x{0000}-\x{ffff}]*?)<\/ul>/u',$html,$li);
    	preg_match_all("/<a\s*key=\"showKeyword\"\s*target=\"\_blank\"\s*href=\"([\x{0000}-\x{ffff}]*?)\"/u", $li[0], $lis);
    	$this->house_info = array();
    	foreach($lis[1] as $k=>$v){
    	    $house_info[] = 'http://shanghai.qfang.com'.$v;
    	}
    	return $house_info;
    }
    /*
     *获取详情页数据
    */
    public function house_detail($source_url) {
    	 $details = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$details);
    	//                 echo $details;die;
    	preg_match('/<h2\sclass=\"text\_of\">([\x{0000}-\x{ffff}]*?)<\/h2>/u',$details,$title);
    	//标题
    	$house_info['house_title'] = $title[1];
    	preg_match('/guide-alink-detailspage([\x{0000}-\x{ffff}]*?)<\/div>/u',$details,$area);
//     	dump($area);die;
    	preg_match_all('/<i\s*class=\"icons_saledetails\">([\x{0000}-\x{ffff}]*?)<\/a>/u',$area[1],$ci);
//     	dump($ci[1]);die;
    	$pp = [];
    	foreach ($ci[1] as $v){
    	    $pp[] = str_replace('租房','',trimall(strip_tags($v)));
    	}
//     	dump($pp);die;
    	//城区
    	$house_info['cityarea_id'] = $pp[1];
    	//商圈
    	$house_info['cityarea2_id'] = $pp[2];
    	//小区id
    	$house_info['borough_id'] = '';
    	
    	preg_match('/header-field-list\">([\x{0000}-\x{ffff}]*?)<\/ul>/u',$details,$xq);
    	$xq = $xq[1];
//     	dump($xq);die;
    	//小区名
    	preg_match('/小区：([\x{0000}-\x{ffff}]*?)<\/a>/u',$xq,$borough);
    	$borough_name = trimall(strip_tags($borough[1]));
//     	dump($borough_name);die;
    	$house_info['borough_name'] = $borough_name;
    	//付款类型
    	preg_match('/&nbsp&nbsp([\x{0000}-\x{ffff}]*?)<\/b>/u',$xq,$pt);
//     	dump($pt);die;
    	$house_info['pay_type'] = $pt[1];
    	//房源类型
    	preg_match('/<span\s*class=\"field\-text\s*fl">类型：([\x{0000}-\x{ffff}]*?)<\/li>/u',$xq,$type);
    	$house_info['house_type'] = trimall(strip_tags($type[1]));
    	$xq = trimall(strip_tags($xq));
//     	dump($xq);die;
    	//总面积
    	$house_info['house_totalarea'] = '';
    	//价格
    	preg_match("/租金：(\d*)元/",$xq,$price);
//     	dump($price);die;
    	$house_info['house_price'] = $price[1];
    	//出租间面积
    	preg_match('/(\d+\.?\d*)㎡/',$xq,$tota);
//     	dump($tota);die;
    	$house_info['house_totalarea'] = $tota[1];
    	//朝向
    	preg_match('/朝向：([\x{0000}-\x{ffff}]*?)类型/u',$xq,$tow);
    	$toward = trimall(strip_tags($tow[1]));
    	$house_info['house_toward'] = $toward;
    	//室
    	preg_match('/户型：([\x{0000}-\x{ffff}]*?)楼层/u',$xq,$room);
    	preg_match('/(\d*)房.*/u',$room[1],$rooms);
    	$house_info['house_room'] = $rooms[1];
    	//厅
    	preg_match('/\d*房(\d*)厅/u',$room[1],$hall);
    	$house_info['house_hall'] = $hall[1];
    	//卫生间
    	$house_info['house_toilet'] = 1;
    	//厨房
    	$house_info['house_kitchen'] = '';
    	//装修
    	preg_match('/装修：([\x{0000}-\x{ffff}]*?)朝向/u',$xq,$fit);
    	$fitment = trimall(strip_tags($fit[1]));
    	$house_info['house_fitment'] = $fitment;
    	//所在楼层
    	preg_match('/楼层：([\x{0000}-\x{ffff}]*?)装修/u',$xq,$floor);
    	$house_floor = trimall(strip_tags($floor[1]));
    	$floors = explode('/',$house_floor);
    	$house_info['house_floor'] = str_replace('层','',$floors[0]);;
    	//总楼层
    	$house_info['house_topfloor'] = str_replace('层','',$floors[1]);
    	//联系人姓名
    	preg_match('/broker\-basic\-name\">([\x{0000}-\x{ffff}]*?)<\/p>/u',$details,$name);
    	$house_info['owner_name'] = trimall(strip_tags($name[1]));
    	//联系人电话
    	preg_match('/tel-num">([\x{0000}-\x{ffff}]*?)<\/p>/u',$details,$tel);
    	$house_info['owner_phone'] = trimall(strip_tags($tel[1]));
    	//房源描述
    	preg_match('/<div\sclass\=\"evaluation\_con"[\x{0000}-\x{ffff}]*?<\!\-\-\send\s房源点评\s\-\->/u',$details,$desc);
    	//dump($desc[0]);
    	$this->house_desc = str_replace('&nbsp;','',trimall(strip_tags($desc[0])));
    	//dump($this->house_desc);die;
    	$house_info['house_desc'] = $this->house_desc;
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
    	// 室内图 用|分割
    	preg_match("/<ul\s*id\=\"guideMinmapCon\">[\x{0000}-\x{ffff}]*?<\!\-\-布局图/u",$details,$pic);
    	//                 var_dump($pic);die;
    	preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\"/u",$pic[0],$allpic);
    	$picarr = str_replace('"','',trimall($allpic[1]));
    	$picstr = array_unique($picarr);
    	$house_info['house_pic_unit'] = implode('|', $picstr);
    	//户型图
    	preg_match("/<\!\-\-布局图 \-\->[\x{0000}-\x{ffff}]*?<\!\-\-\s小区图片\s\-\->/u",$details,$pic2);
    	preg_match_all("/\-src=\"([\x{0000}-\x{ffff}]*?)\"/u",$pic2[0],$allpic2);
    	$picarr2 = str_replace('"','',trimall($allpic2[1]));
    	$picstr2 = implode('|',$picarr2);
    	$house_info['house_pic_layout'] = $picstr2;
    	
    	$house_info['pub_time'] = '';
    	$house_info['is_contrast'] = 2;
    	$house_info['is_fill'] = 2;
    	
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
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml','html',$url);
            if($newurl == $oldurl){
                $Tag = \QL\QueryList::Query($html,[
                    "isOff" => ['.remove_over','class',''],
                    "404" => ['.error-404','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if(empty($Tag)){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }
}
?>