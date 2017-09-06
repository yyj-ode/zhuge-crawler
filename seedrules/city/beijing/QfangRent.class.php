<?php namespace beijing;
/**
 * @description 北京Qfang 整租房抓取规则
 * @classname 北京Qfang
 */


class QfangRent extends \city\PublicClass{
	public function house_page(){
		$maxPage= 8562;
		$urlarr =array();
		for($page=1; $page<=$maxPage; $page++){

			$urlarr[] = "http://beijing.qfang.com/appapi/v3_3/room/list?bizType=RENT&currentPage=".$page."&dataSource=BEIJING&pageSize=20";
		}
		return $urlarr;
	}
	/*
	 * 获取列表页
	 */
	public function house_list($url){
			$url = getHtml($url);
	        $json=json_decode($url,true);
        	$result=$json['result'];
			$list=$result['list'];
			$house_info=array();
			foreach($list as $k=>$element){
				$id=$element['id'];
// 				$house_info[$k]="http://beijing.qfang.com/rent/".$id;
//				$house_info[$k]['house_number'] = $id;
//				//tag
//				$tags = str_replace('|','#',$element['labelDesc']);
//                $house_info[$k]['tag']	= $tags;
//				$details = getSnoopy($this->house_info[$count]['source_url']);
//				//付款类型 从pc抓
//				preg_match("/another\_info\sfl\">[\x{0000}-\x{ffff}]*?<span>\((.*)\)<\/span>/u",$details,$pay);
//				$payarr = explode('，',$pay[1]);
//				$house_info[$k]['pay_type'] = $payarr[1];
//				//房屋公共配置
//				preg_match("/<div\sclass\=\"house\_advantage\_item\">[\x{0000}-\x{ffff}]*?<\/div>/u",$details,$config);
//				$configarr = explode("</span>",$config[0]);
//				$configstr = trimall(strip_tags(implode("#",$configarr)));
//				$house_info[$k]['house_configpub'] = $configstr;
				$house_info[] = "http://beijing.qfang.com/appapi/v3_3/room/detail?bizType=RENT&currentPage=1&dataSource=BEIJING&id=".$id."&pageSize=20&qchatPersonId=151585&which=17";
			}
			$house_info = array_merge($house_info);
			return $house_info;
	}
	
	/* 
	 * 获取详情页
	 * 
	 *  */
	public function house_detail($url){
	
		if(!(strstr($url,"qchatPersonId"))){
			$tmp = explode('/', $url);
			$urlct =count($tmp)-1;
			$temp_url_init = $tmp[$urlct];
			$url= "http://beijing.qfang.com/appapi/v3_3/room/detail?bizType=RENT&currentPage=1&dataSource=BEIJING&id=".$temp_url_init."&pageSize=20&qchatPersonId=151585&which=17";
		}
		
        preg_match('/id=(\d+)/',$url,$id);
        $webUrl = "http://beijing.qfang.com/rent/".$id[1];
        $url1 = getHtml($url);
	    $json_2=json_decode($url1,true);
        //下架检测
        $house_info['off_type'] = 2;
        if($json_2['status'] != 'C0000' || $json_2['message'] == '房源已下架或已删除'){
        	$house_info['title']="下架";
            $house_info['off_type'] = 1;
            $house_info['source_url']= $webUrl;
            
            return $house_info;
        }
        if($json_2['result']['rentType'] == '合租' || $json_2['result']['broker']['company'] != 'Q房网·北京' ){
        	$house_info['source_url'] = $webUrl;
        	return false;
        }
        
	    $result_2=$json_2['result'];
	    $house_info['source_url']= $webUrl;
	    $house_info['house_title']=$result_2['title'];
	    $house_info['house_price']=$result_2['price'];
// 	    $pc_html=getSnoopy($house_info['web_url']);
// 	    $pattern = "/<span[\s]*class=\"fl\">([\d]*)年<\/span>/";
// 	    preg_match_all($pattern, $pc_html,$out);
// 	    $house_info['house_built_year']=$out[1][0];
	    $house_info['house_totalarea']=$result_2['area'];
	    $house_info['house_room']=$result_2['bedRoom'];
	    $house_info['house_hall']=$result_2['livingRoom'];
	    $house_info['house_toilet']=$result_2['bathRoom'];
	    $house_info['house_fitment']=$result_2['decoration'];
	    $house_info['house_desc']=trimall($result_2['description']);
	    $house_info['house_toward']=$result_2['direction'];
	    $house_info['house_floor']=$result_2['floor'];
	    $house_info['house_topfloor']=$result_2['totalFloor'];

	    $house_info['source']=5;
	    //source_owner 区分业主来源  1,房主儿网 2，爱直租
	    $house_info['source_owner'] = '';
	    $broker=$result_2['broker'];
	    $house_info['company']=$broker['company'];
	    $house_info['owner_name']=$broker['name'];
	    $house_info['owner_phone']=$broker['phone'];
	    $house_info['cityarea2_id']=$result_2['garden']['region']['name'];
	    $house_info['borough_name']=$result_2['garden']['name'];
	    $json_3=json_decode(getSnoopy("http://beijing.qfang.com/appapi/v3/garden/detail?dataSource=BEIJING&gardenId=".$result_2['garden']['id']),1);
	    $house_info['cityarea_id']=$json_3['result']['region']['parent']['name'];
	    
	    $house_info['borough_id'] = '';
	    $house_info['house_kitchen'] = '';
	    $house_info['house_type'] = $result_2['roomType'];
	    $house_info['house_relet'] = 2;//默认为非转租
	    $house_info['house_style'] = '';//可有可无主次卧
	    $house_info['sex'] = '';
	    $house_info['into_house'] = '';
	    $house_info['pay_method'] = '';
	    $house_info['comment'] = '';
	    $temp_config = str_replace("|","#",$result_2['facilites']);
	    if($temp_config){
	    	$temp_config=$temp_config."#";
	    }
	    $house_info['house_configroom'] = $temp_config;
	    $house_info['is_ture'] = '';
	    $house_info['is_fill'] = 2;
	    $house_info['created'] = time();
	    $house_info['updated'] = time();
	    $house_info['wap_url'] = '';
	    $house_info['pub_time'] = '';
	    $house_info['is_contrast'] = 2;
	    $house_info['deposit'] = $result_2['payAndPawn'];
	    $house_info['house_pic_layout']=str_replace('{size}','600x450',$result_2['layoutIndexPicture']);
	    $pics=$result_2['roomPictures'];
	    
	    if(!empty($pics)){
	        foreach($pics as $index=>$img){
	            $img_url=str_replace('{size}','600x450',$img['url']);
	    
	            if($img_url!== $house_info['house_pic_layout'])
	            {
	                $house_info['house_pic_in_array'][]=$img_url;
	            }
	        }
	    }
	    $house_info['house_pic_in_array'] = array_unique($house_info['house_pic_in_array']);
	    $house_info['house_pic_unit']=implode('|', $house_info['house_pic_in_array']);
	    unset($house_info['house_pic_in_array']);
	    
	    $house_info = array_merge($house_info);
		return $house_info;
	}
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://beijing.qfang.com/rent/h1';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['.dib','text'],
	    ]);
	    return $totalNum;
	    // 	    return 0;
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
            $Tag = \QL\QueryList::Query($html,[
                "isOff" => ['.remove_over','class',''],
//                "404" => ['.error-404','class',''],
            ])->getData(function($item){
                return $item;
            });
            if(empty($Tag)){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100){
    	$this->house_page();
    }
}