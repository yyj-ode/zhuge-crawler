<?php namespace shenzhen;
/**
 * @description 深圳58同城业主个人 合租房抓取规则
 * @classname 深圳58同城业主个人(k-ok)
 */
Class Five8PersonalHezu extends \city\PublicClass{

	public function house_page(){
		//城区名称  分城区抓取 计划任务记得传参数(城区拼音，抓取页数)
		//examplde :http://localhost/spider/Sell/Five8Personal/fetch?dis=haidian&page=20
        $cityarea_name = array(
            '罗湖',
            '福田',
            '南山',
            '盐田',
            '宝安',
            '龙岗区',
            '布吉',
            '坪山新区',
            '光明新区',
            '龙华新区',
            '大鹏新区',
            '深圳周边'
        );
        $dis = array(
            'luohu',
            'futian',
            'nanshan',
            'yantian',
            'baoan',
            'longgang',
            'buji',
            'pingshanxinqu',
            'guangmingxinqu',
            'szlhxq',
            'dapengxq',
            'shenzhenzhoubian'
        );
	    $index = empty ( $_GET ['dis'] ) ? 0 : $_GET ['dis']; // 城区拼音
		$maxPage = empty ($_GET['maxPage']) ? 70 : $_GET['maxPage'];//要抓取的页数
        $urlarr = array();
		$flag_page = 0;
		for($page= 1;$page<=$maxPage;$page++){
		    if($flag_page == 0){
		        $page = empty ( $_GET ['page'] ) ? 1 : $_GET ['page'];
		        $flag_page = 1;
		    }
            $urlarr[] = 'http://m.58.com/'.$dis[$index].'/hezu/0/pn'.$page.'?refrom=pcfront';
		}
        return $urlarr;
	}
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html=$this->getUrlContent($url);
        $house_info = array();
		preg_match("/<ul\s*class=\"list-info\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $out);
// 		var_dump($out[0]);exit;
		preg_match_all("/<a\s*href=\"([\x{0000}-\x{ffff}]*?)\"/u", $out[0], $ids);
// 		var_dump($ids[1]);exit;
		foreach ($ids[1] as $k=>$v){
            $house_info[] = $v;
		}
        return $house_info;
	}
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html = $this->getUrlContent($source_url);
        $house_info = [];
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
// 		var_dump($html);die;
		$house_info['source'] = 10;
		$house_info['source_owner'] = 5;
		$house_info['is_contrast'] = 9;//未经58检测
		$house_info['company'] = "58同城个人房源";
		preg_match("/\<title>([\x{0000}-\x{ffff}]+?)<\/title>/u",$html,$title);
		//标题
		$title = strip_tags($title[1]);
		$title = str_replace(array("\t","\n", "\r", " "), "", $title);
		$title = SBC_DBC($title);
		$house_info['house_title'] = explode("-",$title)[0];
		preg_match("/<ul\s*class=\"houseInfo-detail\s*bbOnepx\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$detail);
		
		$info = trimall($detail[1]);
// 		var_dump($info);die;
		//价格
		preg_match("/(\d+\.?\d*)元/", $info, $price);
// 		var_dump($price);die;
		$house_info['house_price']=$price[1];
		//总面积
		preg_match("/(\d+\.?\d*)㎡/", $info, $totalarea);
// 		dump($totalarea);die;
		$house_info['house_room_totalarea']=$totalarea[1];
		//小区
//         var_dump($totalarea);die;
		preg_match("/小区:([\x{0000}-\x{ffff}]+?)<span>/u",$info,$borough);
// 		dump($borough);die;
		$house_info['borough_name']=strip_tags($borough[1]);
		preg_match("/楼层:([\x{0000}-\x{ffff}]+?)<span>/u", $info, $fl);
		$fl = strip_tags($fl[1]);
		$fl = str_replace("层","",$fl);
		$fl = explode("/",$fl);
		$house_info['house_floor']=$fl[0];
		$house_info['house_topfloor']=$fl[1];
		
		//付款类型 例如 押一付三
		preg_match("/付款:([\x{0000}-\x{ffff}]+?)<\/li>/u", $info, $pt);
		$house_info['pay_type']= strip_tags($pt[1]);
// 		dump($house_info['pay_type']);die;
// 		室	
		preg_match("/(\d+?)室/", $info, $room);
// 		dump($room);die;
	    $house_info['house_room']=$room[1];
	    
// 		厅
		preg_match("/(\d+?)厅/", $info, $hall);
		$house_info['house_hall']=$hall[1];
// 		dump($hall);die;
		//卫
		preg_match("/(\d+?)卫/",$info,$toilet);
		$house_info['house_toilet']=trimall($toilet[1]);
		//装修
		preg_match("/(精装修|中等装修|简单装修|豪华装修)/",$html,$fitment);
		$fitments = strip_tags($fitment[0]);
		$fitments = str_replace(array("\t","\n","\r", " "), "", $fitments);
		$fitments = SBC_DBC($fitments);
		
		$house_info['house_fitment']=trimall($fitments);
		//朝向
// 		var_dump($html);die;
		preg_match("/<ul\s*class=\"houseDetail\-type\">([\x{0000}-\x{ffff}]+?)<\/ul>/u",$html,$di);
		$di = strip_tags(trimall($di[1]));
// 		dump($di);die;
		preg_match("/朝向:([\x{0000}-\x{ffff}]+?)装/u", $di, $toward);
// 		dump($toward);die;
        $toward = str_replace("暂无信息","",trimall($toward[1]));
		$house_info['house_toward']=$toward;
		//城区，商圈
		preg_match("/位置([\x{0000}-\x{ffff}]+?)<\/li>/u",$detail[0],$area);
		$area_arr = explode('-',strip_tags($area[1]));
		$house_info['cityarea2_id'] =trimall($area_arr[1]);
		$house_info['cityarea_id'] =str_replace("&nbsp","",trimall($area_arr[0]));
		//联系人
		preg_match("/<ul\s*class=\"user\-profile\">([\x{0000}-\x{ffff}]+?)<\/ul>/u",$html,$ui);
// 		dump($ui[1]);die;
		preg_match("/<span\s*class=\"profile\-name\">([\x{0000}-\x{ffff}]+?)<\/span>/u",$ui[1],$name);
		$house_info['owner_name'] = trimall(strip_tags($name[1]));
		//联系电话
		preg_match("/<span\s*class=\"meta\-phone\">([\x{0000}-\x{ffff}]+?)<\/span>/u",$ui[1],$phone);
// 		dump($phone);die;
		$house_info['owner_phone'] = trimall(strip_tags($phone[0]));
        $house_info['owner_phone'] = str_replace('该用户使用了隐私保护','',$house_info['owner_phone']);
        if($house_info['owner_phone'] == $house_info['owner_name']){
            $house_info['owner_name'] = '';
        }
		//房源描述
		preg_match("/description\sdesClose\">([\x{0000}-\x{ffff}]+?)<\/P>/u",$html,$desc);
		$desc = trimall(strip_tags($desc[1]));
		$desc = str_replace(array("\t", "\r", "&nbsp;"), "", $desc);
		$desc = SBC_DBC($desc);
		$house_info['house_desc'] = trimall($desc);
        //房源类型
        preg_match("/类型\:([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$house_type);
        $house_info['house_type']= $house_type[1];
        //入住人限制
        preg_match("/出租间\:([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$sex);
        $house_info['sex']= trimall($sex[1]);
		//图片
		preg_match("/<div\sclass\=\"image_area([\x{0000}-\x{ffff}]+?)<\/ul>/u", $html, $pics);
		preg_match_all("/ref=\"([\x{0000}-\x{ffff}]+?)\"/u", $pics[1], $pic);
		$picture = array_merge($pic[1]);
		$picture = array_unique($picture);
		$house_info['house_pic_unit']= implode("|", $picture);
		$house_info['created'] = time();
		$house_info['updated'] = time();
        return $house_info;
	}

    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)) {
            if (empty($html)) {
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $newurl = get_jump_url($url);
            if ($newurl == $url) {
                if (preg_match("/ico_error/", $html)) {
                    return 1;
                } else {
                    return 2;
                }
            } else {
                return 1;
            }
        }
    }
}