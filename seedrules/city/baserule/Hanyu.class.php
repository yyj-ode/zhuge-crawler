<?php namespace baserule;
/**
 * @description 上海汉宇地产二手房抓取规则
 * @classname 上海汉宇
 */


Class Hanyu  extends \city\PublicClass
{
	public $URL;
    /*
	 * 抓取
	 */
    Public function house_page(){
		$resultData = [];
		$html = gb2312_to_utf8($this->getUrlContent($this->URL));
		$page_info = \QL\QueryList::Query($html,[
			'max' => ['.page-top', 'text','-a',function($max){
				$max = explode('/ ',$max);
				return $max[1];
			}],

		],null,null,null,true)->getData(function($data){
			return $data['max'];
		});
		unset ($html);
		for($i = 1; $i <= $page_info[0]; $i++){
			$resultData[] = $this->URL."?page={$i}";
		}
		writeLog('Zhongyuan_page' . __FUNCTION__, ['url' => $resultData], true);
		return $resultData;
    }
	/*
	 * 获取source_url
	*/
	public function house_list($url){
		$html = gb2312_to_utf8($this->getUrlContent($url));
		preg_match("/<div\s*class=\"main\-left\">[\x{0000}-\x{ffff}]*?ListBottom/u", $html, $div);
		preg_match_all("/<ul[\x{0000}-\x{ffff}]*?ul>/u", $div[0], $uls);
		$house_info = array();
		foreach($uls[0] as $k=>$v){
			preg_match("/id=(\d+?)\"/", $v, $id);
			$house_info[] = 'http://www.hanyuproperty.com/housedetail.asp?id='.$id[1];
		}
        return $house_info;
	}
	
	
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){

		$html = gb2312_to_utf8($this->getUrlContent($source_url));

		$house_info['source'] = 18;
		$house_info['company'] = "汉宇地产";
		if(trim($html) == '房源不存在或已过期'){
			$house_info['content']=$html;
			return $house_info;
		}
// 		echo $html;die;
		//标题<title>只挂：单价5万/平，半年前价格，不抢就没了，房型正气 - 金杨新村八街坊银山路342弄 - 汉宇地产</title>
		preg_match("/<title[\x{0000}-\x{ffff}]+?title>/u", $html, $title);
		
		$title = strip_tags($title[0]);
		$title = explode('-',$title);
// 		print_r($title);die;
		$house_info['house_title'] = trimall($title[0]);
		
		/*
		 * 价格 室厅卫等信息
		 * <div class="housexx1">
		 */
		preg_match("/<div\s*class=\"housexx1[\x{0000}-\x{ffff}]+?<\/ul>/u", $html, $detail);
// 		print_r($detail);die;
		//价格——数字
		preg_match("/<div\s*class=\"h\-price\">(\d+?)<\/div>/", $detail[0], $price);
		//价格——单位
		preg_match("/<div\s*class=\"h\-price2\">([\x{0000}-\x{ffff}]+?)<\/div>/u", $detail[0], $price2);
// 		print_r($price2);
// 		print_r($price);die;
		
		$house_info['house_price'] = $price[1];
		
		preg_match_all("/<li\s*class=\"right\">[\x{0000}-\x{ffff}]+?<\/li>/u", $detail[0], $info);
		$right = strip_tags($info[0][0]);
		//室厅卫 面积 装修 朝向 楼层
		$r_a_f_f = explode('|', $right);
//  		print_r($r_a_f_f);

		preg_match("/(\d+?)房/", $r_a_f_f[0], $room);
		preg_match("/(\d+?)厅/", $r_a_f_f[0], $hall);
		preg_match("/(\d+?)卫/", $r_a_f_f[0], $toilet);
		//室
		$house_info['house_room']=$room[1];
		//厅
		$house_info['house_hall']=$hall[1];
		//卫house_toilet
		$house_info['house_toilet']=$toilet[1];

		preg_match("/(\d+\.?\d*)平米/", $r_a_f_f[1], $totalarea);
		$house_info['house_totalarea']=$totalarea[1];
		
		//朝向和装修
		if(preg_match('/装修/u',$r_a_f_f[2])){
		    $fit_tow = explode('装修', $r_a_f_f[2]);
		    $house_info['house_toward'] = str_replace('朝向', '', trimall($fit_tow[1]));
		    $house_info['house_fitment'] = trimall($fit_tow[0]);
		}
		else{
		    $house_info['house_toward'] = str_replace('朝向', '', trimall($r_a_f_f[2]));
		    $house_info['house_fitment'] = '';
		}
		
		//建造年份
		$house_info['house_built_year'] = trimall(str_replace('年', '', $r_a_f_f[3]));
		
		//楼层
		preg_match("/(\d+?)\/(\d+?)层/", $r_a_f_f[4], $floor);
		$house_info['house_floor']=$floor[1];
		$house_info['house_topfloor']=$floor[2];
		//城区和商圈
		preg_match_all("/<li\s*class=\"xqjj\">[\x{0000}-\x{ffff}]+?<\/li>/u", $html, $xqjj);
		preg_match_all("/<a[\x{0000}-\x{ffff}]+?a>/u", $xqjj[0][0], $dis);
		$house_info['cityarea_id'] = str_replace('区','',trimall(strip_tags($dis[0][0])));
		$house_info['cityarea2_id'] = str_replace("片区", "", trimall(strip_tags($dis[0][1])));
		
		//小区名字
		$house_info['borough_name'] = str_replace(array('地址：', '地图'), "", trimall(strip_tags($xqjj[0][1])));
		
		
		//联系人及电话
		preg_match("/<div\s*class=\"s_jjr_name\">[\x{0000}-\x{ffff}]+?<\/a>/u", $html, $jjr);
		
        $house_info['owner_name'] = trimall(strip_tags($jjr[0]));
        
        preg_match("/<div\s*class=\"jjr_phone\">[\x{0000}-\x{ffff}]+?<\/div>/u", $html, $jjr_phone);
        $house_info['owner_phone'] = trimall(strip_tags($jjr_phone[0]));
		
		//图片
		preg_match("/房源图片[\x{0000}-\x{ffff}]+?<\/li>/u", $html, $pics_tag);;
		preg_match_all("/src=\"([\x{0000}-\x{ffff}]+?)\"/u", $pics_tag[0], $pics);
		$house_info['house_pic_layout'] = 'http://www.hanyuproperty.com/'.$pics[1][count($pics[1])-1];
		unset($pics[1][count($pics[1])-1]);
		foreach($pics[1] as $k=>$v){
			$house_info['house_pic_unit'][] = 'http://www.hanyuproperty.com/'.$v;
		}
		
		$house_info['house_pic_unit']= implode("|", $house_info['house_pic_unit']);
		
		//描述
		preg_match("/<div\s*class=\"housexx2\">[\x{0000}-\x{ffff}]+?<\/ul>/u", $html, $content);
		$house_info['house_desc'] = htmlspecialchars(trimall(strip_tags($content[0])));
        $house_info['tag'] = $this->getTags($html);
        return $house_info;
	}

    /*
    * 抓取房源对应标签
    */
    public function getTags($html){
        preg_match_all("/<div\sclass=\"h-xx\">(.*?)<\/div>/si", $html, $ulMatch);
        if(!$ulMatch)return '';

        preg_match_all("/<li class=\"right\">(.*?)<\/li>/u", $ulMatch[1][0], $liMatch);
        if(!isset($liMatch[1][2]))return '';

        $tagHtml = $liMatch[1][2];
        $tagsArr = explode(',', $tagHtml);
        $tags = [];
        foreach($tagsArr as $tag){
            $tag = trimall(str_replace('&nbsp;', '', $tag));
            if(!$tag) continue;
            $tags[] = $tag;
        }

        return implode("#",array_filter($tags));
    }
	

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 5; $i++){
            $resultData[] = "http://www.hanyuproperty.com/sale.asp?page={$i}&xingzhengqu=&shangquan=&ditie=&jiage=&mianji=&huxing=&order=&tag=&tuijian=&dujia=&flag1=&flag2=&flag3=";
        }

        return $resultData;
    }
}