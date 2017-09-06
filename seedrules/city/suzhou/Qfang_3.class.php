<?php namespace nanjing;
/**
 * @description 南京Q房二手房
 * @classname 南京Q房
 */

Class Qfang extends \city\PublicClass{

    /*
     * 抓取
     */
    public function house_page(){
        $pre_url = 'http://nanjing.qfang.com/appapi/v3/room/list?bizType=SALE&pageSize=30&page=2';
		/*
		 * 接口抓取 20160702_BJF
		 */
		$api = getSnoopy($pre_url);
		$apiarr = json_decode($api,true);
		$maxPage=$apiarr['result']['pageCount'];
		$urlarr = [];
		for($page=1; $page<=$maxPage; $page++){
			$urlarr[] = 'http://nanjing.qfang.com/appapi/v3/room/list?bizType=SALE&dataSource=nanjing&pageSize=30&currentPage='.$page;
		}
		return $urlarr;
    }
    
	/*
	 * 获取列表页 20160702_bjf
	 */
	public function house_list($url){
		$lists = [];
		$baseName = pathinfo(($url), PATHINFO_BASENAME);
		// 最新的
		if(preg_match('/^f\d+$/', $baseName)){
			$html = getSnoopy($url);
			\QL\QueryList::Query($html,[
				'list' => ['#cycleListings > ul > li  .pic-house > a', 'href',]
			])->getData(function($item)use(&$lists){
				$item['list'] && $lists[] = 'http://nanjing.qfang.com' . $item['list'];
			});
		}else{
			$requestList =json_decode(getSnoopy($url), true);
			$list = $requestList['result']['list'];
			foreach($list as $element){
				$id=$element['id'];
				$lists[]="http://nanjing.qfang.com/sale/".$id;
			}
		}
		return $lists;
	}
	
	/*
	 * 获取详情
	 */
	public function house_detail($url){
		$app_id = explode("/sale/",$url);
		$app_url = "http://nanjing.qfang.com/appapi/v3/room/detail?bizType=SALE&dataSource=NANJING&id=" . $app_id[1];
		$result_2 = json_decode(getSnoopy($app_url));
		echo $result_2;
		$house_info['house_title']=trimall(HTMLSpecialChars($result_2['title']));
		$house_info['house_price']=$result_2['price']/10000;
		$pc_html=getSnoopy($url);
		//下架检测

		$pattern = "/(\d{4})年/u";
		preg_match($pattern, $pc_html,$out);
		$house_info['house_built_year']=$out[1];

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
		$broker=$result_2['broker'];
		$house_info['owner_name']=$broker['name'];
		$house_info['owner_phone']=$broker['phone'];
		$house_info['cityarea2_id']=$result_2['garden']['region']['name'];
		$house_info['borough_name']=$result_2['garden']['name'];
		dumpp($house_info);die;
		$json_3=json_decode(getSnoopy("http://shanghai.qfang.com/appapi/v3/garden/detail?dataSource=shanghai&gardenId=".$result_2['garden']['id']),1);
		$house_info['cityarea_id']=$result_2['garden']['region']['parent']['name'];
		$house_pic_unit_array=array();

		$house_info['house_pic_layout']=str_replace('{size}','600x450',$result_2['layoutIndexPicture']);
		$pics=$result_2['roomPictures'];
		if(!empty($pics)){
			foreach($pics as $index=>$img){
				$img_url=str_replace('{size}','600x450',$img['url']);
				if($img_url!= $house_info['house_pic_layout'])
				{
					$house_pic_unit_array[]=$img_url;

				}
			}
		}
		$house_pic_unit_array = array_unique($house_pic_unit_array);
		if(empty($house_pic_unit_array)){
			preg_match('/guideMinmapCon[\x{0000}-\x{ffff}]*?<\/ul>/u',$pc_html,$piclink);
			preg_match_all('/data\-src=\"([\x{0000}-\x{ffff}]*?)\"/u',$piclink[1],$pic);
			$house_pic_unit_array = array_unique($pic[1]);
		}
		$house_info['house_pic_unit']=implode('|', $house_pic_unit_array);
		return $house_info;
	}

    //判断该房源是否下架
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