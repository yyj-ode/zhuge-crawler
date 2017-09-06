<?php namespace shanghai;
/**
 * @description 上海安个家二手房抓取规则
 * @classname 上海安个家
 */

Class Angejia  extends \city\PublicClass{
    /*
    * 抓取
    */
    Public function house_page(){
        $maxPage=3904;
        $url_pre = "http://sale.sh.angejia.com/?page=";
        $urlarr = [];
        for ($page = 1; $page <= $maxPage; $page++){
            $urlarr[] = $url_pre.$page;
        }
        return $urlarr;
    }
    
    /*
     * 获取列表页
    * @param	dis string 分城区抓取设置为城区信息
    */
    public function house_list($url){
    	$html = $this->getUrlContent($url);
    	//标题和链接
    	preg_match("/<div\sclass=\"main\-content[\x{0000}-\x{ffff}]*?pagination\-box\sclearfix\">/u", $html, $list);
    	preg_match_all("/\_blank\"\shref=\"([\x{0000}-\x{ffff}]*?)\"\sdata-origin\=list/u", $list[0], $link);
    	$house_info=array();
    	foreach($link[1] as $i => $v){
    		//链接地址	
    		$house_info[] = $v;
    }
        return array_merge($house_info);
    }
    
    /*
     * 获取详情
    */
    public function house_detail($source_url){
    	$html = $this->getUrlContent($source_url);
        $house_info = [];
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url);
    	//标题
    	preg_match("/\"inventory\-info([\x{0000}-\x{ffff}]*?)block\-title/u", $html, $info);
    	preg_match("/\"inventory\-title\">([\x{0000}-\x{ffff}]*?)<\/h3>/u", $html, $title);
    	$title = strip_tags ( $title[1]);
    	$title = trimall($title);
    	$house_info ['house_title'] =str_replace("&nbsp;","",$title);
    	//价格
    	$info = strip_tags ( $info[1]);
    	$info = trimall($info);
    	preg_match("/(\d+\.?\d*)万/u", $info, $price);
    	$house_info ['house_price'] =$price[1];
    	//室
    	preg_match("/(\d+)室/", $info, $room);
    	$house_info['house_room'] = $room[1];
    	//厅
    	preg_match("/(\d+)厅/", $info, $hall);
    	$house_info['house_hall'] = $hall[1];
    	//卫
    	preg_match("/(\d+)卫/", $info, $toilet);
    	$house_info['house_toilet'] = $toilet[1];
    	//面积
    	preg_match("/(\d+\.?\d*)平/", $info, $area);
    	$house_info['house_totalarea'] = $area[1];
    	 
    	//楼层信息
    	preg_match("/(\d+)\/(\d+)/u", $info, $floor);
    	//所在楼层
    	
    	$house_info['house_floor'] = $floor[1];
    	//总楼层
    	$house_info['house_topfloor'] = $floor[2];
    	 
    	//建造年份
    	preg_match("/年代：(\d{4})/", $info, $year);
    	
    	$house_info['house_built_year'] = $year[1];
    	 
    	//朝向
    	preg_match("/(东北|东南|西北|西南|南北)/", $info, $toward);
    	if(empty($toward)){
    		preg_match("/(东|南|西|西)/", $info, $toward);
    	}
    	$house_info['house_toward'] = $toward[1];
    	 
    	//装修情况
    	preg_match("/(毛坯|普通装修|精装修|豪华装修)/u", $info, $fitment);
    	$house_info['house_fitment'] = $fitment[1];
    	 //房型
    	preg_match("/类型：([\x{0000}-\x{ffff}]*?)小区/u", $info, $type);
    	$house_info['house_type'] = $type[1];
    	//房源编号
    	preg_match("/编号：(\d+)/u", $html, $id);
    	$house_info['house_id'] = $id[1];
    	//联系方式及联系人
    	preg_match("/\"broker\-name\">([\x{0000}-\x{ffff}]+?)<\/a>/u", $html, $name);
    	$name = trimall(strip_tags($name[1]));
    	//发布人姓名
    	$house_info['owner_name'] = $name;
    	//发布人电话
    	preg_match("/tel\-number\">([\x{0000}-\x{ffff}]+?)<\/dl>/u", $html, $phone);
    	$phone = trimall(strip_tags($phone[1]));
    	$phone = str_replace(array('&nbsp;','&#xe608;'), '',$phone);
    	$phone = str_replace('转', ',',$phone);
    	$house_info['owner_phone'] = $phone;
    	 
    	//城区和商圈
    	preg_match("/class=\"address\"([\x{0000}-\x{ffff}]*?)<\/p>/u", $html, $city);
    	$city = trimall($city[1]);
    	preg_match_all("/>([\x{0000}-\x{ffff}]*?)</u", $city, $city);
    	preg_match("/quator\-2\">([\x{0000}-\x{ffff}]*?)<\/a>/u", $html, $city2);
    	$house_info['cityarea_id'] = $city[1][1];
    	$house_info['cityarea2_id'] = $city[1][3];
    	//小区
    	preg_match("/小区：([\x{0000}-\x{ffff}]*?)装修/u", $info, $borough);
    	$house_info['borough_name'] = trimall($borough[1]);
    	
    	//房源图片
    	preg_match_all("/id=\"thumb\_images\"([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $divs);
    	preg_match_all("/<li([\x{0000}-\x{ffff}]*?)<\/li>/u", $divs[0][0], $picss);
    	
    	$pics = array();
    	foreach($picss[1] as $k=>$v){
    		preg_match("/data\-src=\"([\x{0000}-\x{ffff}]*?)\"/u", $v, $img);
    		$pics[$k] = $img[1];
    		}
        $house_info['house_pic_layout']='';
    	$house_info['house_pic_unit'] = implode('|', $pics);
    	//房源描述
    	preg_match("/房源描述([\x{0000}-\x{ffff}]*?)房源动态/u", $html, $desc);
    	$desc = trimall(strip_tags($desc[1]));
    	$house_info['house_desc'] = $desc;
    	//来源
    	$house_info['is_contrast'] = '2';
    	$house_info['is_fill'] = '2';
    	$house_info['source'] = '242';
    	$house_info['company_name']='安个家';
    	return array_merge($house_info);
    	//dump($this->house_info);die;
    }

    //统计官网数据
    public function house_count(){
        $num = [];
        $url = 'http://www.qk365.com/list/f3';
        $html = get_curl_post_data($url, []);
        preg_match('/SortSel\s*fR\">([\x{0000}-\x{ffff}]*?)<\/em>/u',$html,$div);
        preg_match('/总共(\d+)间/',$div[1],$total);
        $total = trimall(strip_tags($total[1]));
        $num['shanghai-QingkeHezu'] = $total;
    
        $List = array(
            'shanghai-Angejia'=>array(
            ),
            'shanghai-DingdingHezu'=>array(
                 'url'=>'http://sh.zufangzi.com/subway/0-22210/',
                 'query'=>'span.pull-right > span:nth-child(1)'
            ),
          'shanghai-DingdingRent'=>array(
                 'url'=>'http://sh.zufangzi.com/area/0-10000000000/',
                 'query'=>'span.pull-right > span:nth-child(1)'
            ),
            'shanghai-Fang'=>array(
            ),
            'shanghai-FangHezu'=>array(
            ),
            'shanghai-FangRent'=>array(
            ),
            'shanghai-FangzhuHezu'=>array(
                'url'=>'http://sh.fangzhur.com/hezu/',
                'query'=>'.result > span:nth-child(1)'
            ),
            'shanghai-FangzhuRent'=>array(
                'url'=>'http://sh.fangzhur.com/rent/',
                'query'=>'.result > span:nth-child(1)'
            ),
            'shanghai-Fdd'=>array(
                'url'=>'http://esf.fangdd.com/shanghai',
                'query'=>'h4.title > span:nth-child(1)'
            ),
            'shanghai-Five8Personal'=>array(
            ),
            'shanghai-Five8PersonalHezu'=>array(
            ),
            'shanghai-Five8PersonalRent'=>array(
            ),
            'shanghai-Hanyu'=>array(
            ),
            'shanghai-Iwjw'=>array(
                'url'=>'http://www.iwjw.com/sale/shanghai/',
                'query'=>'#Order > dt:nth-child(1) > span:nth-child(1)'
            ),
            'shanghai-IwjwRent'=>array(
                'url'=>'http://www.iwjw.com/chuzu/shanghai/',
                'query'=>'#Order > dt:nth-child(1) > span:nth-child(1)'
            ),
            'shanghai-Kufang'=>array(
                'url'=>'http://shanghai.koofang.com/sale/',
                'query'=>'.tongji > span:nth-child(2)'
            ),
            'shanghai-KufangRent'=>array(
                'url'=>'http://shanghai.koofang.com/rent/',
                'query'=>'.tongji > span:nth-child(2)'
            ),
            'shanghai-KyjHezu'=>array(
                'url'=>'http://sh.kuaiyoujia.com/zufangs/hezuhouse/quyu',
                'query'=>'.ay_sumfangyuan > i:nth-child(1)'
            ),
            'shanghai-KyjRent'=>array(
                'url'=>'http://sh.kuaiyoujia.com/zufangs/house/quyu',
                'query'=>'.ay_sumfangyuan > i:nth-child(1)'
            ),
            'shanghai-Lianjia'=>array(
                'url'=>'http://sh.lianjia.com/ershoufang/',
                'query'=>'.list-head > h2:nth-child(1) > span:nth-child(1)'
            ),
            'shanghai-Qfang'=>array(
                'url'=>'http://shanghai.qfang.com/sale',
                'query'=>'.dib'
            ),
            'shanghai-QfangRent'=>array(
                'url'=>'http://shanghai.qfang.com/rent/h1',
                'query'=>'.dib'
            ),
            'shanghai-QfangHezu'=>array(
                'url'=>'http://shanghai.qfang.com/rent/h2',
                'query'=>'.dib'
            ),
            'shanghai-Wiwj'=>array(
                'url'=>'http://sh.5i5j.com/exchange/',
                'query'=>'.font-houseNum'
            ),
            'shanghai-Wiwjhezu'=>array(
                'url'=>'http://sh.5i5j.com/rent/w2',
                'query'=>'.font-houseNum'
            ),
            'shanghai-WiwjRent'=>array(
                'url'=>'http://sh.5i5j.com/rent/w1',
                'query'=>'.font-houseNum'
            ),
            'shanghai-YujianHezu'=>array(
            ),
            'shanghai-Zhongyuan'=>array(
                'url'=>'http://sh.centanet.com/ershoufang/',
                'query'=>'.pagerTxt > span:nth-child(1) > span:nth-child(1) > em:nth-child(1)'
            ),
            'shanghai-ZiroomHezu'=>array(
            ),
        );
        foreach ($List as $key=>$value){
            if(empty($value)){
                $num[$key] = '无';
                continue;
            }
            $num[$key] = $this->queryList($value['url'], [
                'total' => [$value['query'],'text'],
            ])[0]['total'];
        }
//        var_dump($num);die;
        return $num;
        // 	    return 0;
    }

    //下架检测
    /*
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            if(preg_match("/报错/u", $html)){
                return 1;
                //暂未找到已出售页面
            }elseif(preg_match("/remove_over\s*state_bg/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }*/

    /**
     * 抓取房源对应标签(没有标签)
     * @param $web_url
     * @param string $html
     * @return string
     */
    public function getTags($htlm){
        return '';
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){;
        $data = [];
        for($i = 1; $i <= 100; $i++){
            $data[] = "http://sh.angejia.com/sale/?sort=publish-desc&page={$i}";
        }
        return $data;
    }
}