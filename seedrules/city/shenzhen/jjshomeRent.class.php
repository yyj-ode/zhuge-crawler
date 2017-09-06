<?php namespace shenzhen;
/**
 * @description 深圳家家順整租房抓取规则
 * @classname 深圳家家順（k-ok）
 */

Class jjshomeRent extends \city\PublicClass{
    /*
       * @author kevin
       * 家家順用搜索条件的方式获取页面列表
       * 参数http://www.iwjw.com/sale/shanghai/ip3ia2rn1p2/?kw=%E6%99%AE%E9%99%80&t=1
       */
    Public function house_page(){
        $minPage = empty($_GET['minPage'])?1:$_GET['minPage'];
        $maxPage = empty($_GET['maxPage'])?2360:$_GET['maxPage'];
        $urlarr = [];
        for($page = $minPage; $page < $maxPage; $page ++) {
            $urlarr[] = "http://shenzhen.jjshome.com/zf/index/n".$page;
        }
        return $urlarr;
    }
  /*
   * 获取列表页
  */
  public function house_list($url){
  	$html = $this->getUrlContent($url);
  	//取得列表标签
  	preg_match ( "/list\-left\"([\x{0000}-\x{ffff}]*?)<div\sclass=\"clearfix/u", $html, $ol );
  	preg_match_all ( "/<a\shref=\"(http:[\x{0000}-\x{ffff}]*?)\"/u", $ol [1], $lis );
  	//取得列表页的的链接
  	$lis = array_unique($lis[1]);
  	$house_info = [];
  	foreach ( $lis  as $v ) {
  		$house_info[] = $v;
  	}
      return $house_info;
  }
  
  /*
   * 获取详情
  */
  public function house_detail($source_url){
      $html = $this->getUrlContent($source_url);
      $house_info= [];
  	//下架检测
//  	$house_info['off_type'] = $this->is_off($source_url);
  	// 来源
  	$house_info['source'] = 12;
  	// 经济公司
  	$house_info['company_name'] = '家家順';
  	//标题
  	preg_match ( "/assessTitle\">([\x{0000}-\x{ffff}]+?)<\/span>/u", $html, $title );
  	
  	$title = strip_tags ( $title[1]);
  	
  	//$title = explode("二手房", $title);
  	$house_info['house_title'] =trimall(HTMLSpecialChars($title));
  	  	
  	//<meta name="description" content="该房简介：1室1厅1卫，40m²，65万，1.6万/平，低层 / 3层，普装，2010年，点击查看更多爱盛家园小区信息与参考房价">
  	preg_match ( "/text\sc666\">([\x{0000}-\x{ffff}]*?)<div\sclass=\"yuyue\sfl\">/u", $html, $meta);
  	$details = $meta[1];
  	$info = trimall(strip_tags($details));
  	//小区名
  	preg_match ( "/小区：([\x{0000}-\x{ffff}]+?)查看地图/u", $info, $title );
  	$house_info['borough_name'] = $title[1];
  	preg_match ( "/(\d+\.?\d*)元/", $info, $price );
  	$house_info['house_price'] = $price[1];
  	preg_match ( "/(\d+)室/", $info, $room );
  	preg_match ( "/(\d+)厅/", $info, $hall );
  	preg_match ( "/(\d+)卫/", $info, $toilet );
  	preg_match ( "/(\d+\.?\d*)㎡/", $info, $total );
  	preg_match ( "/(中|高|低)楼层/", $info, $floor );
  	preg_match ( "/(\d+)层/", $info, $topflooor );
  	preg_match("/(普通装修|精装修|豪华装修|毛坯)/",$info, $fitment);
  	
  	preg_match ( "/(\d{4})年/", $details, $year );
  	// 室
  	$house_info['house_room'] = $room [1];
  	// 厅
  	$house_info['house_hall'] = $hall [1];
  	// 卫
  	$house_info['house_toilet'] = $toilet [1];
  	// 面积
  	$house_info['house_totalarea'] = $total [1];
  	// 建造年份
  	$house_info['house_built_year'] = $year [1];
  	// 所在楼层
  	$house_info['house_floor'] = $floor [1];
  	// 总楼层
  	$house_info['house_topfloor'] = $topflooor [1];
  	// 装修情况
  	$house_info['house_fitment'] = $fitment [1];
	
  	preg_match("/朝向：([\x{0000}-\x{ffff}]+?)楼层/u",$info, $toward);
  	// 装修情况
  	$house_info['house_toward'] = trimall(strip_tags($toward[1]));
  	preg_match("/like\-p\smt10\">([\x{0000}-\x{ffff}]+?)<\/span> /u",$html, $city);
  	$city = explode('&nbsp', $city[1]);
  	$city = explode('-', $city[0]);
  	$house_info['cityarea_id'] = trim ( $city[0] );
  	$house_info['cityarea2_id'] = trim ( $city[1] );

  	//图片，房型图
  	preg_match( "/slt\_disc\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $pics );
  	preg_match_all ( "/src=\"([\x{0000}-\x{ffff}]*?)\"/u", $pics[1], $picss);
  	
  	$house_info['house_pic_layout'] = $picss[1][0];
  	unset ($picss[1][0]);
  	$house_info['house_pic_unit'] = implode ( "|", $picss[1] );
  	
  	//经纪人信息
  	preg_match("/(\d{3}\-\d{4}\-\d{4})/u",$info, $phone);
  	$phone = str_replace('-','',$phone[1]);
      if(empty($phone)){
          preg_match("/(\d+转\d+)/u",$info, $phone);
      }//var_dump($info);die;
      $phone = str_replace('转',',',$phone[1]);
  	$house_info['owner_phone'] = $phone;
  	preg_match("/workerName\">([\x{0000}-\x{ffff}]*?)<\/a>/u",$html, $name);
  	$house_info['owner_name'] = trimall($name[1]);
  	//房源描述，多描述用|分割
  	preg_match_all("/fp-con\">([\x{0000}-\x{ffff}]*?)<p\sclass=\"tr\spb10\">/u",$html, $descs);
  	$desc = array();
  	foreach($descs[1] as $k=> $v){
  	    $desc[] = trimall(strip_tags($v));
  	}
  	$house_info['house_desc'] = implode ( "|", $desc);
  	$house_info['is_contrast'] = 2;
  	$house_info['is_fill'] = 2;
  	$house_info['house_relet'] = '';
  	$house_info['house_style'] = '';
  	//创建时间
  	$house_info['created']= time();
  	//更新时间
  	$house_info['updated']= time();
      return $house_info;
  }
   //检测该房源是否下架
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            if(preg_match("/<div\s*class=\"xiajia\s*mt15\s*tc\s*clearfix\"\s*>([\x{0000}-\x{ffff}]*?)<\/div>/u", $html)){
                return 1;
            }elseif(preg_match("/404错误/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}