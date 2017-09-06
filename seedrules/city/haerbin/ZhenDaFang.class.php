<?php namespace haerbin;
/**
 * @description 哈尔滨振达房房源抓取规则
 * 
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class ZhenDaFang extends \city\PublicClass{
	public $URL = 'http://www.zhendafang.com';
    public $defaultUrl = 'http://www.zhendafang.com/esf/default.aspx?qid=&bid=&pid=&mid=&fid=&yid=';
    /*
		获取最大page
    */
	public function maxpage(){
		$url = "http://www.zhendafang.com/esf/";
		$html = file_get_contents($url);
		preg_match('/<div\s*class=\"second_c_left\">.*<span\s*style=\"color:red;\">\s*(.*?)<\/span> 套房源<\/div>/', $html,$maxpage);
		return $maxpage[1];

	}
        /**
	 * curl post请求
	 * @param $url
	 * @param array $data
	 * @return bool|mixed
	 */
	function curlHttpPost($url,$data=array(), $jsonDecode=false){
		//对空格进行转义
		$url = str_replace(' ','+',$url);
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, "$url");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch,CURLOPT_TIMEOUT,3);  //定义超时3秒钟
		// POST数据
		curl_setopt($ch, CURLOPT_POST, 1);
		// 把post的变量加上
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //所需传的数组用http_bulid_query()函数处理一下，就ok了
	
		//执行并获取url地址的内容
		$output = curl_exec($ch);
		$errorCode = curl_errno($ch);
		//释放curl句柄
		curl_close($ch);
		if(0 !== $errorCode) {
			return false;
		}
	
		if($jsonDecode){
			$output = json_decode( $output, true );
		}
		return $output;
	}

		public function house_page(){
			$maxpage = $this -> maxpage();
            $urls = [];
            for($index = 1;$index <= $maxpage; $index++){//3761
                $urls[] = $index;
            }
            // var_dump($maxpage);die;
            return $urls;
	}
	
	/*
	 * 获取列表页
	*/
	public function house_list($url){
			$data['__EVENTTARGET'] = 'AspNetPager1';
			$data['__EVENTARGUMENT'] = $url;
            $result = $this->curlHttpPost('http://www.zhendafang.com/esf/default.aspx?qid=&bid=&pid=&mid=&fid=&yid=',$data);
            if(!$result){
            	$result = $this->curlHttpPost('http://www.zhendafang.com/esf/default.aspx?qid=&bid=&pid=&mid=&fid=&yid=',$data);
            }
            preg_match_all("/<div\s*class=\"second_d_sele_all_text_title\"><a\s*href=\'(.*?)\'.*<\/a><\/div>/",$result, $res);
            $urls = [];
            for($i = 0;$i<count($res[1]);$i++){
                $urls[] = $res[1][$i];
            }
            // vadump($urls);die;
            return $urls;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		sleep(2);
		$url = 'http://www.zhendafang.com'.$source_url;
		$html = file_get_contents($url);
        //下架检测
		$house_info['source_url']=$url;
		$house_info['company'] = "哈尔滨世纪振达房地产经纪有限责任公司";
		preg_match_all("/<div\s*class=\"detail_b_left_laimg_text_all_conte\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$public_time);
		$house_info['public_time']=strtotime($public_time[1][0]);
		
		//标题
		preg_match("/<div\s*class=\"detail_b_left_title\">(.*?)<font.*>(.*?)<\/font><\/div>/",$html,$title);
//		var_dump($title);die;
		//商圈
		// var_dump($title[1]);die;
		$house_info['cityarea_id'] = mb_substr($title[1],0,2,'utf-8');
		//总面积
		preg_match('/厅(.*?)㎡/',$title[1],$totalarea);
		$house_info['house_totalarea'] = $totalarea[1];
		// var_dump($totalarea[1]);
		// var_dump($$house_info['house_totalarea']);die;
		$house_info['house_title'] = $title[1].$title[2];
		//房源图片
		preg_match_all("/<div\s*class=\"detail_c_con_ticon_photo\"><img\s*src=\'(.*?)\'.*<\/div>/",$html,$house_pic_unit);
		// var_dump(count($house_pic_unit[1]));die;
		// $house_info['house_pic_layout'] = $house_pic_unit[1][0];
		$house_info['house_pic_unit'] = '';
		// var_dump(count($house_pic_unit[1]));die;
		for($i = 0;$i<count($house_pic_unit[1]);$i++){
			if($i!=0){
				$house_info['house_pic_unit'] .= $house_pic_unit[1][$i].'|';
			}
			
		}
		
	    //价格
		
		$house_price = str_replace('万','',$public_time[1][1]);
	    $house_info['house_price'] = $house_price;
	    //面积
		//厨房默认值==1
	    $house_info['house_kitchen']= 1;
	    //朝向
	    $house_toward = str_replace('向', '', $public_time[1][9]);
	    $house_info['house_toward'] = $house_toward;
	    //小区
	    //$house_borough_name = str_replace('', replace, subject)
	    $house_info['borough_name'] = $public_time[1][3];
	    // $house_info['borough_id'] = '';
	    //城区商圈
        
		$house_info['cityarea2_id'] =$public_time[1][14];//trimall($area_arr[1]);
		// $house_info['cityarea_id'] ='';//trimall($area_arr[0]);
		//装修
		$house_info['house_fitment'] = $public_time[1][10];
		//建筑年代
		$house_built_year = str_replace('年', '',$public_time[1][12]);
		$house_info['house_built_year'] = $house_built_year;

        //户型
//        preg_match("/户型([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$layout);
        preg_match('/(\d+)[居|室]/u',$public_time[1][7],$room);
        //若没有阿拉伯数字匹配，考虑汉字！
        preg_match('/室(.+?)厅/u',$public_time[1][7],$hall);
        preg_match('/(\d+)卫/u',$public_time[1][7],$toilet);
        $house_info['house_room'] = $room[1];
        $house_info['house_hall'] = $hall[1];
        $house_info['house_toilet'] = $toilet[1];

        //楼层
        
       $floor = explode('/',$public_time[1][8]);
        $topfloor = $floor[1];
        $house_info['house_floor'] = $floor[0];
        $house_info['house_topfloor'] = $topfloor;
		
	    $house_info['house_type'] = $public_time[1][11];

	    //联系人
	    preg_match("/<div\s*class=\"detail_b_right_name\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$name);
	    $house_info['owner_name'] = trimall(strip_tags($name[1]));
	    
	    //联系电话
	    preg_match("/<div\s*class=\"detail_b_right_tel\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$phone);
	    $house_info['owner_phone'] = trimall(strip_tags($phone[1]));
		
	    return $house_info;
	}
	public function callNewData()
    {
        
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data [] = $i;
        }
        if (!$data)
            writeLog('ZhenDaFang' . __FUNCTION__, ['url' => $url], $this->log);
        return $data;
    }

 
}

?>
