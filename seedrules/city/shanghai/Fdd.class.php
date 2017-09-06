<?php namespace shanghai;
/**
 * @description 上海房多多二手房抓取规则
 * @classname 上海房多多
 */


Class Fdd  extends \baserule\Fdd
{
    public $PRE_URL = 'http://esf.fangdd.com/shanghai';
//    /*
//    * 抓取
//    */
//    Public function house_page(){
//
//        $PRE_URL = 'http://esf.fangdd.com/shanghai';
//        //获取搜索条件
//       $urlList = $this->get_condition($PRE_URL);
//        $urlarr = [];
//        foreach($urlList as $url){
//            //从当前条件首页抓取最大页
//            $maxPage = $this->get_maxPage($url);
//			echo $url.PHP_EOL;
//            for ($page=1; $page<=$maxPage; $page++){
//                $urlarr[] = $url.'_pa'.$page;
//            }
//        }
//        return $urlarr;
//    }
//
//    /*
//     * 获取列表页
//    * @param	dis string 分城区抓取设置为城区信息
//    */
//    public function house_list($url){
//    	$html=$this->getUrlContent($url);
//    	//标题和链接
//    	preg_match("/<div\sclass=\"main\sclearfix\">([\x{0000}-\x{ffff}]*?)<div\sclass=\"page\-pagination\"\>/u", $html, $list);
//    	//价格
//    	preg_match_all("/\"btn\"\shref=\"([\x{0000}-\x{ffff}]*?)\"/u", $list[0], $link);
//    	$house_info=array();
//    	foreach($link[1] as $i => $v){
//    		//链接地址
//    		$house_info[]=$v;
//    	}
//    	return array_merge($house_info);
//    	//dump($this->house_info);die;
//    }
//
//    /*
//     * 获取详情
//    */
//    public function house_detail($source_url){
//    	$html = $this->getUrlContent($source_url);
//
//        $houseInfo = [];
//        \QL\QueryList::Query($html,[
//            'house_title' => ['.house__name', 'text'],
//            'borough_name' => ['.house__name > .tit:eq(0)', 'text'],
//            'house_price' => ['var.price', 'text'],
//            'cityarea_id' => ['.address > a:eq(0) ', 'text'],
//            'cityarea2_id' => ['.address > a:eq(1) ', 'text'],
//            'home_hall' => ['.house__name > .tit:eq(1)', 'text'],
//            'house_totalarea' => ['.house__name > .tit:eq(2)', 'text'],
//            'floor' => ['.house__detail .table tr:eq(0) > td:eq(1)', 'text'],
//            'house_built_year' => ['.house__detail .table tr:eq(0) > td:eq(2)', 'text'],
//            'house_type' => ['.house__detail .table tr:eq(1) > td:eq(0)', 'text'],
//            'house_id' => ['.house__detail .table tr:eq(1) > td:eq(2)', 'text'],
//            'owner_name' => ['.ownername', 'text'],
//            'house_pic_unit' => ['.thumbnail__item__container > img', 'src'],
//            'house_desc' => ['#owner-say-content', 'text', '-span'],
//
//        ])->getData(function($data)use(&$houseInfo){
//            isset($data['borough_name']) && $houseInfo['borough_name'] = $data['borough_name'];
//            isset($data['house_price']) && $houseInfo['house_price'] = $data['house_price'];
//            isset($data['cityarea_id']) && $houseInfo['cityarea_id'] = $data['cityarea_id'];
//            isset($data['cityarea2_id']) && $houseInfo['cityarea2_id'] = $data['cityarea2_id'];
//            if(isset($data['house_title'])){
//                $houseInfo['house_title'] = preg_replace( '/[\r\n\s\t]/', '', $data['house_title']);
//            }
//            if($data['home_hall']){
//                $homeHall = preg_split('/室|厅|卫/', trim($data['home_hall']));
//                $houseInfo['house_room'] = getValue($homeHall, 0, 0);
//                $houseInfo['house_hall'] = getValue($homeHall, 1, 0);
//                $houseInfo['house_toilet'] = getValue($homeHall, 2, 0);
//            }
//
//            if($data['house_totalarea']){
//                preg_match('/^(\d+(\.\d+)?)/', trim($data['house_totalarea']), $match);
//                $houseInfo['house_totalarea'] = $match[1];
//            }
//
//            if($data['floor']){
//                preg_match('/(\d+\/\d)/', trim($data['floor']), $match);
//                $floor = explode('/', $match[1]);
//                $houseInfo['house_floor'] = $floor[0];
//                $houseInfo['house_topfloor'] = $floor[1];
//            }
//
//            if($data['house_built_year']){
//                preg_match('/\d+/', trim($data['house_built_year']), $match);
//                $houseInfo['house_built_year'] = $match[0];
//            }
//
//            if($data['house_type']){
//                $houseInfo['house_type'] = str_replace(["--", '房型：'],"",$data['house_type']);
//            }
//
//            if($data['house_id']){
//                $houseInfo['house_id'] = str_replace(['房源编号：'],"",$data['house_id']);
//            }
//
//            if(isset($data['owner_name'])){
//                $houseInfo['owner_name'] =  ltrim(trim($data['owner_name']), '业主');
//            }
//
//            $data['house_pic_unit'] && $houseInfo['house_pic_unit'][] =  $data['house_pic_unit'];
//            $data['house_desc'] && $houseInfo['house_desc'] =  $data['house_desc'];
//        });
//
//        //下架检测
////        $house_info['off_type'] = $this->is_off($source_url,$html);
//
//        //朝向
//        $houseInfo['house_toward'] = '';
//
//    	//装修情况
//        $houseInfo['house_fitment'] = '';
//
//    	//发布人电话
//        $houseInfo['owner_phone'] = "";
//
//    	//房源图片
//        array_pop( $houseInfo['house_pic_unit']);
//        $houseInfo['house_pic_layout']=$houseInfo['house_pic_unit'][5];
//        $houseInfo['house_pic_unit'] = implode('|', $houseInfo['house_pic_unit']);
//
//    	//来源
//        $houseInfo['source'] = '14';
//        $houseInfo['company_name']='房多多';
//    	//创建时间
//        $houseInfo['created']= time();
//    	//更新时间
//        $houseInfo['updated']= time();
//
//        $houseInfo['tag'] = $this->getTags($html);
//        $houseInfo['content'] = $html;
//    	return $houseInfo;
//    }
//
//
//	/*
//	 * 获取各类搜索条件
//	 */
//	Public function get_condition($PRE_URL){
//	    $html = $this->getUrlContent($PRE_URL);
//	    //城区搜索条件
//	    preg_match_all('/shanghai\/list\/(s\d+)\">/u',$html,$dis);
//	    $Dis = array_merge(array_unique($dis[1]));
//	    //价格搜索条件
//	    preg_match_all('/shanghai\/list\/(co\d+\-\d+)\">/u',$html,$price);
//	    $Price = array_merge(array_unique($price[1]));
//	    //房型搜索条件
//	    preg_match_all('/shanghai\/list\/(r\d)\">/u',$html,$room);
//	    $Room = array_merge(array_unique($room[1]));
//	    $url_list = array();
//	    foreach($Dis as $DIS){
//	        foreach($Price as $PRICE){
//	            foreach($Room as $ROOM){
//	                $url_list[] = $PRE_URL."/list/".$DIS.'_'.$PRICE.'_'.$ROOM;
//	            }
//	        }
//	    }
//        return $url_list;
//	}
//
//	/*
//	 * 获取搜索条件下的最大页
//	 */
//	Public function get_maxPage($url){
//	    $html = getSnoopy($url);
//	    preg_match('/\s*(\d+)\s*<\/span>\s*套房源/u',$html,$page);
//	    $maxPage = ceil($page[1]/20);
//	    //如果最大页抓空，返回1
//	    if(!empty($maxPage)){
//	        return $maxPage;
//	    }else{
//	        return 0;
//	    }
//	}
//    public function is_off($url,$html=''){
//        if(!empty($url)){
//            if(empty($html)){
//                $html = $this->getUrlContent($url);
//            }
//            //抓取下架标识
//            $off_type = 1;
//            $newurl = get_jump_url($url);
//            if($newurl == $url){
//                $Tag = \QL\QueryList::Query($html,[
//                    "isOff" => ['.house-status > img:nth-child(2)','src',''],
//                    "404" => ['#container > h1:nth-child(1)','text',''],
//                ])->getData(function($item){
//                    return $item;
//                });
//                if($Tag[0]['isOff'] == null && $Tag[0]['404'] == null ){
//                    $off_type = 2;
//                }
//            }
//            return $off_type;
//        }
//        return -1;
//
//    }
//
//    /*
//     * 抓取房源对应标签
//     */
//    public function getTags($html){
//
//        $tags = [];
//        \QL\QueryList::Query($html,[
//            'tag' => ['.tag-div > span', 'text']
//        ])->getData(function($item)use(&$tags) {
//            $item['tag'] && $tags[] = $item['tag'];
//            return $item;
//        });
//
//        return implode("#",$tags);
//    }
//
//    /**
//     * 获取最新的房源种子
//     * @author robert
//     * @return type
//     */
//    public function callNewData(){
//        $areaArr = [
//            'sp0-50',
//            'sp50-70',
//            'sp70-90',
//            'sp90-120',
//            'sp120-150',
//            'sp150-200',
//            'sp200-300',
//            'sp300-999999',
//        ];
//        $data = [];
//        foreach($areaArr as $area){
//            for($i = 1; $i <= 20; $i++){
//                $data[] = "http://esf.fangdd.com/shanghai/list/t%E6%9C%80%E8%BF%91%E6%96%B0%E4%B8%8A_pa{$i}_{$area}";
//            }
//        }
//        return $data;
//    }
}