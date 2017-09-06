<?php namespace shanghai;
/**ok
 * @description 上海丁丁整租租房抓取规则
 * @classname 上海丁丁整租
 */
//http://mbs.zufangzi.com/gms/order/orderApiController/createOrder.do
//http://userapp.iwjw.com:80/ihouse/user/myAgent.rest
class DingdingRent extends \city\PublicClass{
    
    Public function house_page(){
        /*
         * http://sh.zufangzi.com/subway/0-22220
         * http://sh.zufangzi.com/subway/0-22220/2/
         * http://sh.zufangzi.com/subway/0-22220/3/
         */
        $minPage=$_GET['minPage'];
        $maxPage=$_GET['maxPage'];
        $minPage=empty($minPage)?1:$minPage;
        $maxPage=empty($maxPage)?222:$maxPage;
        $url_pre = 'http://sh.zufangzi.com/area/0-10000000000/';
        $url = [];
        for($page = 1;$page <= $maxPage ;$page++){
            $url[] = $url_pre.$page;
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
                    'link' => ['.row .col-lg-5 h2 a', 'href', '', function($content){
                            return $content;
                    }],
            ])->getData(function($item){
                    return $item['link'];
            });
    }
        
	/*
	 *获取详情页数据 
	 */
	public function house_detail($source_url) {
		$html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
		preg_match("/last-child\"><h1>\s*([\x{0000}-\x{ffff}]*?)<\/h1><\/li>/u", $html, $info);
        $info = trimall($info);
		//普通住宅玉兰花苑6500元精装修68平南1室1厅1卫0单间房屋出租
//		dump($info);
        //类型:在标题中可以截取
        preg_match("/(普通住宅|别墅|写字楼|公寓)/u", $info[1], $house_type);
        $house_info['house_type'] = $house_type[1];
//        dump($house_type[1]);
        //装修
        preg_match("/(精装修|简装|毛坯)/u", $info[1], $fitment);
//        dump($fitment[1]);
		$house_info['house_fitment']= $fitment[1];
		//出租间面积
		preg_match("/(\d+\.?\d*)平/", $info[1], $area);
//        dump($area[1]);
		$house_info['house_totalarea']= $area[1];
		//朝向
		preg_match("/(东|西|南|北|东南|东北|西南|西北|南北)/", $info[1], $toward);
//        dump($toward[1]);
        $house_info['house_toward']= $toward[1];
		//室厅卫
		preg_match("/(\d+?)室/u", $info[1], $room);
//        dump($room[1]);
		//卧室/居
		$house_info['house_room']= $room[1];
		//厅
		preg_match("/(\d+?)厅/u", $info[1], $hall);
//        dump($hall[1]);
		$house_info['house_hall']= $hall[1];
		//厨房：这个字段其实可以不要或者默认为1
		preg_match("/(\d+?)厨/u", $info[1], $kitchen);
//        dump($kitchen[1]);
		$house_info['house_kitchen'] = $kitchen[1];
		//卫生间
		preg_match("/(\d+?)卫/u", $info[1], $toilet);
//        dump($toilet[1]);
		$house_info['house_toilet']= $toilet[1];
		//价格
		preg_match("/(\d+\.?\d*)元/u", $info[1], $price);
//        dump($price[1]);
		$house_info['house_price']= $price[1];
		//详细信息
//		preg_match("/<div\s*class=\"xqyC1R_3[\x{0000}-\x{ffff}]*?div>/u", $html, $detail);
//		preg_match_all("/<p[\x{0000}-\x{ffff}]*?p>/u", $detail[0], $pps);
		//preg_match("/<p\s*class=\"louceng[\x{0000}-\x{ffff}]*?p>/u", $detail[0], $floor);
		preg_match("/(高|中|低)层/", $html, $f);
		preg_match("/共(\d+?)层/", $html, $ft);
		//所在楼层
//        dump($f[1]);
		$house_info['house_floor']= $f[1];
		//总楼层
//        dump($ft[1]);
        $house_info['house_topfloor'] = $ft[1];
		//小区名字
		preg_match("/小区\：([\x{0000}-\x{ffff}]*?)<\/a>/u", $html, $borough);
		$borough = trimall(strip_tags($borough[1]));
//        dump($borough);
		$house_info['borough_name'] = $borough;
		//标题
		$house_info['house_title'] = $borough;
		//通过API抓取城区商圈
		//抓取经纬度
		preg_match('/lngHidder\"\s*value\=\"(\d+\.?\d*)\">/u',$html,$lng);//经度
        preg_match('/latHidder\"\s*value\=\"(\d+\.?\d*)\">/u',$html,$lat);//纬度
//        dump($lng);dump($lat);
		$Map = file_get_contents("http://api.map.baidu.com/geocoder/v2/?location=".$lat[1].",".$lng[1]."&output=json&ak=aqLgbABLabxT9csGOEhrjDFM");
		$map = json_decode($Map,1);
		$cityarea_id = str_replace("区","",$map['result']['addressComponent']['district']);
		$cityarea2_id = explode(",",$map['result']['business'])[0];
//        dump($cityarea_id);dump($cityarea2_id);
		$house_info['cityarea_id'] = $cityarea_id;
		$house_info['cityarea2_id'] = $cityarea2_id;
		//房源编号:没有该参数！
//		preg_match("/(PEK[\w\-]*?)\)/", $pps[0][7], $number);
		$house_info['house_number'] = NULL;
		//图片
		preg_match("/house-pics-slider\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $pic_div);
		preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\"/u", $pic_div[0], $src);
		$house_info['house_pic_unit'] = array();
		foreach($src[1] as $k=>$v){
			$house_info['house_pic_unit'][] = $v;
		}
		$house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
		$house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
//        dump($house_info['house_pic_unit']);die;
		//入住人限制
		$house_info['sex']= "";
		//入住时间
		$house_info['into_house']= "";
		//付款方式 例如信用卡
		$house_info['pay_method']= "";
		//付款类型 例如 押一付三
		$house_info['pay_type']= "";
		//标签(房源特色)
        preg_match("/房东说<\/a>\s*<\/div>([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $Pre_tag);
        preg_match_all("/class\=\"tag\">([\x{0000}-\x{ffff}]*?)<\/span>/u", $Pre_tag[1], $tag);
        foreach($tag[1] as $k=>$v){
            $house_info['tag'][] = $v;
        }
        $house_info['tag'] = array_unique($house_info['tag']);
        $house_info['tag'] = implode('|', $house_info['tag']);
//        dump($house_info['tag']);
		//房源评价
		$house_info['comment']= "";
		//押金
		$house_info['deposit']= "";
		//合租户数
		$house_info['homes']= "";
		//真实度
		$house_info['is_ture']= "";
		//室友信息
		$house_info['friend_info']= "";
		//创建时间
		$house_info['created']= time();
		//更新时间
		$house_info['updated']= time();
		$house_info['house_configpub'] ="";
		$house_info['is_contrast'] = 2;
		$house_info['is_fill'] = 2;
		$house_info['source_url'] = $source_url;
		return $house_info;
//        dump($house_info);die;
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
                    "isOff" => ['div.btn','text','',function($is_off){
                        return preg_match("/已下架/",$is_off);   //下架返回1，未下架返回0
                    }],
                    "404" => ['.error-title','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]["isOff"]== 0 && $Tag[0]["404"]==NULL){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }
}
