<?php namespace guangzhou;
/**
 * @description 广州Qfang整租抓取规则
 * @classname 广州Qfang整租(k-ok)
 */

class QfangRent  extends \city\PublicClass{
    public function house_page(){
        $maxPage=6112;
        $urlarr = [];
        for($page=1; $page<=$maxPage; $page++){
            $urlarr[]="http://guangzhou.qfang.com/appapi/v3_4/room/list?bizType=RENT&dataSource=GUANGZHOU&pageSize=20&currentPage=".$page;
        }
        return $urlarr;
    }
	/*
	 * 获取列表页
	 */
	public function house_list($url){
	        $json=json_decode(getHtml($url),1);
        	$result=$json['result'];
			$list=$result['list'];
			$house_info=array();
			foreach($list as $k=>$element){
				$id=$element['id'];
				$house_info[] = "http://guangzhou.qfang.com/appapi/v3_4/room/detail?id=".$id."&qchatPersonId=83638900403346&dataSource=GUANGZHOU&bizType=RENT&pageSize=20&which=19|"."http://guangzhou.qfang.com/rent/".$id;
			}
			return $house_info;
	}
	/* 
	 * 获取详情页
	 * 
	 *  */
	public function house_detail($source_url){
        $house_info = [];
        $split = explode('|',$source_url);
        $source_url = $split[0];
        $house_info['source_url'] = $split[1];
	    $json_2=json_decode(getHtml($source_url),1);
	    $result_2=$json_2['result'];
	    $house_info['house_title']=$result_2['title'];
	    $house_info['house_price']=$result_2['price'];
	    $pc_html=getHtml($house_info['source_url']);
        //下架检测
//        $house_info['off_type'] = $this->is_off($house_info['source_url'],$pc_html);
	    $pattern = "/<span[\s]*class=\"fl\">([\d]*)年<\/span>/";
	    preg_match_all($pattern, $pc_html,$out);
	    $house_info['house_built_year']=$out[1][0];
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
	    $house_info['owner_name']=$broker['name'];
	    $house_info['owner_phone']=$broker['phone'];
	    $house_info['cityarea2_id']=$result_2['garden']['region']['name'];
	    $house_info['borough_name']=$result_2['garden']['name'];
	    $json_3=json_decode(getHtml("http://guangzhou.qfang.com/appapi/v3_4/garden/detail?qchatPersonId=83638900403346&dataSource=GUANGZHOU&gardenId=".$result_2['garden']['id']),1);
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
	    $house_info['deposit'] = '';
	    $house_info['house_configroom'] = '';
	    $house_info['is_ture'] = '';
	    $house_info['is_fill'] = 2;
	    $house_info['created'] = time();
	    $house_info['updated'] = time();
	    $house_info['wap_url'] = '';
	    $house_info['pub_time'] = '';
	    $house_info['is_contrast'] = 2;
	    $house_info['house_pic_layout']=str_replace('{size}','600x450',$result_2['layoutIndexPicture']);
	    $pics=$result_2['roomPictures'];
	    if(!empty($pics)){
	        foreach($pics as $index=>$img){
	            $img_url=str_replace('{size}','600x450',$img['url']);
	    
	            if($img_url!== $house_info['house_pic_out'])
	            {
	                $house_info['house_pic_in_array'][]=$img_url;
	            }
	        }
	    }
	    $house_info ['house_pic_in_array'] = array_unique($house_info ['house_pic_in_array']);
	    $house_info['house_pic_unit']=implode('|', $house_info['house_pic_in_array']);
	    unset($house_info['house_pic_in_array']);	    
	    return $house_info;
	}

    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = getHtml($url);
            }
            //抓取下架标识
            $off_type = 1;
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml','html',$url);
            if($newurl == $oldurl){
                $Tag = \QL\QueryList::Query($html,[
                    "isOff" => ['.remove_over','class',''],
                    "404" => ['.error-404','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if(empty($Tag)){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }
}