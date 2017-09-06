<?php namespace beijing;
/**
 * @description 北京丁丁合租抓取规则
 * @classname 北京丁丁
 * @author lp
 * @version 1
 * @since 2016-03-25(DONE)
 */
header ( "Content-type: text/html; charset=utf-8" );
ini_set ( "memory_limit", "4000M" );
ini_set ( 'max_execution_time', '0' );


/**
 
 *
 * @author 
 *        
 */
class DingdingHezu extends \city\PublicClass{
    /*
     * 抓取
     */
    Public function house_page() {
        $PRE_URL = 'http://bj.zufangzi.com/area/0-20000000000/';
		$data = $this->queryList($PRE_URL, [
			'total' => ['.navbar-wrapper .color-yellow','text'],
		]);
		$total = $data[0]['total'];
        $maxPage = ceil($total/20);
        $urlarr=array();
        for($page = 1; $page <= $maxPage; $page++){
            $urlarr[]="http://bj.zufangzi.com/area/0-20000000000/".$page;
        }
        return $urlarr;
    }
	
   /*
	* 列表页
	*/
	public function house_list($url){
		$html = getFzSnoopy($url);
		preg_match("/house\-list\">[\x{0000}-\x{ffff}]*?\"container\">/u", $html, $houseList);
		preg_match_all("#http://bj\.zufangzi\.com/detail/[\d]+?\.html#", $houseList[0], $hrefs);
		$hrefs = array_unique($hrefs[0]);
		foreach($hrefs as $link){
			$house_info[] = $link;
		}
		$house_info = array_merge($house_info);
		//dump($house_info);die;
		return $house_info;
	}
	
   /*
	* 获取详情页数据 
	*/
	public function house_detail($source_url) {
       //$source_url = "http://bj.zufangzi.com/detail/136854414335647744.html";
		$html = file_get_contents($source_url);
        //下架检测
        $house_info['off_type'] = $this->is_off($source_url,$html);

	    preg_match('/houseinfo\">([\x{0000}-\x{ffff}]+?)houseinfo\-device\-item/u',$html,$detail);
	    $info = trimall(strip_tags($detail[1]));
//        var_dump($info);
		//标题
		preg_match("/last\-child\">([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$title);
		$title = trimall(strip_tags($title[1]));
		$house_info['house_title']= $title;
		//装修
		preg_match("/(精装修|简装|毛坯)/u", $title, $fitment);
		$house_info['house_fitment']= $fitment[1];
		//出租间面积
		preg_match("/(\d+)平米/u", $info, $area);
		$house_info['house_room_totalarea']= $area[1];
		//朝向
		preg_match("/(东南|东北|西南|西北|南北)/u", $info, $toward);
		if(empty($toward[1])){
		    preg_match("/(东|北|西|南)/u", $info, $toward);
		}
		$house_info['house_toward']= $toward[1];
		//年份
		preg_match('/(\d{4})年/',$info,$year);
		$house_info['house_built_year']= $year[1];
		//室厅卫
		//卧室/居
		preg_match('/(\d+)室/u',$info,$room);
		$house_info['house_room']= $room[1];
		//厅
		preg_match('/(\d+)厅/u',$info,$hall);
		$house_info['house_hall']= $hall[1];
		//卫生间
		preg_match('/(\d+)卫/u',$info,$toilet);
		$house_info['house_toilet'] = $toilet[1];
		//厨房
		preg_match('/(\d+)厨/u',$info,$kitchen);
		$house_info['house_kitchen']= $kitchen[1];
		
		//价格
		preg_match("/monthRent\">([\x{0000}-\x{ffff}]*?)<label>/u",$html,$price);
		$house_info['house_price']= trimall(strip_tags($price[1]));
		//城区商圈
		preg_match("/区域：([\x{0000}-\x{ffff}]*?)<\/div>/u", $detail[1], $city);
		$city = explode("&nbsp;",trimall(strip_tags($city[1])));
		$house_info['cityarea_id'] = $city[0];
		$area2 = explode('(',$city[2]);
		$house_info['cityarea2_id'] = $area2[0];
		//类型
		$house_info['house_type'] = '';
		
		preg_match("/(高|中|低)层/", $info, $f);
		preg_match("/\/共(\d+?)层/", $info, $ft);
		//所在楼层
		$house_info['house_floor']= $f[1];
		//总楼层
		$house_info['house_topfloor'] = $ft[1];
		
		//小区名字
		preg_match("/小区：([\x{0000}-\x{ffff}]*?)<\/div>/u", $detail[1], $borough);
		$borough = trimall(strip_tags($borough[1]));	
		$house_info['borough_name'] = $borough;
		
		//房源编号
		preg_match("/编号：([\w]*?\d+)/", $info, $number);
		$house_info['house_number'] = $number[1];
		
		//图片
		preg_match("/list\-container\">[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $pic_div);
		preg_match_all("/src=\"(.*?)\"/", $pic_div[0], $src);
		$house_info['house_pic_unit'] = array();
		foreach($src[1] as $k=>$v){
			$house_info['house_pic_unit'][] = $v;
		}
		$house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
		$house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
	    //官网电话
	    preg_match('/icon\-phone\">([\x{0000}-x{ffff}]*?)<\/li>/u',$html,$phone);
	    $house_info['service_phone'] = str_replace("-","",trimall(strip_tags($phone[1])));
		//描述
		preg_match('/房东说([\x{0000}-\x{ffff}]*?)<\/i>/u', $html, $desc);
		preg_match_all('/tag\">([\x{0000}-\x{ffff}]*?)<\/span>/u',$desc[1],$des);
		$house_info['house_desc']= implode('，',$des[1]);
		//入住人限制
		$house_info['sex']= "";
		//入住时间
		$house_info['into_house']= "";
		//付款方式 例如信用卡
		$house_info['pay_method']= "";
		//付款类型 例如 押一付三
		preg_match('/(押一付三|月付)/u',$html,$pay_type);
		$house_info['pay_type']= $pay_type[1];
		//标签(房源特色)
		$house_info['tag']= "";
		//房源评价
		$house_info['comment']= "";
        //房主电话
        $house_info['owner_phone']= "";
		//押金
		$house_info['deposit']= "";
		//合租户数
		$house_info['homes']= "";
		//真实度
		$house_info['is_ture']= "";
		//室友信息
		$house_info['friend_info']= "";
		
		//创建时间
		$house_info['created']= time();
		//更新时间
		$house_info['updated']= time();
		preg_match_all('/good-has-1">([\x{0000}-\x{ffff}]+?)<\/div>/u',$html,$config);
		$configs = array();
		foreach($config[1] as $k =>  $v){
		    $configs[] = trimall(strip_tags($v));
		}
		$config = implode('#',$configs);
		$house_info['house_configroom'] =$config;
		$house_info['house_configpub'] =$config;
		
		$house_info['is_contrast'] = 2;
		$house_info['is_fill'] = 2;
		return $house_info;
	}
    //统计官网数据
    public function house_count(){
        $PRE_URL = 'http://bj.zufangzi.com/area/0-20000000000/';
        $totalNum = $this->queryList($PRE_URL, [
            'total' => ['span.pull-right > span:nth-child(1)','text'],
        ]);
        return $totalNum;
    }
    public function is_off($url,$html=''){
        return 2;
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $Tag = \QL\QueryList::Query($html,[
                "isOff" => ['div.btn','text','',function($item){
                    return preg_match("/成交/",$item);
                }],
                "borough_name"=>['div.houseinfo-item:nth-child(1) > div:nth-child(2) > div:nth-child(1) > div:nth-child(2) > a:nth-child(2)','text'],
                "house_price"=>['.rent > span:nth-child(1)','text','-label'],
                "house_totalarea"=>['div.info-line:nth-child(2) > div:nth-child(1) > span:nth-child(2)','text'],
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
            return 1;
        }
    }
}
