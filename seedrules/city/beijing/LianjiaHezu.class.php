<?php namespace beijing;
/**
 * @description 北京链家整租抓取规则
 * @classname 北京链家
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class LianjiaHezu extends \city\PublicClass{

	/*
	 * 抓取分页
	 */
	public function  house_page(){
		$PRE_URL = 'http://bj.lianjia.com/zufang/cy1/';
		$urllist = \QL\QueryList::run('Request', [
			'target' => $PRE_URL,
		])->setQuery([
			'link' => ['body > div.wrapper > div.main-box.clear > div > div.list-head.clear > h2 > span', 'text', '', function($total){
				$total = intval($total/30);
				for($i = 1; $i <= $total; $i++){
					$url[] = 'http://bj.lianjia.com/zufang/pg'.$i.'cy1/';
				}
				return $url;
			}],
		])->getData(function($item){
			return $item['link'];
		});
		return $urllist[0];
	}

	/*
	 * 获取列表页
	*/
	public function house_list($url){
		$html=getSnoopy($url);
		preg_match("/<ul\s*id=\"house-lst[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $out);
		preg_match_all("/data\-id=\"(\w+?)\"/", $out[0], $ids);
    	
		foreach ($ids[1] as $k=>$v){
			$house_info[$k] = "http://bj.lianjia.com/zufang/".$v.".html";
		}
		return $house_info;
	}

	/*
	 * 获取详情
	*/
	public function house_detail($source_url){

        $ziroomTag = $this->queryList($source_url, [
            'ziroomTag' => ['#topImg .imgContainer .ziroomTag', 'text'],
        ]);
		//删除混入的自如合租房源
		if(empty($ziroomTag)){
            $html = $this->getUrlContent($source_url);
            $house_info["tag"] = $this->getTags($source_url,$html);
            //下架检测
            $house_info['off_type'] = $this->is_off($source_url,$html);

			$house_info['source'] = 1;
			$house_info['company'] = "链家官网";
			//标题
			preg_match("/title\-box\sleft\">([\x{0000}-\x{ffff}]+?)<\/h2>/u", $html, $title);
			$title = strip_tags($title[1]);
			$title = str_replace(array("\t", "\r", " "), "", $title);
			$title = SBC_DBC($title);
			$house_info['house_title'] =trimall(HTMLSpecialChars($title));

			preg_match("/<div\s*class=\"info\-box\s*left([\x{0000}-\x{ffff}]+?)<\/div>/u", $html, $detail);
			$info = strip_tags($detail[0]);
			$info = str_replace(array("\t", "\r", " "), "", $info);
			$info = SBC_DBC($info);
			//价格
			preg_match("/(\d+\.?\d*)元/", $info, $price);
			$house_info['house_price']=$price[1];

			//总面积
			preg_match("/(\d+\.?\d*)㎡/", $info, $totalarea);
			$house_info['house_totalarea']=$totalarea[1];

			preg_match("/(\d+?)室/", $info, $room);
			preg_match("/(\d+?)厅/", $info, $hall);
			//室
			$house_info['house_room']=$room[1];
			//厅
			$house_info['house_hall']=$hall[1];

			//朝向
			preg_match("/朝向:([\x{0000}-\x{ffff}]+?)楼层/u", $info, $toward);
			$house_info['house_toward']=$toward[1];

			//楼层
			preg_match("/(高|中|低)楼层/u", $info, $floor);
			preg_match("/共(\d+)/u", $info, $topfloor);
			$house_info['house_floor']=$floor[1];
			$house_info['house_topfloor']=$topfloor[1];

			//建造年份
			preg_match("/(\d+)年/", $info, $year);
			$house_info['house_built_year']=$year[1];

			preg_match("/小区:([\x{0000}-\x{ffff}]+?)\(([\x{0000}-\x{ffff}]+?)\)/u", $info, $cb);
			$ccc = explode('&nbsp;', $cb[2]);
			$house_info['borough_name']=$cb[1];
			$house_info['cityarea2_id'] =$ccc[1];
			$house_info['cityarea_id'] =$ccc[0];

			preg_match("/<span\s*class=\"ft-num[\x{0000}-\x{ffff}]*?<\/span>/u", $html, $contact);
			$contact = strip_tags($contact[0]);
			$house_info['owner_phone'] = str_replace("转", ",", $contact);

			preg_match("/<div\s*class=\"p\-del\s*right\">[\x{0000}-\x{ffff}]*?<\/div>/u", $html, $jjr);
			preg_match("/<p[\x{0000}-\x{ffff}]*?p>/u", $jjr[0], $name);
			$house_info['owner_name'] = trimall(strip_tags($name[0]));

			preg_match("/<div\s*id=\"detail-album[\x{0000}-\x{ffff}]*?<div\s*id=\"view\-top\-people/u", $html, $p_tags);
			preg_match_all("/original=\"(\S*?)\"/", $p_tags[0], $ps);
			$house_info['house_pic_layout'] = $ps[1][0];
			unset($ps[1][0]);
			$pics = array_merge($ps[1]);
			$pics = array_unique($pics);
			$house_info['house_pic_unit']= implode("|", $pics);

			preg_match("/(\[\{\"uid\"[\x{0000}-\x{ffff}]*?\}\])/u", $html, $content);
			$desc = json_decode($content[1],1)[0]['commentContent'];
			$house_info['house_desc'] = trimall($desc);
			$house_info['created'] = time();
			$house_info['updated'] = time();
		}else{
			unset($house_info);
		}
		return $house_info;
	}
	//统计官网数据
	public function house_count(){
	    $PRE_URL = 'http://bj.lianjia.com/zufang/';
	    $totalNum = $this->queryList($PRE_URL, [
	        'total' => ['.list-head > h2:nth-child(1) > span:nth-child(1)','text'],
	    ]);
	    return $totalNum;
	    // 	    return 0;
	}
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
                    "isOff" => ['.shelves','class',''],
//                     "404" => ['.sub-tle','text',''],
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
    /*
     * 抓取房源对应标签
     */
    public function getTags($web_url,$html=''){
        if(empty($html)){
            $html = $this->getUrlContent($web_url);
        }
        $Tags = \QL\QueryList::Query($html,[
            "school" => ['.fang05-ex > span:nth-child(1)','text',''],
            "subway" => ['.fang-subway-ex > span:nth-child(1)','text',''],
            //集中供暖
            "heating"=> ['.heating-ex > span:nth-child(1)','text',''],
            //独立阳台
            "balcony"=>['.independentBalcony-ex > span:nth-child(1)','text',''],
            //独卫
            'bathroom'=>['.privateBathroom-ex > span:nth-child(1)','text',''],
            //随时看房
            'haskey'=>['.haskey-ex > span:nth-child(1)','text',''],
        ])->getData(function($item){
            return $item;
        });
        return implode("#",$Tags[0]);
    }
}