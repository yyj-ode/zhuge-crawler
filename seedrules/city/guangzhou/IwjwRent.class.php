<?php namespace guangzhou;
/**
 * @description 广州爱屋吉屋整租房抓取规则
 * @classname 广州爱屋吉屋（k-ok）
 */


Class IwjwRent extends \city\PublicClass  {
    Public function house_page(){
        /*
         * @author jsx
        * 爱屋及屋用搜索条件的方式获取页面列表
        */
        $dis=array(1=>'白云区',2=>'荔湾区',3=>'越秀区',4=>'海珠区',5=>'天河区',6=>'番禺区',7=>'花都区',8=>'黄埔区',9=>'南沙区');
        /*
         * 参数
         * $d：表示的是选择的城区，需要编码
         * $a：表示选择的装修类型
         * $pr：表示选择的价格
         * $r：表示室数目的选择
         */

        $urlarr = [];
       foreach($dis as $index){
           for($a = 1; $a <=4 ; $a ++) {
               for($r = 1; $r <= 5; $r ++) {
                   for($pr = 1; $pr <= 7; $pr ++) {
                       //搜索条件，从第一页拿到最大标签页
                       $condition = "http://www.iwjw.com/chuzu/guangzhou/ip".$pr."rn".$r."dt".$a."/?kw=".urlencode($index);
                       echo $condition."\r\n";
                       $html = getSnoopy($condition);
                       //判断是否有搜索结果
                       preg_match("/<div\sclass=\"no\-content\">/u", $html, $content);
                       //结果为空直接跳出本循环
                       if($content){continue;}
                       // 拿到检索的最大页面数目$maxPage
                       preg_match("/<p\s*class=\"Page\">[\x{0000}-\x{ffff}]*?<\/p>/u", $html, $pages);
                       preg_match_all("/>(\d+?)</", $pages[0], $maxPage);
                       // $max表示当前条件下最大的页面数最大的页面数
                       if (! empty ( $maxPage[1] )) {
                           $max = $maxPage [1] [count ( $maxPage [1] ) - 1];
                       } else {
                           $max = 1;
                       }
                       unset ( $html );
                       //放置当前搜索条件下的所有列表页
                       $page_list = array ();
                       for($i = 1; $i <= $max; $i ++) {
                           $urlarr [] = "http://www.iwjw.com/chuzu/guangzhou/ip" . $pr . "rn" . $r . "dt" . $a . "p" . $i . "/?kw=" . urlencode ( $index);
                       }
                   }
               }
           }
       }
        return $urlarr;
    }
    /*
     * 列表页
    */
    public function house_list($url){
        $html = $this->getUrlContent ( $url );
        //取得列表标签
        preg_match ( "/<ol([\x{0000}-\x{ffff}]*?)<\/ol>/u", $html, $ol );
        preg_match_all ( "/<li([\x{0000}-\x{ffff}]*?)<\/li>/u", $ol [1], $lis );
        //$$house_info = array ();
        //取得列表页的的链接
        foreach ( $lis [1] as $k => $v ) {
            //url
            preg_match ( "/href=\"([\x{0000}-\x{ffff}]*?)\"/u", $v, $href );
            $house_info[]  = "http://www.iwjw.com".$href [1];
        }
        return $house_info;
		//dump($this->house_info);die;
    }
    /*
     *获取详情页数据
    */
    public function house_detail($source_url) {
    	$html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
    	// 经济公司
    	$house_info ['company_name'] = '爱屋吉屋';
    	//获取title标签
    	preg_match("/<title>([\x{0000}-\x{ffff}]*?)<\/title>/u", $html, $title);
    	$title_info = explode('_', $title[1]);
//     	    	print_r($title_info);die;

    	//标题
    	$house_info ['house_title'] = str_replace('租房', '', $title_info[0]);
    	//小区名=标题！！！
    	$house_info ['borough_name'] = $house_info ['house_title'];

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
    	preg_match("/(中|高|低)层/", $meta[1], $floor);
    	preg_match("/(\d+)层/", $meta[1], $topfloor);
    	//所在楼层
    	$house_info['house_floor']=$floor[1];
    	//总楼层
    	$house_info['house_topfloor']=$topfloor[1];

    	preg_match_all("/<div\s*class=\"item\-infos\">[\x{0000}-\x{ffff}]*?<\/div>/u", $html, $div);
    	$div = implode("<br>", $div[0]);
    	preg_match_all("/class=\"pname\">[\x{0000}-\x{ffff}]*?<\/p>/u", $html, $listinfo);
    	//print_r($listinfo[0]);die;
    	$listinfo = $listinfo[0];
    	/*
    	 * 朝向和装修，朝向可能没有
    	*/
    	$toward = str_replace(['</i>', '</p>','class="pname">','朝向：'], '', $listinfo[4]);
    	$house_info['house_toward'] = trimall($toward);
//     	print_r($house_info);die;
    	$fitment = str_replace(['</i>', '</p>','class="pname">','装修：'], '', $listinfo[6]);
    	$house_info ['house_fitment'] = trimall($fitment);

    	//建造年代
    	preg_match('/建造年代：([\x{0000}-\x{ffff}]*?)年/u', $html, $year);
    	$house_info['house_built_year'] = strip_tags(trimall($year[1]));
    	//小区id,城区商圈
    	$house_info['borough_id'] = '';
    	//preg_match ( "/<a\s*class=\"detail\-more[\x{0000}-\x{ffff}]*?<\/div>/u", $html, $city_info);
    	//preg_match_all("/<p>[\x{0000}-\x{ffff}]*?<\/p>/u", $html, $p);
    	//preg_match("/<\/i>([\x{0000}-\x{ffff}]*?)<\/span>/u", $p[0][5], $city);
    	//$city = explode('-', $city[1]);
    	//$house_info ['cityarea_id'] = str_replace("区","",trim ( $city [0] ));
    	//$house_info ['cityarea2_id'] = trim ( $city [1] );
    	preg_match("/区域板块：([\x{0000}-\x{ffff}]+?)span>/u",$html, $city);
    	$city = explode('-', $city[1]);
    	$house_info ['cityarea_id'] = str_replace("区","",trim ( strip_tags($city [0])) );
    	$house_info ['cityarea2_id'] = trim ( strip_tags($city [1]) );

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