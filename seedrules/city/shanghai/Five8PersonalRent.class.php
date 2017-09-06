<?php namespace shanghai;
/**ok
 * @description 上海58同城业主个人整租房抓取规则
 * @classname 上海58同城
 */
Class Five8PersonalRent extends \city\PublicClass{
    
	public function house_page(){
		//城区名称  分城区抓取 计划任务记得传参数(城区拼音，抓取页数)
		//examplde :http://localhost/spider/Sell/Five8Personal/fetch?dis=haidian&page=20
		$cityarea_name = array(
		    '黄浦',
			'卢湾',
			'静安',
			'徐汇',
			'浦东',
			'长宁',
			'虹口',
			'杨浦',
			'普陀',
			'闸北',
			'闵行',
			'宝山',
			'嘉定',
			'青浦',
			'奉贤',
			'南汇',
			'崇明',
			'金山',
			'松江',
			'上海周边'
		);
		$dis = array(
		    'huangpu',
		    'luwan',
		    'jingan',
		    'xuhui',
		    'pudongxinqu',
		    'changning',
		    'hongkou',
		    'yangpu',
		    'putuo',
		    'zhabei',
		    'minxing',
		    'baoshan',
		    'jiading',
		    'qingpu',
		    'fengxiansh',
		    'nanhui',
		    'chongming',
		    'jinshan',
		    'songjiang',
		    'shanghaizhoubian'
		);
		
		$index = empty ( $_GET ['dis'] ) ? 0 : $_GET ['dis']; // 城区拼音
		$maxPage = empty ($_GET['maxPage']) ? 70 : $_GET['maxPage'];//要抓取的页数
		
		$url = [];
		for($page= 1;$page<=$maxPage;$page++){
	        $url[] = 'http://m.58.com/'.$dis[$index].'/zufang/0/pn'.$page.'?refrom=wap';
		}
		return $url;
	}
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html = $this->getUrlContent($url);
        $house_info = [];
		preg_match("/<ul\s*class=\"list\-info\">[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $out);
		preg_match_all("/infoid=[\x{0000}-\x{ffff}]*?uid=/u", $out[0], $ids);
		$ids = preg_replace('/\D/s',"",$ids[0]);
		//preg_match_all("(\d+?)",$ids[0],$id);
		foreach ($ids as $k=>$v){
			$house_info[] = "http://m.58.com/sh/zufang/".$v."x.shtml";
		}
		return $house_info;
	}
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
// 		$source_url = 'http://m.58.com/bj/ershoufang/23642587060107x.shtml';
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
		$house_info['source'] = 10;
		$house_info['source_owner'] = 5;
		$house_info['is_contrast'] = 9;//未经58检测
		$house_info['company'] = "58同城个人房源";
		preg_match("/\<title>([\x{0000}-\x{ffff}]+?)<\/title>/u",$html,$title);
		//标题
		$title = strip_tags($title[1]);
		$title = str_replace(array("\t","\n", "\r", " "), "", $title);
		$title = SBC_DBC($title);
		$house_info['house_title'] = $title;
		preg_match("/<ul\s*class=\"houseInfo-detail\s*bbOnepx\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$detail);
		
		$info = trimall($detail[1]);
		//价格
		preg_match("/(\d+\.?\d*)元/", $info, $price);
		$house_info['house_price']=$price[1];
		//总面积
		preg_match("/(\d+\.?\d*)㎡/", $info, $totalarea);
		$house_info['house_totalarea']=$totalarea[1];
		//小区
		preg_match("/小区:([\x{0000}-\x{ffff}]+?)<span>/u",$info,$borough);
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
		preg_match("/(\d|一|两|三|四|五|六)室/", $info, $room);
// 		dump($room);die;
        if(empty($room)){
            preg_match("/(\d|一|两|三|四|五|六)室/", $title, $room);
        }
	    $house_info['house_room']=$room[1];
	    
// 		厅
		preg_match("/(\d+?)厅/", $info, $hall);
        if(empty($hall)){
            preg_match("/(\d|一|两|三|四|五|六)厅/", $title, $hall);
        }
		$house_info['house_hall']=$hall[1];
// 		dump($hall);die;
		//卫
		preg_match("/(\d+?)卫/",$info,$toilet);
        if(empty($toilet)){
            preg_match("/(\d|一|两|三|四|五|六)卫/", $title, $toilet);
        }
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
        if($house_info['owner_phone'] == $house_info['owner_name']){
            $house_info['owner_name'] = '';
        }
		//房源描述
		preg_match("/\<div\sid=\"describe\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$desc);
		$desc = strip_tags($desc[1]);
		$desc = str_replace(array("\t", "\r", " ","万"), "", $desc);
		$desc = SBC_DBC($desc);
		$house_info['house_desc'] = trimall($desc);
		//图片
		preg_match("/<div\sclass\=\"image_area([\x{0000}-\x{ffff}]+?)<\/ul>/u", $html, $pics);
		preg_match_all("/ref=\"([\x{0000}-\x{ffff}]+?)\">/u", $pics[1], $pic);
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