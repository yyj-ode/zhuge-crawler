<?php namespace beijing;
/**
 * @description 北京Q房二手房
 * @classname 北京Q房
 */

Class Qfang extends \city\PublicClass
{

	public function house_page()
	{
		$minPage = 0;
		$maxPage = 8179;
		$urlarr = array();
		for ($page = $minPage; $page < $maxPage; $page++) {
			$url = "http://beijing.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=" . $page . "&dataSource=BEIJING&pageSize=20";
//			echo $url."\r\n";
			$urlarr[] = $url;
		}
		return $urlarr;
	}

	/*
	 * 获取列表页
	*/
	public function house_list($url)
	{
		$json = json_decode(getSnoopy($url), 1);
		$result = $json['result'];
		$list = $result['list'];
		$count = 0;
		foreach ($list as $element) {
			//$house_info[] = "http://beijing.qfang.com/sale/" . $element['id'];
			$house_info[] = "http://beijing.qfang.com/appapi/v3/room/detail?bizType=SALE&dataSource=BEIJING&id=" . $element['id'];
		}
		return $house_info;
	}

	/*
	 * 获取详情
	*/
	public function house_detail($source_url)
	{

		$house_url = "http://beijing.qfang.com/sale/";
		$tmp = explode("&id=",$source_url);
		$house_id = $tmp[1];
		$house_info['source_url']=$house_url.$house_id;
//        preg_match('/id=(\d+)/',$source_url,$id);
//        $url = "http://beijing.qfang.com/sale/" . $id[1];
        //下架检测
//        $house_info['off_type'] = $this->is_off($url);
		$json_2 = json_decode(getSnoopy($source_url), 1);
		//下架检测
        $house_info['off_type'] = 2;
		if($json_2['status'] != 'C0000' || $json_2['message'] == '房源已下架或已删除'){
			$house_info['off_type'] = 1;
			return $house_info;
		}
		$result_2 = $json_2['result'];
		$house_info['house_title'] = $result_2['title'];
		$house_info['house_price'] = $result_2['price'] / 10000;
		$pc_html = getSnoopy($house_info['source_url']);
		//echo $this->house_info[$i]['web_url'];die;
		$pattern = "/<span[\s]*class=\"fl\">([\d]*)年<\/span>/";
		preg_match_all($pattern, $pc_html, $out);
		$house_info['house_built_year'] = $out[1][0];

		$house_info['house_totalarea'] = $result_2['area'];
		$house_info['house_room'] = $result_2['bedRoom'];
		$house_info['house_hall'] = $result_2['livingRoom'];
		$house_info['house_toilet'] = $result_2['bathRoom'];
		$house_info['house_fitment'] = $result_2['decoration'];
		$house_info['house_desc'] = trimall($result_2['description']);
		$house_info['house_toward'] = $result_2['direction'];
		$house_info['house_floor'] = $result_2['floor'];
		$house_info['house_topfloor'] = $result_2['totalFloor'];
		$house_info['source'] = 5;
		$broker = $result_2['broker'];
		$house_info['company'] = $broker['company'];
		$house_info['owner_name'] = $broker['name'];
		$house_info['owner_phone'] = $broker['phone'];
		$house_info['cityarea2_id'] = $result_2['garden']['region']['name'];
		$house_info['borough_name'] = $result_2['garden']['name'];
		$json_3 = json_decode(getSnoopy("http://beijing.qfang.com/appapi/v3/garden/detail?dataSource=BEIJING&gardenId=" . $result_2['garden']['id']), 1);
		$house_info['cityarea_id'] = $json_3['result']['region']['parent']['name'];
		$house_info['house_pic_unit_array'] = array();

		$house_info['house_pic_layout'] = str_replace('{size}', '600x450', $result_2['layoutIndexPicture']);
		$pics = $result_2['roomPictures'];
		if (!empty($pics)) {
			foreach ($pics as $index => $img) {
				$img_url = str_replace('{size}', '600x450', $img['url']);
				if ($img_url != $house_info['house_pic_layout']) {
					$house_info['house_pic_unit_array'][] = $img_url;

				}
			}
		}
		$house_info['house_pic_unit_array'] = array_unique($house_info['house_pic_unit_array']);
		$house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit_array']);
		unset($house_info['house_pic_unit_array']);
		return $house_info;
	}
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://beijing.qfang.com/sale';
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
//                    "404" => ['.error-404','class',''],
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
}
