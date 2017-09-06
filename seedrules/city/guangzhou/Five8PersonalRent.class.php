<?php namespace guangzhou;
/**
 * @description 广州58个人 整租房抓取规则
 * @classname 广州58个人(k-ok)
 */

Class Five8PersonalRent
    extends \city\PublicClass{
    

	public function house_page(){
		//城区名称  分城区抓取 计划任务记得传参数(城区拼音，抓取页数)
		//examplde :http://localhost/spider/Sell/Five8Personal/fetch?dis=haidian&page=20
		$cityarea_name = array(
			'天河',
			'海珠',
			'越秀',
			'白云',
			'荔湾',
			'黄埔',
			'花都',
			'番禺',
			'增城',
			'萝岗',
			'东莞',
			'佛山',
			'从化',
			'南沙',
			'经济开发区',
			'广州周边'
			
		);
		$dis = array(
		    'tianhe',
		    'haizhu',
		    'yuexiu',
		    'baiyun',
		    'liwan',
		    'huangpugz',
		    'huadugz',
		    'panyu',
		    'zengcheng',
		    'luoganggz',
		    'dongguanqu',
		    'foshanqu',
		    'conghua',
		    'nanshagz',
		    'jingjikaifaqu',
		    'guangzhouzhoubian'		
		);

		foreach($dis as $index){
            $URLPRE = "http://m.58.com/".$index."/zufang/0/?refrom=wap";
            //获取最大页
            $maxPage = $this->get_maxPage($URLPRE);

            for($page = 1; $page <= $maxPage; $page++){
                $urlarr[] = 'http://m.58.com/'.$index.'/zufang/0/pn'.$page.'?refrom=wap';
            }
        }
        return $urlarr;
	}
	
	/*
	 * 获取搜索条件下的最大页
	 */
	Public function get_maxPage($url){
	    $html = $this->getUrlContent($url);
	    preg_match('/total\">(\d+?)<\/span>/u',$html,$page);
	    $maxPage = $page[1];
	    //如果最大页抓空，返回0
	    if(!empty($maxPage)){
	        return $maxPage;
	    }else{
	        return 0;
	    }
	}
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		//这里可以去ip代理池中取一个ip再继续（还没做）
		$html=$this->getUrlContent($url);
		preg_match("/<ul\s*class=\"list\-info\">[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $out);
		preg_match_all("/infoid=[\x{0000}-\x{ffff}]*?uid=/u", $out[0], $ids);
		$ids = preg_replace('/\D/s',"",$ids[0]);
		foreach ($ids as $k=>$v){
			$house_info[] = "http://m.58.com/gz/zufang/".$v."x.shtml";
		}
		return $house_info;
		//dump($this->house_info);die;
	}
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
        //$source_url = 'http://m.58.com/bj/ershoufang/23642587060107x.shtml';
        //这里可以去ip代理池中取一个ip再继续（还没做）
        $html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
        $house_info['source'] = 10;
        $house_info['source_owner'] = 5;
        $house_info['is_fill'] = 2;
        $house_info['is_contrast'] = 9;//未经58检测
        $house_info['company'] = "58同城个人房源";
        preg_match("/meta-tit\">([\x{0000}-\x{ffff}]+?)<\//u",$html,$title);
        //标题
        $title = strip_tags($title[1]);
        $title = str_replace(array("\t","\n", "\r", " "), "", $title);
        $title = SBC_DBC($title);
        $house_info['house_title'] = $title;

        preg_match("/body-content\">([\x{0000}-\x{ffff}]+?)<script/u",$html,$detail);
        $info = strip_tags($detail[0]);
        $info = str_replace(array("\t","\n","\r", " ","&nbsp"), "", $info);
        $info = SBC_DBC($info);
        //价格
        preg_match("/(\d+\.?\d*)元/", $info, $price);
        $house_info['house_price']=$price[1];
        //总面积
        preg_match("/(\d+\.?\d*)㎡/", $info, $totalarea);
        $house_info['house_totalarea']=$totalarea[1];
        //小区
        preg_match('/xiaoqu:\{name:\'([\x{0000}-\x{ffff}]+?)\',lat/u',$html,$bor);
        $house_info['borough_name']=$bor[1];

        preg_match("/(\d)室/u", $info, $rooms);
        if(empty($rooms)){
            preg_match("/(\d)室|房/u", $title, $rooms);
        }
        if(!is_numeric($rooms[1])){
            switch (trimall($rooms[1])) {
                case "一":
                    $rooms[1] = 1;
                    break;
                case "二":
                    $rooms[1] = 2;
                    break;
                case "三":
                    $rooms[1] = 3;
                    break;
                case "四":
                    $rooms[1] = 4;
                    break;
                case "五":
                    $rooms[1] = 5;
                    break;
                case "六":
                    $rooms[1] = 6;
                    break;
                case "七":
                    $rooms[1] = 7;
                    break;
                case "八":
                    $rooms[1] = 8;
                    break;
                default:
                    $rooms = null;
            }

        }
        $house_info['house_room']=$rooms[1];
        preg_match("/(\d)厅/", $info, $hall);
        if(empty($hall)){
            preg_match("/(\d)厅/", $title, $hall);
        }
        //厅
        $house_info['house_hall']=$hall[1];
        //卫
        preg_match("/(\d)卫/",$info,$toilet);
        if(empty($toilet)){
            preg_match("/(\d)卫/", $title, $toilet);
        }
        $house_info['house_toilet']=trimall($toilet[1]);
        //装修
        preg_match("/(精装修|中等装修|简单装修|豪华装修)/",$html,$fitment);
        $fitments = strip_tags($fitment[0]);
        $fitments = str_replace(array("\t","\n","\r", " "), "", $fitments);
        $fitments = SBC_DBC($fitments);

        $house_info['house_fitment']=trimall($fitments);
        //朝向
        preg_match("/朝向([\x{0000}-\x{ffff}]+?)装修/u", $info, $toward);
        //dump($info);dump($toward);die;
        $house_info['house_toward']=trimall($toward[1]);
        //所在楼层
        preg_match("/楼层:([\x{0000}-\x{ffff}]+?)\//u", $info, $floor);
        //总楼层
        preg_match("/(\d+?)层/u",$info,$topfloor);
        $house_info['house_floor']=trimall($floor[1]);
        $house_info['house_topfloor']=trimall($topfloor[1]);
        //城区，商圈
        preg_match("/位置([\x{0000}-\x{ffff}]+?)<\/li>/u",$detail[0],$area);
        $area_arr = explode('-',strip_tags($area[1]));
        $house_info['cityarea2_id'] =trimall($area_arr[1]);
        $house_info['cityarea_id'] =str_replace("&nbsp","",trimall($area_arr[0]));
        //联系人
        preg_match("/profile-name\">([\x{0000}-\x{ffff}]+?)</u",$html,$name);
        $house_info['owner_name'] = $name[1];
        //联系电话
        preg_match("/\d{11}/u",$info,$phone);
        $house_info['owner_phone'] =$phone[0];
        //房源描述
        preg_match("/\<div\sid=\"describe\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$desc);
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
        //创建时间
        $house_info ['created']= time();
        //更新时间
        $house_info ['updated']= time();
        //dump($house_info);die;
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