<?php namespace shanghai;
/**
 * @description 上海寓见 合租房抓取规则
 * @classname 上海寓见合租 OK
 */
class YujianHezu extends \city\PublicClass{
    Public function house_page() {
        $minPage=empty($_GET['minPage'])?1:$_GET['minPage'];
        $maxPage = empty($_GET['maxPage'])?28:$_GET['maxPage'];
        $urlarr = array();
        for($page = $minPage; $page <=$maxPage; $page ++) {
            $urlarr[] = "http://www.yujiangongyu.com/shanghai/list.htm?district=&street=&qujian=0&minPrice=0&maxPrice=-1&pageIndex=".$page;
        }
        return $urlarr;
    }
	/*
	 * 列表页
	 */
	public function house_list($url){
		$html = $this->getUrlContent($url);
        $house_info = array();
		preg_match("/<div\s*class=\"normal-start\s*mb70[\x{0000}-\x{ffff}]*?pure-g\s*mt15\s*page\">/u", $html, $houseList);
		//dump($houseList);die;
		preg_match_all("#http://www.yujiangongyu.com/shanghai/[\d]*.htm#", $houseList[0], $hrefs);
		//dump($hrefs);die;
		$count = count($hrefs[0]);
		for($i = 0; $i < $count; $i ++){
            $house_info[] = $hrefs[0][$i];
		}
        return $house_info;
	}
	
	/*
	 *获取详情页数据 
	 */
	public function house_detail($source_url) {
        //$source_url = "http://www.yujiangongyu.com/shanghai/64818.htm";
        $house_info = [];
        $html = $this->getUrlContent($source_url);
        $house_info['source'] = 15;
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url);
        //房源编号
        preg_match("/<span\s*class=\"title pl20\">([\x{0000}-\x{ffff}]*?)<\/span>/u", $html, $number);
        $number=str_replace('NO.SH-','',$number[1]);
        $house_info['house_number'] = $number;
        preg_match("/<div\s*class=\"room-info-buy\">([\x{0000}-\x{ffff}]*?)<div\s*class=\"center\s*pt15\">/u", $html, $info);
        $info=strip_tags($info[0]);
        $info = str_replace(array("\t", "\n", "\r", " ","&nbsp;"),"", $info);
        
        preg_match("/合租/u",$info,$type);
        if(!empty($type)){            
            //价格
            preg_match("/￥([\x{0000}-\x{ffff}]*?)元\/月/u", $info, $price);
            $house_info['house_price']= $price[1];
            preg_match("/房屋概况：([\x{0000}-\x{ffff}]*?)层/u", $info, $atrf);
            $arr_atrf=explode('-',$atrf[1]);
            //出租间面积
            $area=str_replace("m²","",$arr_atrf[0]);
            $house_info['house_room_totalarea']= $area;
            //朝向
            $toward=str_replace("朝","",$arr_atrf[1]);
            $house_info['house_toward']= $toward;
            //卧室/居
            $room=str_replace("室","",$arr_atrf[2]);
            $house_info['house_room']= $room;
            //厅
            $house_info['house_hall']= '';
            //卫
            $house_info['house_toilet'] = '';
            //厨房
            $house_info['house_kitchen'] = '';
            //楼层
            $arr_f=explode('/',$arr_atrf[3]);
            $house_info['house_floor']= $arr_f[0];
            $house_info['house_topfloor'] = $arr_f[1];
            preg_match("/地址：([\x{0000}-\x{ffff}]*?)(类型|公寓类型：)/u", $info, $address);
            $arr_address=explode('-',$address[1]);
            //城区
            $house_info['cityarea_id'] = str_replace(array("新区","区"),"",$arr_address[0]);
            //商圈
            $house_info['cityarea2_id'] = $arr_address[1];
            //小区名字
            $house_info['borough_name'] = $arr_address[2];
            //标题
            $house_info['house_title']=$arr_address[2];
            //类型
            $house_info['house_type'] = '';
            //装修
            $house_info['house_fitment']= '';
            //卫生间
            $house_info['house_toilet']= '';
            //图片
            preg_match("/<div\s*class=\"room-info-head-imgs\">([\x{0000}-\x{ffff}]*?)<div\s*class=\"room-info-head-imgs-small\">/u", $html, $pics);
            preg_match_all("/http:([\x{0000}-\x{ffff}]*?)\/800/u", $pics[0], $imgs);
            $house_info['house_pic_unit'] = array();
            foreach($imgs[0] as $k=>$v){
            	$house_info['house_pic_unit'][] = $v;
            }
            $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
            $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
            //联系人
            $house_info['owner_name'] = '';
            $house_info['owner_phone'] = '4006369090';//官网电话
            //入住人限制
            $house_info['sex']= "";
            //入住时间
            $house_info['into_house']= "";
            //付款方式 例如信用卡
            $house_info['pay_method']= "";
            //付款类型 例如 押一付三
            $house_info['pay_type']= "";
            //标签(房源特色)
            $house_info['tag']= "";
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
            //dump($house_info);die;
        }
        else{
            unset($house_info);
        }
        return $house_info;
    }
    //下架判断
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            if(preg_match("/未出租/", $html)){
                return 2;
            }elseif(preg_match("/error/", $html)){
                return 1;
            }else{
                return 1;
            }
        }else{
            return 1;
        }
    }
}
