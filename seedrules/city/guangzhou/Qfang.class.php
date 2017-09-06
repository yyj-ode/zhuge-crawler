<?php namespace guangzhou;
/**
 * @description 广州Qfang二手房抓取规则
 * @classname 广州Qfang(k-ok)
 */

Class Qfang  extends \city\PublicClass{
    public function curl_get($url)
    {
        $ch_curl = curl_init();
        curl_setopt ($ch_curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch_curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch_curl, CURLOPT_HEADER,false);
        curl_setopt($ch_curl, CURLOPT_HTTPGET, 1);
        curl_setopt($ch_curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt ($ch_curl, CURLOPT_URL,$url);
        $str  = curl_exec($ch_curl);
        curl_close($ch_curl);
        return $str;
    }
    public function house_page(){
//         $gzurl = "http://guangzhou.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=1&dataSource=guangzhou&pageSize=20";
//         $api = $this->curl_get($gzurl);
//         $apiarr = json_decode($api,true);
//         $maxPage=$apiarr['result']['pageCount'];
//         $urlarr = [];
//         for($page=1; $page<=$maxPage; $page++){
//             $urlarr[] = "http://guangzhou.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=".$page."&dataSource=guangzhou&pageSize=20";
//         }
//         return $urlarr;
		dumpp($this->callNewData());die;
    }
	/*
	 * 获取列表页
	*/
	public function house_list($url){
        $city = 'guangzhou';
		$json=json_decode(getHtml($url),1);
		$result=$json['result'];
		$list=$result['list'];
		$house_info=array();
		foreach($list as $element){
			$id=$element['id'];
			//$this->house_info[$this->count]['source_url']="http://".$city.".qfang.com/sale/".$id;
			$house_info[]="http://".$city.".qfang.com/appapi/v3/room/detail?bizType=SALE&dataSource=".$city."&id=".$id."&pageSize=20&qchatPersonId=60057&which=5|"."http://guangzhou.qfang.com/sale/".$id;
		}
		return $house_info;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
//        $source_url = 'http://guangzhou.qfang.com/appapi/v3/room/detail?bizType=SALE&dataSource=guangzhou&id=7071717&pageSize=20&qchatPersonId=60057&which=5|http://guangzhou.qfang.com/sale/70';
		$split = explode('|',$source_url);
        $source_url = $split[0];
        $house_info['source_url'] = $split[1];
		$json_2=json_decode(getHtml($source_url),1);
		
		print_r($json_2['result']['broker']['company']);
		if( $json_2['result']['broker']['company'] != 'Q房网·广州' ){
			$house_info['source_url'] = $split[1];
			return false;
		}
		
		$result_2=$json_2['result'];
		$house_info['house_title']=trimall(HTMLSpecialChars($result_2['title']));
		$house_info['house_price']=$result_2['price']/10000;
		$pc_html=getHtml($house_info['source_url']);
        //下架检测
//        $house_info['off_type'] = $this->is_off($house_info['source_url'],$pc_html);
		$pattern = "/\<span[\s]*class=\"fl\"\>([\d]*)年\<\/span\>/u";
		preg_match_all($pattern, $pc_html,$out);
		$house_info['house_built_year']=$out[1][0];
		
		$house_info['house_totalarea']=$result_2['area'];
		$house_info['house_room']=$result_2['bedRoom'];
		$house_info['house_hall']=$result_2['livingRoom'];
		$house_info['house_toilet']=$result_2['bathRoom'];
		$house_info['house_fitment']=$result_2['decoration'];
		$house_info['house_desc']=trimall(HTMLSpecialChars($result_2['description']));
		$house_info['house_toward']=$result_2['direction'];
		$house_info['house_floor']=$result_2['floor'];
		$house_info['house_topfloor']=$result_2['totalFloor'];
		$house_info['source']=5;
		$house_info['is_contrast']=2;
		$house_info['is_fill']=2;
		$broker=$result_2['broker'];
		$house_info['owner_name']=$broker['name'];
		$house_info['owner_phone']=$broker['phone'];
		$house_info['cityarea2_id']=$result_2['garden']['region']['name'];
		$house_info['borough_name']=$result_2['garden']['name'];
		//$json_3=json_decode(getSnoopy("http://".$city.".qfang.com/appapi/v3/garden/detail?dataSource=".$city."&gardenId=".$result_2['garden']['id']),1);
		$house_info['cityarea_id']=$result_2['garden']['region']['parent']['name'];
		
		$house_info['house_pic_layout']=str_replace('{size}','600x450',$result_2['layoutIndexPicture']);
		$pics=$result_2['roomPictures'];
		if(!empty($pics)){
			foreach($pics as $index=>$img){
				$img_url=str_replace('{size}','600x450',$img['url']);
				if($img_url!= $house_info['house_pic_layout'])
				{
					$house_info['house_pic_unit'][]=$img_url;			 
				}
			}
		}
		$pp = array_unique($house_info['house_pic_unit']);
		$house_info['house_pic_unit']=implode('|', $pp); 
		//创建时间
		$house_info['created']= time();
		//更新时间
		$house_info['updated']= time();
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
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 941){
    	$gzurl = "http://guangzhou.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=1&dataSource=guangzhou&pageSize=20";
        $api = $this->curl_get($gzurl);
        $apiarr = json_decode($api,true);
        $maxPage=$apiarr['result']['pageCount'];
        dumpp($maxPage);die;
        $urlarr = [];
        for($page=1; $page<=$maxPage; $page++){
            $urlarr[] = "http://guangzhou.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=".$page."&dataSource=guangzhou&pageSize=20";
        }
        return $urlarr;
    }
}