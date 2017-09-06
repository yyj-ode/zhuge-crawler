<?php namespace guangzhou;
/**
 * @description 广州中原二手房抓取规则
 * @classname 广州中原(k-ok)
 */

class Zhongyuan extends \baserule\Zhongyuan {
     public function house_page(){
        $urlarr1 = [];
        $urlarr2 = [];
        $urlarr3 = [];
        $cityarea2=array(
        	"changban","chebei","dongpu","dongwanzhuang","dongzhan","gangding","huajingxincheng","longdong","longkoudong","longkouxi","shahe","shatainan","shipaiqiao","tiyuxi","tiyuzhongxin",
        	"tianhegongyuan","tianhenan","tianrunlu","wushan","yuancun","yuancunerhenglu","yuancunsihenglu","yueken","zhujiangxinchengxi",
        	"keyunlu","cencun","eomtang","tangxia","wushanlukou","shangshe","beijinglu","dashatou","dongchuanlu","dongshan","ershadao","haizhuguangchang","huanshidong","huanghuagang","jianshelu","jiefangbei","jiefangnan","nongjiangsuo",
        	"wuyangxincheng","ximenkou","xiaobei","yangji","shuiyinlu","lujinglu","gonghelu","dongshankou","jianshe","xianlielu","baogang","binjiangdong","binjiangxi","binjiangzhong","changganglu","gongyedadaobei","gongyedadaonan","guangzhoudadaonan","jiangnandadao",
        		"jiangnanxi","jiangeomlu","kecun","lijiao","nanzhou","pazhou","qianjinlu","xingangxi","zhongda","xiadulu","dongxiaonan",
        		"baiyundadaobei","baiyundadaonan","guanghualu","guangyuanxincun","guangzhoudadaobei","guihuagang","huangbian","huangshi",
        		"jichanglu","jiahewanggang","jiangxia","jinshazhounan","jingei","luochongwei","nanhu","sanyuanli","shijing","tongdewei","tonghe","xinshi","jinshazhoubei",
        		"jinshazhou","dajinzhong",
        		"dashi","huanan","huananbiguiyuan","luoei","nanpu","qifu","qifuxinqiu","qifuzhongqiu","shawan","shiqi","shiqiaobei",
        		"shiqiaodong","shiqiaonan","shiqiaoxi","shundebiguiyuan","yayuncheng","zhongcun","daxuecheng","jinshangu","shilou","xiajiao","nancun",
        );
         $temp=array("tianhebei","huijingxincheng","zhujiangxinchengzhong","taojin","zhujiangxinchengdong","xinghewan","dongfengdong","chigang","guangzhouyajule");
        foreach ($cityarea2 as $val){
        	$pre = "http://gz.centanet.com/sale/".$val."/";
        	$maxPage = $this->getMaxPage($pre);
        	for ($page=1; $page <= $maxPage; $page++) {
        		$urlarr3[] = $pre."p".$page."/";
        	}
        }
        $cityarea = array("huadu","liwan","huangpu","zengcheng","nanhai","conghua","nansha","shunde","chancheng","taojin","xinghewan","dongfengdong","chigang","guangzhouyajule");
        $cityarea2_temp =array("tianhebei","huijingxincheng","zhujiangxinchengzhong","zhujiangxinchengdong");
        
        foreach ($cityarea as $val){
        	for($price=1;$price<=9;$price++){
        		$pre = "http://gz.centanet.com/sale/".$val."/s".$price;
        		$maxPage = $this->getMaxPage($pre);
        		for ($page=1; $page <= $maxPage; $page++) {
        			$urlarr1[] = $pre."-p".$page."/";
        		}
        	}
        }
        foreach ($cityarea2_temp as $val){
        	for($price=1;$price<=9;$price++){
        	     for($room=1;$room<=6;$room++){
                    $pre = "http://gz.centanet.com/sale/".$val."/s".$price."-x".$room;
                    $maxPage = $this->getMaxPage($pre);
                    for ($page=1; $page <= $maxPage; $page++) {
                        $urlarr2[] = $pre."-p".$page."/";
                    }
                }
        	}
        }
        return array_merge($urlarr1, $urlarr2,$urlarr3);
    }
	
	//到网页拿经纪人的电话及姓名
	public function get_name_phone($url){
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
	
	public function house_list($url){
		$html = $this->getUrlContent($url);
		preg_match_all('/<div\s*class=\"house-item[\x{0000}-\x{ffff}]+?<\/div>/u', $html, $ul);
	    $house_info = array();
		foreach($ul[0] as $j =>$v){
		    preg_match_all('/\<a\s*href=\"(.+?)\"\s*title=/', $v, $sublink);
            $house_info[] = $sublink[1][0];
		}
        return $house_info;
	}
	
	public function house_detail($url){
        $html = $this->getUrlContent($url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($url);
        preg_match("/roombase-box[\x{0000}-\x{ffff}]+?detailTabBox/u",$html,$detail);
        $info=trimall(strip_tags($detail[0]));
        preg_match("/f18\"\s*?>([\x{0000}-\x{ffff}]+?)</u",$html,$title);
        $house_info['house_title'] = $title[1];
        //价格
        preg_match("/(\d+)万/u",$info,$item);
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
        $temp_city_id =str_replace("二手房","",strip_tags($citybor[2]));
        $house_info['cityarea_id'] = str_replace("&gt;","",$temp_city_id);
        //商圈
        $temp_cityarea2_id =str_replace("二手房","",strip_tags($citybor[3]));
        $house_info['cityarea2_id'] = str_replace("&gt;","",$temp_cityarea2_id);
        //小区
        $house_info['borough_name'] = str_replace("&gt;","",strip_tags($citybor[4]));;
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
        
        preg_match('/<p\x*class=\"hotlineA\".*p>/',$html,$item);
        $text1 = '/<p.*?imobile.*</';
        preg_match($text1, $html, $result);
        
        $house_info['owner_phone'] = str_replace("转","",strip_tags($result[0]));
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
    protected function getMaxPage($url){
        $maxpage= 0;
        preg_match("/<em>(\d+)<\/em><\/span>/u",$this->getUrlContent($url),$count);
        if($count){
            $maxpage = ceil($count[1]/20);
        }
        return $maxpage;
    }

    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100)
    {
    	$url = 'http://gz.centanet.com/sale/gz/o1-p{$page}/';
    	$data = [];
    	for($i = 1; $i <= 100; $i++){
    		if($i ==1){
    			$data[] = "http://gz.centanet.com/sale/gz/o1/";
    		}else{
    			$data[] = str_replace('{$page}', $i, $url);
    		}
    		
    	}
    	return $data;
    }
}
