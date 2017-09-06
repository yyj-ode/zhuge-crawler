<?php
namespace nanjing;
/**
 * @description 南京丁丁整租
 * @classname 南京丁丁
 */
class DingdingRent extends \city\PublicClass{
    
    /*
     * 抓取列表页
     * */
    Public function house_page() {
        $PRE_URL = 'http://nj.zufangzi.com/area/0-10000000000/';
    	$html = file_get_contents($PRE_URL);
    	preg_match('/class=\"color\-yellow\s*gaps\">(\d+)<\/span>/',$html,$total);
    	$total = trimall($total[1]);
    	$maxPage = ceil($total/20);
    	$url = [];
    	for($page = 1 ; $page <= $maxPage ; $page++){
    	    $url[] = $PRE_URL.$page;
    	}
    	return $url;
    }
	/*
	 * 列表页
	 */
	public function house_list($url){
		$html =file_get_contents($url);
		preg_match("/house\-list\">[\x{0000}-\x{ffff}]*?\"container\">/u", $html, $houseList);;
		preg_match_all("#http://nj\.zufangzi\.com/detail/[\d]+?\.html#", $houseList[0], $hrefs);
		$hrefs = array_unique($hrefs[0]);
		foreach($hrefs as $i=>$v){
			$house_info[] = $v;
		}
		return $house_info;
	}
	
	/*
	 *获取详情页数据 
	 */
	public function house_detail($source_url) {
			$html = file_get_contents($source_url);
			//下架检测
//			$house_info['off_type'] = $this->is_off($source_url,$html);
		    preg_match('/houseinfo\">([\x{0000}-\x{ffff}]+?)houseinfo\-device\-item/u',$html,$detail);
		    $info = trimall(strip_tags($detail[1]));
			//装修
			preg_match("/(精装修|简装|毛坯)/u", $info, $fitment);
			$house_info['house_fitment']= $fitment[1];
			//出租间面积
			preg_match("/(\d+\.?\d*)[㎡|平米]/u", $info, $area);
			$house_info['house_totalarea']= $area[1];
			
			//朝向
			preg_match("/(东南|东北|西南|西北|南北)/u", $info, $toward);
			if(empty($toward[1])){
			    preg_match("/(东|北|西|南)/u", $info, $toward);
			}
			$house_info ['house_toward']= $toward[1];
			//年份
			preg_match('/(\d{4})年/',$info,$year);
			$house_info ['house_built_year']= $year[1];
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
			preg_match("/(\d+\,?\.?\d*)元/u", $html, $price);
			$house_info['house_price']= str_replace(",","",$price[1]);
			//城区商圈
			preg_match("/区域：([\x{0000}-\x{ffff}]*?)<\/div>/u", $detail[1], $city);
			$city = explode("&nbsp;",trimall(strip_tags($city[1])));
			$house_info['cityarea_id'] = $city[0];
			$house_info['cityarea2_id'] = $city[2];
			//类型
			$house_info['house_type'] = '';
			
			preg_match("/(高|中|低)层/", $info, $f);
			preg_match("/\/共?(\d+?)层/", $info, $ft);
			//所在楼层
			$house_info['house_floor']= $f[1];
			//总楼层
			$house_info['house_topfloor'] = $ft[1];
			
			//小区名字
			preg_match("/小区：([\x{0000}-\x{ffff}]*?)<\/div>/u", $detail[1], $borough);
			$borough = trimall(strip_tags($borough[1]));	
			$house_info['borough_name'] = $borough;
			//标题
			$house_info['house_title'] = $borough.$room[1]."室".$hall[1]."厅".$toilet[1]."卫";
			
			//房源编号
			preg_match("/编号：([\w]*?\d+)/", $info, $number);
			$house_info['house_number'] = $number[1];
			
			//图片
			preg_match("/list\-container\">[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $pic_div);
			preg_match_all("/src=\"(.*?)\"/", $pic_div[0], $src);
			$house_info ['house_pic_unit'] = array();
			foreach($src[1] as $k=>$v){
				$house_info ['house_pic_unit'][] = $v;
			}
			$house_info ['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
			$house_info ['house_pic_unit'] = implode('|', $house_info ['house_pic_unit']);
		    //官网电话
		    preg_match('/icon\-phone\">([\x{0000}-x{ffff}]*?)<\/li>/u',$html,$phone);
		    $house_info['service_phone'] = str_replace("-","",trimall(strip_tags($phone[1])));
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
			
			$house_info['source_url'] = $source_url;
			usleep(500000);
			return $house_info;
	}
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
	                "isOff" => ['div.btn','text','',function ($is_off){
	                    return preg_match("/已下架/",$is_off);   //若已出租，有返回值0，未出租则返回数值1
	                }],
	                "404" => ['.error-warp > dl:nth-child(2) > dt:nth-child(1)','text',''],
	            ])->getData(function($item){
	                return $item;
	            });
	            if($Tag[0]['isOff']== 0 && $Tag[0]['404']==NULL){
	                $off_type = 2;
	            }
	        }
	        return $off_type;
	    }
	    return -1;
	
	}
}

