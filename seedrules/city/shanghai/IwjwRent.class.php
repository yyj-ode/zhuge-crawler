<?php namespace shanghai;
/**
 * @description 上海爱屋吉屋整租房源抓取规则
 * @classname 上海爱屋吉屋整租
 */


Class IwjwRent extends \city\PublicClass{
    
    Public function house_page(){
        /*
         * @author jsx
         * 爱屋及屋用搜索条件的方式获取页面列表
         */
        $dis=array(12=>'浦东新区',3=>'黄浦',5=>'静安',6=>'徐汇',7=>'长宁',8=>'虹口',9=>'杨浦',
            10=>'闸北',11=>'普陀',13=>'宝山',14=>'闵行',15=>'嘉定',16=>'松江',17=>'青浦',
            18=>'奉贤',19=>'金山',20=>'崇明'
        );
        $rooms = array(1=>'一室',2=>'二室',3=>'三室',4=>'四室',5=>'四室以上');
        $url = array();
        foreach ($dis as $k => $v){
            foreach ($rooms as $m => $n){
                $url_tmp = 'http://www.iwjw.com/chuzu/shanghai/g1id'.$k.'rn'.$m;
                $html = file_get_contents($url_tmp);
                preg_match("/相关在租房源([\x{0000}-\x{ffff}]*?)套/u", $html, $total);
                $total = trimall(strip_tags($total[1]));
                $maxpage = ceil($total/30);
                for($page = 1; $page <= $maxpage; $page++){
                    $url[] = 'http://www.iwjw.com/chuzu/shanghai/g1id'.$k.'rn'.$m.'p'.$page;
                }
            }
        }
        return $url;
    }
    /*
     * 列表页
    */
    public function house_list($url){
        return \QL\QueryList::run('Request', [
			'target' => $url,
		])->setQuery([
			'link' => ['.f-l .info1 b a', 'href', '', function($content){
				return 'http://www.iwjw.com/'.$content;
			}],
		])->getData(function($item){
			return $item['link'];
		});
    }
    /*
     *获取详情页数据
    */
    public function house_detail($source_url) {
        //$source_url = "http://www.iwjw.com/chuzu/rqJ5VUsuTJk/?from=010101&p=2";
    	$html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
//        dump($html);
    	// 来源
    	$house_info['source'] = 6;
    	// 经济公司
    	$house_info['company_name'] = '爱屋吉屋';
    	 
    	//获取title标签
    	preg_match("/<title>([\x{0000}-\x{ffff}]*?)<\/title>/u", $html, $title);
    	$title_info = explode('_', $title[1]);
    	 
    	//标题
    	$house_info['house_title'] = str_replace('租房', '', $title_info[0]);
    	//小区名=标题！！！
    	$house_info['borough_name'] = $house_info['house_title'];
    	 
    	preg_match("/(\d\.?\d*)元/", $title_info[1], $price);
    	$house_info['house_price']=$price[1];
    	 
    	preg_match("/(\d+)室/", $title_info[2], $room);
    	preg_match("/(\d+)厅/", $title_info[2], $hall);
    	preg_match("/(\d+)卫/", $title_info[2], $toilet);
    	//室
    	$house_info['house_room'] = $room[1];
    	//厅
    	$house_info['house_hall'] = $hall[1];
    	//卫
    	$house_info['house_toilet'] = $toilet[1];
    	//厨房
    	$house_info['house_kitchen'] = 0;
    	 
    	 
    	preg_match("/(\d+\.?\d*)m²/", $title_info[3], $total);
    	//面积
    	$house_info['house_totalarea'] = $total[1];
    	 
    	//<meta name="description" content="该出租房简介：瑞和园，2000元/月，1室0厅1卫，20m²，中层 / 16层，简装，点击查看更多瑞和园小区租房信息">
    	preg_match("/<meta\s*name=\"description\" content=\"([\x{0000}-\x{ffff}]*?)\">/u", $html, $meta);
    	// print_r($meta);
    	preg_match("/(中|高|低)层/", $meta[1], $floor);
    	preg_match("/(\d+)层/", $meta[1], $topfloor);
    	//所在楼层
    	$house_info['house_floor']=$floor[1];
    	//总楼层
    	$house_info['house_topfloor']=$topfloor[1];
    	 
    	preg_match_all("/<div\s*class=\"item\-infos\">[\x{0000}-\x{ffff}]*?<\/div>/u", $html, $div);
    	$div = implode("<br>", $div[0]);
    	/*
    	 * 朝向和装修，朝向可能没有
    	*/
    	$fitment = trimall(strip_tags($div));
    	preg_match("/朝向：([\x{0000}-\x{ffff}]*?)南北通风/u", $fitment, $toward);
    	$house_info['house_toward'] = ($toward[1] == '—') ? '' : $toward[1];
    	preg_match("/装修\：([\x{0000}-\x{ffff}]*?)发布日期/u", $fitment, $fit);
        $house_info['house_fitment'] = $fit[1];
    	$house_info['borough_id'] = '';
    	
    	//城区商圈
    	preg_match ( "/区域板块：([\x{0000}-\x{ffff}]*?)<\/span>/u", $html, $city_info);
    	$city = trimall(strip_tags($city_info[1]));
    	$city = explode('-', $city);
    	$house_info['cityarea_id'] = $city [0];
    	$house_info['cityarea2_id'] = $city [1];
    	
    	//图片，无房型图
    	preg_match( "/sellYoukuplayer[\x{0000}-\x{ffff}]+?span/u", $html, $ul);
    	preg_match_all("/data-src=\"(\S+?)\"/", $ul[0], $img);
    	$pic = array();
    	foreach($img[1] as $k=>$v){
    		$pic[] = $v;
    	}
    	$pic = array_unique($pic);
    	 
    	//室内图
    	$house_info['house_pic_unit'] = implode("|", $pic);
    	//房型图
    	$house_info['house_pic_layout'] = "";
    	//付款方式
    	$house_info['pay_method'] = '';
    	//付款类型
    	$house_info['pay_type'] = '';
    	//标签（房源特色）
    	$house_info['tag'] = '';
    	//房源评价
    	$house_info['comment'] = '';
    	
    	$house_info['deposit'] = '';
    	$house_info['is_ture'] = '';
    	$house_info['house_config'] = '';
    	$house_info['created'] = time();
    	$house_info['updated'] = time();
    	$house_info['friend_info'] = '';
    	 
    	$house_info['house_desc'] = '';
    	$house_info['house_type'] = '';
    	 
    	$house_info['owner_name'] = '';
    	$house_info['owner_phone'] = '';
    	$house_info['sex'] = '';
    	$house_info['wap_url'] = 0;
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
//                    "isOff" => ['a.sellBtnView1','class',''],
                    "404" => ['.img-404','class',''],
                    "rent" => ['a.sellBtnView1:nth-child(3)','text','',function($rent){
                        preg_match("/已租出/",$rent,$is_rent);
                        return $is_rent[0];
                    }]
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]['404']==NULL && $Tag[0]['rent']==NULL){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }
}