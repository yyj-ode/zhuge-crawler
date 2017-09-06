<?php namespace shanghai;
/**
 * @description 上海酷房二手房抓取规则
 * @classname 上海酷房
 */

Class Kufang extends \city\PublicClass
{
    Public function house_page(){

        //城区号0-15
        $dis = array(
            10,11,12,13,14,15,16,17,18,19,20,21,22,24,25,28
        );
        $Pagelist = array(
            66,100,2,100,86,3,11,4,100,3,100,57,46,10,3,1
        );
        $urlarr = [];
        foreach($dis as $index=>$value){
            $url_pre = "http://shanghai.koofang.com/sale/c".$value."/";

            for ($page=1; $page<=$Pagelist[$index]; $page++){
                $urlarr[] = ($page==1) ? $url_pre:$url_pre."pg".$page."/";
            }
        }
        return $urlarr;
    }
    /*
     * 获取列表页
    * @param	dis string 分城区抓取设置为城区信息
    */
    public function house_list($url){
    	$list=$this->getUrlContent($url);
    	//标题和链接
    	preg_match_all("/id=\"biaoti_a([\x{0000}-\x{ffff}]*?)</u", $list, $titles);
    	//价格
    	preg_match_all("/xiangxi_right_price\s*fl\"><span>(\d+?)<\/span>/", $list, $prices);

    	foreach($titles[0] as $i => $v){
    		//链接地址
    		preg_match("/\-(\d+)\./", $v,$href);
    		$house_info[]="http://shanghai.koofang.com/sale/d-".$href[1].".html";

    	}
    	return $house_info;
    }
    
    /*
     * 获取详情
    */
    public function house_detail($source_url){

    	$html = $this->getUrlContent($source_url);
        $house_info['content'] = $html;
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
    	//标题
    	preg_match("/<dt\s*title=([\x{0000}-\x{ffff}]*?)<\/dt>/u", $html, $title);
    	$title = strip_tags ( $title[0]);
    	$title = trimall($title);
    	$house_info ['house_title'] =$title;
    	//价格
    	preg_match("/售价：([\x{0000}-\x{ffff}]*?)万元/u", $html, $price);
    	$price = strip_tags ( $price[0]);
    	$price = trimall($price);
    	$price = str_replace(array("售价：","万元"),"",$price);
    	$house_info ['house_price'] =$price;
    	
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
    	preg_match("/(东北|东南|西北|西南|南北)/", $details[8], $toward);
    	if(empty($toward)){
    		preg_match("/(东|南|西|西)/", $details[8], $toward);
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
    	//dump($citya);die;
    	$house_info['cityarea_id'] = $citya[0];
    	$house_info['cityarea2_id'] = $citya[1];
    	
    	//房源图片
    	preg_match_all("/Details_Page_five_down_a([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $divs);
    	$pics = array();
    	//dump($divs[1]);die;
    	foreach($divs[1] as $k=>$v){
    		//dump($v);die;
    		if(preg_match("/href=\"(\S*?)borough(\S*?)\"/", $v, $img)){
    			$house_info['house_pic_layout']=$img[1]."borough".$img[2];
    			//dump($house_info['house_pic_layout']);die;
    		}else{
    		preg_match("/href=\"(\S*?)\"/", $v, $img);
    		//dump($img[1]);die;	
    		$pics[$k] = $img[1];
    		}
    	}

        \QL\QueryList::Query($html,[
            "house_pic_layout" => ['.Details_Page_three .Details_Page_five_down .Details_Page_five_down_a img','src'],
        ])->getData(function($data)use(&$house_info){
            $data['house_pic_layout'] && $house_info['house_pic_layout'] = $data['house_pic_layout'];
            return $data;
        });

    	if(empty($house_info['house_pic_layout'])){
    		$house_info['house_pic_layout']='';
    	}
    	//dump($pics);die;
    	//$house_info['house_pic_layout'] = empty($pics[0])?'':$pics[0];
//     	for($j=1; $j<count($pics); $j++){
    		 
//     		$house_info['house_pic_unit'][$j] = $pics[$j];
//     	}
    	//dump($pics);die;
    	//$house_info['house_pic_unit'] = array_unique($house_info[$i]['house_pic_unit']);
    	$house_info['house_pic_unit'] = implode('|', $pics);
    	 
    	//类型
    	preg_match("/>(普通住宅|别墅)</u", $html, $type);
    	$house_info['house_type'] = trim($type[1]);
    	 
    	//来源
    	$house_info['source'] = '3';
    	$house_info['company_name']='酷房网';
    	return $house_info;
    }
    //下架判断
    public function is_off($url,$html=''){
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
                    "isOff" => ['.big_qvkuai_top','class',''],
                    "404" => ['.contenttop_err','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]['isOff'] == NULL && $Tag[0]['404'] == NULL){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }


    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
    	$url1 = \QL\QueryList::run('Request', [
    			'target' => "http://shanghai.koofang.com/sale/c1/sw5-0/",
    			])->setQuery([
    					'link' => ['.tongji > span:nth-child(2)','text', '', function($total){
    						return $total;
    					}],
    					])->getData(function($item){
    						return $item['link'];
    					});
    					$num = ceil($url1[0]/30);
    	
    	
        $resultData = [];
        for($i = 1; $i <= $num; $i++){
            $resultData[] = "http://shanghai.koofang.com/sale/c1/sw5-0pg{$i}/";
        }

        return $resultData;
    }
}