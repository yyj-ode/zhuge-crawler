<?php namespace guangzhou;
/**
 * @description 广州中原整租抓取规则
 * @classname 广州中原整租（k-ok）
 */



class ZhongyuanRent extends \city\PublicClass {
    Public function house_page(){
        $urlarr = [];
        for($price=1;$price<=7;$price++){
            for($area=1;$area<=8;$area++){
                for($room=1;$room<=6;$room++){
                    $pre = "http://gz.centanet.com/rental/gz/z".$price."-m".$area."-x".$room;
                    $maxPage = $this->getMaxPage($pre);
//                    var_dump($maxPage);
                    for ($page=1; $page <= $maxPage; $page++) {
                        $urlarr[] = $pre."-p".$page."/";
                    }
                }
            }
        }
        return $urlarr;
    }

	
	public function house_list($url){
        $html = $this->getUrlContent($url);
        //dump($html);die;
        preg_match_all('/<div\s*class=\"house-item[\x{0000}-\x{ffff}]+?<\/div>/u', $html, $ul);
        $house_info = array();
        foreach($ul[0] as $j =>$v){
            preg_match_all('/\<a\s*href=\"(.+?)\"\s*title=/', $v, $sublink);
            //dump($sublink[1][0]);die;
            $house_info[] = $sublink[1][0];
        }
        return $house_info;
	}
	
	public function house_detail($url){
        $html = $this->getUrlContent($url);
        $house_info = [];
        //下架检测
//        $house_info['off_type'] = $this->is_off($url);
        preg_match("/roombase-box[\x{0000}-\x{ffff}]+?detailTabBox/u",$html,$detail);
        $info=trimall(strip_tags($detail[0]));
        preg_match("/f18\"\s*?>([\x{0000}-\x{ffff}]+?)</u",$html,$title);
        $house_info['title'] = $title[1];
        //价格
        preg_match("/(\d+)元/u",$info,$item);
        $house_info['house_price'] = $item[1];
        //室
        preg_match("/(\d+)室/u",$info,$item);
        $house_info['house_room'] = $item[1];
        //厅
        preg_match("/(\d+)厅/u",$info,$item);
        $house_info['house_hall'] = $item[1];
        //卫
        preg_match("/(\d+)卫/u",$info,$item);
        $house_info['house_toilet'] = $item[1];
        //厨
        preg_match("/(\d+)厨/u",$info,$item);
        $house_info['house_kitchen'] = $item[1];
        //楼层
        preg_match("/(低|中|高)/u",$info,$item);
        $house_info['house_floor'] = $item[1];
        //面积
        preg_match("/(\d+)平/u",$info,$item);
        $house_info['house_totalarea'] = $item[1];
        preg_match("/breadnav[\x{0000}-\x{ffff}]+?<\/div>/u",$html,$bor);
        $citybor = explode("<b>",trimall($bor[0]));
        //城区
        $house_info['cityarea_id'] = str_replace("二手房","",strip_tags($citybor[2]));
        //商圈
        $house_info['cityarea2_id'] = str_replace("二手房","",strip_tags($citybor[3]));
        //小区
        $house_info['borough_name'] = strip_tags($citybor[4]);
        //朝向
        preg_match("/朝向：([\x{0000}-\x{ffff}]+?)年/u",$info,$item);
        $house_info['house_toward'] = $item[1];
        //年代
        preg_match("/(\d{4})年/u",$info,$item);
        $house_info['house_built_year'] = $item[1];
        //装修
        preg_match("/(毛坯|简装|精装|豪装)/u",$info,$item);
        $house_info['house_fitment'] = $item[1];
        //联系人
        preg_match("/<b>([^<>]*?)<\/b><\/a>/u",$html,$item);
        $house_info['owner_name'] = $item[1];
        //电话
        preg_match("/\"imobile\">([\x{0000}-\x{ffff}]+?)<\/p>/u",$html,$item);
        $house_info['owner_phone'] = str_replace("转","",$item[1]);
        //描述
        preg_match("/id=\"PostDescription\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$item);
        $house_info['house_desc'] = trimall(strip_tags($item[1]));
        //房源图片
        preg_match("/image-gallery[\x{0000}-\x{ffff}]+?<\/ul>/u",$html,$pics);
        preg_match_all("/src=\"(\S+?)\"/u",$pics[0],$pic);
        $house_info['house_pic_unit'] = implode("|",array_unique($pic[1]));
        $house_info['created'] = time();
        $house_info['updated'] = time();
        return $house_info;
    		
	}
    //到网页拿经纪人的电话及姓名
    public function get_name_phone($url){
// 		$url = "http://sz.centanet.com/ershoufang/a10cb8bb-46cc-446d-ac01-4879b9c43d68.html";
        $s_html = $this->getUrlContent($url);
        preg_match('/rel=\"nofollow\"\s*class=\"cBlue\"\s*href=\"(.+?)\">/u', $s_html, $jjr_url);
        $data = array();
        $html = $this->getUrlContent('http://sz.centanet.com'.$jjr_url[1]);
        preg_match('/zvalue=\"([\x{0000}-\x{ffff}]+?)\"/u', $html, $json);
        $arr = explode(':', $json[1]);
        preg_match('/(\d{11})/', $arr[1], $phone);
        $data['phone'] = $phone[1];
        $data['name'] = str_replace(array("'", '}'), '', $arr[3]);
        return $data;
    }
    protected function getMaxPage($url){
        $maxpage= 0;
        preg_match("/<em>(\d+)<\/em><\/span>/u",$this->getUrlContent($url),$count);
        if($count){
            $maxpage = ceil($count[1]/20);
        }
        return $maxpage;
    }

    //下架判断
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){//没有跳转
            $html = $this->getUrlContent($url);
            //暂未找到下架页面
            if(preg_match("/remove_over\s*state_bg/", $html)){
                return 1;
            }elseif(preg_match("/class=\"errortag\"/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}
