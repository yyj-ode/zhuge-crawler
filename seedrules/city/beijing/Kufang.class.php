<?php namespace beijing;
/**
 * @description 北京酷房 二手房抓取规则
 * @classname 北京酷房
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');
Class Kufang  extends \city\PublicClass{

    public function house_page(){
		return $this->callNewData();
        $dis = array(
            10,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37
        );
        $pageList = array(
            100,83,100,41,33,3,100,1,57,100,37,48,1,1,1,1,69
        );
        //0-16
        $urlarr=array();
        for($index=0;$index<17;$index++){
            $url_pre = "http://beijing.koofang.com/sale/c".$dis[$index]."/";
            for ($page=1; $page<=$pageList[$index]; $page++){
                $url = ($page==1) ? $url_pre:$url_pre."pg".$page."/";
                $urlarr[]=$url;
            }
        }
        return $urlarr;
    }
    /*
     * 获取列表页
    * @param	dis string 分城区抓取设置为城区信息
    */
    public function house_list($url){
    	$list=getHtml($url);
    	//标题和链接
    	preg_match_all("/id=\"biaoti_a([\x{0000}-\x{ffff}]*?)</u", $list, $titles);
    	//价格
    	preg_match_all("/xiangxi_right_price\s*fl\"><span>(\d+?)<\/span>/", $list, $prices);
    	$house_info=array();
    	foreach($titles[0] as $i => $v){
    		//链接地址
    		preg_match("/\-(\d+)\./", $v,$href);
    		$house_info[]="http://beijing.koofang.com/sale/d-".$href[1].".html";
    	}
    	$house_info = array_merge($house_info);
    	return $house_info;
    }
    
    /*
     * 获取详情
    */
    public function house_detail($source_url){
    	$html = file_get_contents($source_url);
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url,$html);
    	//标题
    	preg_match("/<dt\s*title=([\x{0000}-\x{ffff}]*?)<\/dt>/u", $html, $title);
    	$title = strip_tags ( $title[0]);
    	$title = trimall($title);
    	$house_info['house_title'] =$title;
    	//价格
    	preg_match("/售价：([\x{0000}-\x{ffff}]*?)万元/u", $html, $price);
    	$price = strip_tags ( $price[0]);
    	$price = trimall($price);
    	$price = str_replace(array("售价：","万元"),"",$price);
    	$house_info['house_price'] =$price;
    	
    	preg_match("/<meta\s*name=\"description\"\s*content=\"([\x{0000}-\x{ffff}]+?)\"/u", $html, $content);
    	$content = SBC_DBC($content[1]);
    	 
    	//圣世一品，600万元，2室2厅2卫，产证面积：105平米，单价：57143元/平米，无租约，第12层/共25层,房龄：2008年，东北朝向，精装修-找更多北京圣世一品二手房信息就到北京酷房网
    	$details = explode(',', $content);
    	 
    	$house_info['borough_name'] = $details[0];
    	 
    	preg_match("/(\d+)室(\d+)厅(\d+)卫/", $details[2], $rht);
    	//室
    	$house_info['house_room'] = $rht[1];
    	//厅
    	$house_info['house_hall'] = $rht[2];
    	//卫
    	$house_info['house_toilet'] = $rht[3];
    	 
    	//面积
    	preg_match("/(\d+\.?\d*)平米/", $details[3], $area);
    	$house_info['house_totalarea'] = $area[1];
    	 
    	//楼层信息
    	preg_match("/第(\d+)层\/共(\d+)层/u", $details[6], $floor);
    	//所在楼层
    	$house_info['house_floor'] = $floor[1];
    	//总楼层
    	$house_info['house_topfloor'] = $floor[2];
    	 
    	//建造年份
    	preg_match("/(\d+)年/", $details[7], $year);
    	$house_info['house_built_year'] = $year[1];
    	 
    	//朝向
    	preg_match("/(东北|东南|西北|西南|南北)/u", $details[8], $toward);
    	if(empty($toward)){
    		preg_match("/(东|南|西|西)/u", $details[8], $toward);
    	}
    	$house_info['house_toward'] = $toward[1];
    	 
    	//装修情况
    	preg_match("/([\x{0000}-\x{ffff}]+?)\-/u", $details[9], $fitment);
    	$house_info['house_fitment'] = $fitment[1];
    	 
    	//联系方式及联系人
    	preg_match("/<span\s*class=\"fixed_span1\">([\x{0000}-\x{ffff}]+?)<\/span>/u", $html, $con);
    	$con = trimall($con[1]);
    	$con = explode('：', $con);
    	//发布人姓名
    	$house_info['owner_name'] = trimall($con[0]);
    	//发布人电话
    	$house_info['owner_phone'] = trimall($con[1]);
    	 
    	//城区和商圈
    	preg_match("/<span\s*class=\"fixed_span2\">[\x{0000}-\x{ffff}]+?<\/span>/u", $html, $city);
    	
    	
    	preg_match("/㎡\)([\x{0000}-\x{ffff}]+?)室/u",strip_tags($city[0]),$city);
    	$city = str_replace("&nbsp; ","",$city);
    	$citya=explode(" ", $city[1]);
    	$house_info['cityarea_id'] = $citya[0];
    	$house_info['cityarea2_id'] = $citya[1];
    	
    	//房源图片
    	preg_match_all("/Details_Page_five_down_a([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $divs);
    	$pics = array();
    	foreach($divs[1] as $k=>$v){
    		if(preg_match("/href=\"(\S*?)borough(\S*?)\"/", $v, $img)){
    			$house_info['house_pic_layout']=$img[1]."borough".$img[2];
    		}else{
    		preg_match("/href=\"(\S*?)\"/", $v, $img);
    		$pics[$k] = $img[1];
    		}
    	}
    	if(empty($house_info['house_pic_layout'])){
    		$house_info['house_pic_layout']='';
    	}
    	$house_info['house_pic_unit'] = implode('|', $pics);
    	 
    	//类型
    	preg_match("/>(普通住宅|别墅)</u", $html, $type);
    	$house_info['house_type'] = trim($type[1]);
    	 
    	//来源
    	$house_info['source'] = $this->getSource();
    	$house_info['company_name']='酷房网';
    	$house_info['house_relet'] = '';
    	$house_info['house_style'] = '';
    	$house_info = array_merge($house_info);
    	return $house_info;
    }
    //统计官网数据
    public function house_count(){
        $PRE_URL = 'http://beijing.koofang.com/sale/';
        $totalNum = $this->queryList($PRE_URL, [
            'total' => ['.tongji > span:nth-child(2)','text'],
        ]);
        return $totalNum;
        // 	    return 0;
    }






	/**
	 * 获取最新的房源种子
	 * @param type $num 条数
	 * @author vincent
	 * @return type
	 */
	public function callNewData($num = 100){
		//http://beijing.koofang.com/sale/c1/sw5-0pg2/
		$url = 'http://beijing.koofang.com/sale/c1/sw5-0pg{$page}/';
		$data = [];
		for($i = 1; $i <= $num; $i++){
			$data[] = str_replace('{$page}', $i, $url);
		}
		return $data;
	}


    //下架判断
    public function is_off($url,$html=''){
        return 2;
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $off_type = 1;
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml','html',$url);
            if($newurl == $oldurl){
                $Tag = \QL\QueryList::Query($html,[
                    "isOff" => ['.contenttop_err','text','',function ($item){
                        return preg_match("/存在/",$item);
                    }],
                ])->getData(function($item){
                    return $item;
                });
             foreach ($Tag[0] as $key=>$value) {
                    if($key == "isOff" && $value == 1){
                        return 1;
                    }else{
                        return 2;
                    }
                }
                return 2;
            }
        }
    }
}
                