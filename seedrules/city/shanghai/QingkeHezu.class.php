<?php namespace shanghai;
/**
 * @description 上海青客 合租房抓取规则
 * @classname 上海青客 OK
 */

class QingkeHezu extends \city\PublicClass {
    Public function house_page() {
        $urlarr =array();
        $url = 'http://www.qk365.com/list/f3';
        $html = $this->getUrlContent($url);
        preg_match("/总共([\x{0000}-\x{ffff}]*?)间房间/u", $html, $houses);
        $maxPage = intval(ceil($houses[1]/9));
        for($page = 1; $page <= $maxPage; $page ++) {
            $urlarr[] = "http://www.qk365.com/list/f3-p".$page;
        }
        return $urlarr;
    }
	/*
	 * 列表页
	 */
	public function house_list($url){
        $house_info = array();
		$html = $this->getUrlContent($url);
		preg_match("/<div\s*class=\"easyWarp[\x{0000}-\x{ffff}]*?easyPage\">/u", $html, $houseList);
		preg_match_all("#http://www\.qk365\.com/room/[\d]*#", $houseList[0], $hrefs);
		$count = count($hrefs[0]);
		for($i = 0; $i < $count; $i +=2){
			$house_info[$i] = $hrefs[0][$i];
		}
		$house_info = array_merge($house_info);
        return $house_info;
	}
	/*
	 *获取详情页数据 
	 */
	public function house_detail($url) {
		$html = $this->getUrlContent($url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($url,$html);
		preg_match("/description\"\scontent\=\"\"\s\/>([\x{0000}-\x{ffff}]*?)<\/title>/u", $html, $Pre_title);
        preg_match("/<title>([\x{0000}-\x{ffff}]*?)<\/title>/u", $Pre_title[0], $title);
		//普通住宅水岸双桥4800元精装修90.0㎡南北2室1厅1厨1卫出租-距 双桥站 步行3分钟-丁丁租房网
        //标题
        $house_info['house_title']=$title[1];
        preg_match("/houInfoTit([\x{0000}-\x{ffff}]*?)<\/dl>/u", $html, $info2);
		$info1=strip_tags($info2[1]);
		$info1 = str_replace(array("\t", "\n", "\r", " "),"", $info1);
		//价格
		preg_match("/租金\：(\d+\.?\d*)元\/月/u", $info1, $price);
		$house_info['house_price']= $price[1];
		//房源编号
		preg_match("/房间编号\：([\x{0000}-\x{ffff}]*?)面积/u", $info1, $number);
		$house_info['house_number'] = $number[1];
		//出租间面积
		preg_match("/面积\：(\d+\.?\d*)M²/", $info1, $area);
		$house_info['house_room_totalarea']= $area[1];
		//朝向
		preg_match("/房屋概况\：朝([\x{0000}-\x{ffff}]*?)层/u", $info1, $toward);
		$toward=explode('-',$toward[1]);
		$house_info['house_toward']= $toward[0];
		//楼层
		$f=explode('/',$toward[1]);
		$house_info['house_floor']= $f[0];
		$house_info['house_topfloor'] = $f[1];
		//城区
		preg_match("/所在区域\：([\x{0000}-\x{ffff}]*?)小区名称：/u", $info1,$city);
		preg_match("/(嘉定|闵行|浦东|宝山|松江|青浦|徐汇|闸北|杨浦|静安|黄埔|普陀|虹口|长宁|金山|奉贤|崇明|卢湾)/u", $city[1], $cityarea);
		$house_info['cityarea_id'] = $cityarea[0];
		//商圈
		$house_info['cityarea2_id'] = str_replace($cityarea[0],"",$city[1]);
		//小区名字
		preg_match("/小区名称\：([\x{0000}-\x{ffff}]*?)<\/a>/u", $info2[1],$borough);
		$house_info['borough_name'] = trimall(strip_tags($borough[1]));
		//装修
		$house_info['house_fitment']= '';
		//卧室/居
        preg_match_all("/\w+房间/",$html,$room);
		$house_info['house_room']= count(array_unique($room[0]));
		//厅
		$house_info['house_hall']= '';
		//厨房
		$house_info['house_kitchen'] = '';
		//卫生间
		$house_info['house_toilet']= '';
		//类型
		$house_info['house_type'] = '';
        //联系人
        preg_match("/stewPhotName\">([\x{0000}-\x{ffff}]*?)<\/a>/u", $html,$owner_name);
        $house_info['owner_name'] = trimall(strip_tags($owner_name[1]));
        //400电话
        preg_match("/houPhone\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html,$service_phone);
        $house_info['service_phone'] = $service_phone[1];
		//图片
		preg_match("/houseboxList([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $Pre_imgs);
        preg_match_all("/src\=\"([\x{0000}-\x{ffff}]*?)\"/u", $Pre_imgs[0], $imgs);
		$house_info['house_pic_unit'] = array();
		foreach($imgs[1] as $k=>$v){
			$house_info['house_pic_unit'][] = $v;
        }
		$house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
		$house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
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
		$house_info['source_url'] = $url;
		return $house_info;
	}
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            preg_match("/chuzuHover/",$html,$is_off);
            if ($is_off){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}
