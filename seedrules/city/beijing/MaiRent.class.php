<?php namespace beijing;
/**
 * @description 北京麦田 整租房抓取规则
 * @classname 北京麦田
 */



Class MaiRent extends \city\PublicClass
{
    Public function house_page(){

        $maxPage=951;
        $url_pre="http://bj.maitian.cn/zfall";
        $urlarr =array();
        for ($page=1; $page<=$maxPage;$page++){
            $urlarr[]= $url_pre . "/PG" . $page;
        }
        return $urlarr;
    }
   /*
    * 抓取列表页
    */
    public function house_list($url){

    	$html = file_get_contents($url);
    	preg_match("/list\_wrap\">([\x{0000}-\x{ffff}]*?)Main\send/u", $html, $houseList);
    	preg_match_all("/list\_title\">([\x{0000}-\x{ffff}]*?)<\/li>/u", $houseList[0], $hrefs);
    	$hrefs = array_unique($hrefs[1]);
    	foreach($hrefs as $k=>$li){
    		preg_match("/zfxq\/I(\d+)\'/u", $li, $v);
            //所在楼层
            preg_match("/(高|中|低)楼层/", $li, $f);
            $floor = isset($f[1])?$f[1]:'';
            //总楼层
            preg_match("/(\d+?)层/", $li, $ft);
            $top_floor = isset($ft[1])?$ft[1]:'';
            preg_match_all('/class=\"three\">([^<>]+?)</',$li,$config);
            if($config[1]){
            	foreach ($config[1] as $conf){
            		$cf .= $conf."#";
            	}
            }
            
            preg_match('/mai\-ico\"><\/kbd>([^<>]+?)</',$li,$cont);
            $test = explode("&nbsp;",$cont[1]);
    		$house_info[$k]= "http://bj.maitian.cn/zfxq/I".$v[1].'|'.$floor.'|'.$top_floor.'|'.$cf.'|'.$test[0]."@".$test[1];
    	}
    	$house_info = array_merge($house_info);
        return $house_info;
    }
    
   /*
    * 获取详情页数据
    */
    public function house_detail($url){
        $Pre_url = explode('|',$url);
        $html = file_get_contents($Pre_url[0]);
        //下架检测
        $house_info['off_type'] = $this->is_off($url,$html);
        
        //链接
        $house_info['source_url'] = $Pre_url[0];
        
        //所在楼层
        $house_info['house_floor'] = $Pre_url[1];
        $house_info['house_topfloor'] = $Pre_url[2];
        //房屋配置
        $house_info['house_configroom'] = $Pre_url[3];
        
        $temp_city= $Pre_url[4];
        $city_tt = explode("@",$temp_city);
        preg_match("/网站地图([\x{0000}-\x{ffff}]+?)房源编号/u",$html,$details);
        $info = trimall(strip_tags($details[0]));
        //标题
        preg_match("/<samp>([\x{0000}-\x{ffff}]*?)<\/samp>/u", $details[0], $title);
        $house_info['house_title'] = trimall($title[1]);
        //价格
        preg_match("/(\d+)元\/月/u", $info, $price);
        $house_info['house_price'] = $price[1];
        //室
        preg_match("/(\d+)室/u", $info, $room);
        $house_info['house_room']= $room[1];
        //厅
        preg_match('/(\d+)厅/u', $info, $hall);
        $house_info['house_hall']= $hall[1];
        //卫生间
        preg_match('/(\d+)卫/u', $info, $toilet);
        $house_info['house_toilet'] = isset($toilet[1])? $toilet[1]:'';
        //厨房
        $house_info['house_kitchen']= 1;
        //朝向
        preg_match("/(东南|东北|西南|西北|南北)/u", $info, $toward);
        if(empty($toward[1])){
            preg_match("/(东|北|西|南)/u", $info, $toward);
        }
        $house_info['house_toward']= $toward[1];
        //面积
        preg_match("/(\d+\.?\d*)㎡/u", $info, $area);
        $house_info['house_totalarea']= $area[1];
        //装修
        preg_match("/(豪华装修|精装修|简单装修|毛坯)/u", $info, $fitment);
        $house_info['house_fitment']= $fitment[1];

        //小区名字
        preg_match("/小区名称([\x{0000}-\x{ffff}]*?)<\/a><\/span>/u", $html, $borough);
        $borough = trimall(strip_tags($borough[1]));
        $house_info['borough_name'] = $borough;
        //城区商圈
//         preg_match("/crumbs\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $city);
//         $city = explode("租房",trimall(strip_tags($city[1])));
        $house_info['cityarea_id'] = $city_tt[0];
        $house_info['cityarea2_id'] = $city_tt[1];

        $house_info['house_configpub'] = '';
    	//联系人姓名电话
        preg_match("/top_jl\"\>([\x{0000}-\x{ffff}]+?)<\/a><\/span><\/dd>/u",$html,$name);
    	$house_info['owner_name'] = strip_tags($name[1]);
        preg_match("/<ul><i\sclass=\"mai-ico\"><\/i>([\x{0000}-\x{ffff}]+?)<\/ul>/u",$html,$phone);
    	$house_info['owner_phone'] = $phone[1];
    	//付款类型
    	preg_match("/付款：([\x{0000}-\x{ffff}]*?)<\/td>/u", $details, $paytype);
    	$house_info['pay_type'] = trimall(strip_tags($paytype[1]));
    	//房源描述
    	preg_match("/房评：([\x{0000}-\x{ffff}]*?)更新&nbsp/u", $info, $desc);
    	$house_info['house_desc'] = str_replace('该房源暂无评论','',trimall(strip_tags($desc[1])));
    	//房源图片
//     	preg_match("/<div\sclass=\"all\_img\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $details, $pics);
//     	preg_match("/pika\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $pics[0], $pics);
//     	preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\"/u",$pics[1],$pic);
//     	$house_info['house_pic_unit']=implode('|',$pic[1]);
    	//图片无法区分是室内还是户型图，所以house_pic_out 为空
    	$house_info['house_pic_layout'] = '';
    	   	
    	$house_info['house_type'] = '';
    	$house_info['house_style'] = '';   	
    	$house_info['sex'] = '';
    	$house_info['into_house'] = '';
    	$house_info['comment'] = '';
    	$house_info['deposit'] = '';
    	$house_info['is_ture'] = '';
    	$house_info['friend_info'] = '';
    	$house_info['created'] = time();
    	$house_info['updated'] = time();
    	
    	$house_info['house_relet'] = '';
    	$house_info['app_url'] = '';
    	$house_info['wap_url'] = '';
    	$house_info['pub_time'] = '';
    	$house_info['chain_url'] = '';
    	$house_info['is_contrast'] = 2;
    	$house_info['is_fill'] = 2;
    	$house_info = array_merge($house_info);
    	$temp_url = $Pre_url[0];
    	
    	$house_info1 = \QL\QueryList::run('Request',[
    			'target' => $temp_url,
    			])->setQuery([

    					'deposit' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(4) > td:nth-child(2)', 'text', '-span', function($deposit){
    						return $deposit;
    					}],
//     					'tag' => ['.hc_left > table:nth-child(3) > tbody:nth-child(1) > tr:nth-child(5) > td:nth-child(2)', 'text', '-span', function($tag){
//     						return $tag;
//     					}],
    					'house_pic_unit' => ['#myGallery > li > img:nth-child(1)', 'src', '', function($house_pic_unit){
    						return $house_pic_unit;
    					}]])->getData(function($data) use($temp_url){
			//下架检测
//			$data['off_type'] = $this->is_off($source_url);
			return $data;
		});
    					
		foreach((array)$house_info1 as $key => $value){
			if(isset($house_info1[$key]['house_pic_unit'])){
				$house_pic_unit[] = $house_info1[$key]['house_pic_unit'];
			}
		}
		$house_info1[0]['house_pic_unit'] = implode('|', $house_pic_unit);
// 		if($house_info1[0]['tag']){
// 			var_dump(trimall($house_info1[0]['tag']));die;
// 			$house_info1[0]['tag']=str_replace(" ","#",trimall($house_info1[0]['tag']));
// 		}
// 		$temp=trimall($house_info1[0]['tag']);
// 		var_dump($temp);die;
// 		var_dump($house_info1[0]);die;
		$house_info['house_pic_unit']=$house_info1[0]['house_pic_unit'];
		$house_info['deposit']=$house_info1[0]['deposit'];
    	return $house_info;
    }
    //统计官网数据
    public function house_count(){
        $PRE_URL = 'http://www.maitian.cn/zfall';
        $totalNum = $this->queryList($PRE_URL, [
            'total' => ['.screening > p:nth-child(3) > span:nth-child(1)','text'],
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
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml','html',$url);
            if($newurl == $oldurl){
                $Tag = \QL\QueryList::Query($html,[
                    //"isOff" => [],
                    "isOff" => ['.upgrade','class',''],
//                     "404" => ['.upgrade','class',''],
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
    /**
     * 获取最新的房源种子
    * @param type $num 条数
    * @return type
    */
    public function callNewData($num = 941){
    	$url = 'http://bj.maitian.cn/zfall/OR32PG{$page}';
    	$data = [];
    	for($i = 1; $i <= $num; $i++){
    		$data[] = str_replace('{$page}', $i, $url);
    	}
    	return $data;
    }
}