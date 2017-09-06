<?php namespace nanjing;
/**
 * @description 南京58整租抓取规则
 * @classname 南京房58
 */

Class Five8PersonalRent extends \city\PublicClass  {

	public function house_page(){
		//城区名称  分城区抓取 计划任务记得传参数(城区拼音，抓取页数)
		//examplde :http://localhost/spider/Sell/Five8Personal/fetch?dis=haidian&page=20
		$dis_name = array(
			'玄武',
			'鼓楼',
			'建邺',
			'白下',
			'秦淮',
			'下关',
			'雨花台',
			'浦口',
			'栖霞',
			'江宁',
			'六合',
			'高淳',
			'溧水',
			'大厂',
			'南京周边'
			
		);
		$dis = array(
		    'xuanwuqu',
		    'gulouqu',
		    'jianyequ',
		    'bbaixiaqu',
		    'qinhuaiqu',
		    'xiaguanqu',
		    'yuhuatai',
		    'pukouqu',
		    'qixiaqu',
		    'jiangningqu',
		    'liuhequ',
		    'gaochunqu',
		    'lishuixian',
		    'dachangqu',
		    'nanjing'
		
		);
		//http://m.58.com/nj/zufang/?58hm=m_home_house_zhengzu_new&58cid=172&from=home_house_3&PGTID=0d100000-000a-c5ec-08d9-8dcac80d551e&ClickID=1
        $maxPage = empty ($_GET['maxPage']) ? '100' : $_GET['maxPage'];//最大页数
        $urlarr = [];
		foreach($dis as $index){
            for($page=1;$page<=$maxPage;$page++){
                $urlarr[] = 'http://m.58.com/'.$index.'/zufang/pn'.$page.'?refrom=wap';
            }
        }
        return $urlarr;
	}
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html=file_get_contents($url);
// 		var_dump ($html);die;
		preg_match("/id=\"filter\-more\">[\x{0000}-\x{ffff}]*?<\/html>/u", $html, $out);
// 		var_dump($out);die;
		preg_match_all("/infoid=\'([\x{0000}-\x{ffff}]*?)\'/u", $out[0], $ids);
// 		var_dump($ids[1]);die;
		$ids = preg_replace('/\D/s',"",$ids[1]);
// 		var_dump($ids);die;
		//preg_match_all("(\d+?)",$ids[0],$id);
		//dump($ids);die;
		
		foreach ($ids as $k=>$v){
			$house_info[] = "http://m.58.com/nj/zufang/".$v."x.shtml";
		    //dump($this->house_info);
		}
		return $house_info;
	}
	
   /*
	* 获取详情
	*/
	public function house_detail($source_url){
// 	    var_dump($source_url);die;
		$html = file_get_contents($source_url);
		//下架检测
//		$house_info['off_type'] = $this->is_off($source_url,$html);
// 		var_dump($html);die;
// 		echo $html;die;
		$house_info['source'] = 10;
		$house_info['source_owner'] = 5;
		$house_info['is_contrast'] = 9;//未经58检测
		$house_info['company'] = "58同城个人房源";
		preg_match("/\<title>([\x{0000}-\x{ffff}]+?)<\/title>/u",$html,$title);
		//标题
		$title = strip_tags($title[1]);
		$title = str_replace(array("\t","\n", "\r", " ","-南京58同城"), "", $title);
		$title = SBC_DBC($title);
		$house_info['house_title'] = $title;
		preg_match("/houseInfo\-detail\s*bbOnepx\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$detail);
		$info= $detail[1];
// 		var_dump($info);
		//价格
		preg_match("/(\d+\.?\d*)元/", $info, $price);
// 		var_dump($price);die;
		$house_info['house_price']=$price[1];
		//总面积
		preg_match("/面积:([\x{0000}-\x{ffff}]+?)<\/span>/u", $info, $totalarea);
		$totalarea = str_replace(array('㎡','平'),'',trimall($totalarea[1]));
		
// 		var_dump($totalarea);die;
		$house_info['house_totalarea']=$totalarea;
		
		//小区
		//dump($html);die;
		preg_match("/<ul\s*class=\"houseInfo\-meta\s*bbOnepx\">([\x{0000}-\x{ffff}]+?)<\/span>/u",$html,$borough);
// 		var_dump($borough);die;
		$borough_name = trimall(strip_tags($borough[1]));
		$borough_name = str_replace("小区:", "", $borough_name);
// 		var_dump($borough_name);die;
		$house_info['borough_name']=$borough_name;
		
		preg_match("/(\d+)室/", $info, $room);
		$rooms = $room[1];
		if(is_numeric($rooms)){
	       //室
	        $house_info['house_room']=$rooms;
		}else{
		    switch (trimall($rooms)) {
		        case "一":
		            $rooms='1';
		            break;
		        case "二":
		            $rooms='2';
		            break;
		        case "三":
		            $rooms='3';
		            break;
		        case "四":
		            $rooms='4';
		            break;
		        case "五":
		            $rooms='5';
		            break;
		        case "六":
		            $rooms='6';
		            break;
		        case "七":
		            $rooms='7';
		            break;
		        case "八":
		            $rooms='8';
		            break;
		        case "九":
		            $rooms='9';
		            break;
		        case "十":
		            $rooms='10';
		            break;
		        default:
		            $rooms=null;
		    }
		    $house_info['house_room']=$rooms;
		} 
		
		preg_match("/(\d+?)厅/", $info, $hall);
		//厅
		$house_info['house_hall']=$hall[1];
		//卫
		preg_match("/厅(\d+?)卫/",$info,$toilet);
// 		var_dump($toilet);die;
		$house_info['house_toilet']=trimall($toilet[1]);
		//装修
		preg_match("/(精装修|中等装修|简单装修|豪华装修)/",$html,$fitment);
// 		dump($fitment);die;
		$fitments = strip_tags($fitment[0]);
		$fitments = str_replace(array("\t","\n","\r", " "), "", $fitments);
		$fitments = SBC_DBC($fitments);
		
		$house_info['house_fitment']=trimall($fitments);
		//朝向
		preg_match("/<ul\s*class=\"houseDetail\-type\">([\x{0000}-\x{ffff}]+?)<\/li>/u", $html, $toward);
		$toward = str_replace("朝向:", "", trimall(strip_tags($toward[1])));
		if ($toward == '暂无信息'){
		    $toward = '';
		}
		$house_info['house_toward']=$toward;
		
// 		var_dump($house_info);die;
		//房屋类型
		preg_match("/类型:([\x{0000}-\x{ffff}]+?)<\/li>/u", $html, $house_type);
		$house_type = trimall(strip_tags($house_type[1]));
		if ($house_type == '暂无信息'){
		    $house_type = '';
		}
		$house_info['house_type']=$house_type;
		//所在楼层
		preg_match("/楼层:([\x{0000}-\x{ffff}]+?)<\/span>/u", $info, $floor);
		$floor = str_replace('层','',trimall($floor[1]));
		$floorarr = explode("/",$floor);
// 		var_dump($floorarr);die;
		//总楼层
		$house_info['house_floor']=$floorarr[0];
		$house_info['house_topfloor']=$floorarr[1];
		//付款类型
		preg_match("/付款:([\x{0000}-\x{ffff}]+?)<\/span>/u", $info, $pay_type);
		$pay_type = trimall($pay_type[1]);
		$house_info['pay_type'] = $pay_type;
		
		//城区，商圈
		// dump($detail);die;
		preg_match("/位置([\x{0000}-\x{ffff}]+?)<\/li>/u",$detail[0],$area);
		// dump($area);die;
		$area_arr = explode('-',strip_tags($area[1]));
		$house_info['cityarea2_id'] =trimall($area_arr[1]);
		$house_info['cityarea_id'] =str_replace("&nbsp","",trimall($area_arr[0]));
		//联系人
		
		preg_match("/profile-name\">([\x{0000}-\x{ffff}]+?)<\/span>/u",$html,$name);
		// dump($name);die;
		$house_info['owner_name'] = trimall(strip_tags($name[1]));
		//联系电话
		preg_match("/meta-phone\">([\x{0000}-\x{ffff}]+?)<\/span>/u",$html,$phone);
		$house_info['owner_phone'] = trimall(strip_tags($phone[1]));
        if($house_info['owner_phone'] == $house_info['owner_name']){
            $house_info['owner_name'] = '';
        }
		//房源描述
		preg_match("/desClose\">([\x{0000}-\x{ffff}]+?)<\/p>/u",$html,$desc);
		$desc = strip_tags($desc[1]);
		$desc = str_replace(array("\t", "\r", " ","万"), "", $desc);
		$desc = SBC_DBC($desc);
		$house_info['house_desc'] = trimall($desc);
		//图片
		preg_match("/<div\sclass\=\"image_area([\x{0000}-\x{ffff}]+?)<\/ul>/u", $html, $pics);
		// dump($pics);die;
		preg_match_all("/ref=\"([\x{0000}-\x{ffff}]+?)\">/u", $pics[1], $pic);
		$picture = array_merge($pic[1]);
		$picture = array_unique($picture);
		$house_info['house_pic_unit']= implode("|", $picture);
		if(empty($house_info['house_pic_unit'])){
		    $house_info['house_pic_unit'] = '';
		}
		$house_info['created'] = time();
		$house_info['updated'] = time();
		$house_info['house_relet'] = '';
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
?>