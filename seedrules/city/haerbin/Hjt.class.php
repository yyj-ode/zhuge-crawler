<?php namespace haerbin;
    /**
     * @description 哈尔滨和美家网 整租房抓取规则
     * @classname 哈尔滨和美家网
     */
class Hjt extends \city\PublicClass
{



    public $PRE_URL = 'http://www.hjtfdc.com/fang.asp';
    private $hhhhhh = '';
    private $_log = false;
    public $current_url = '';
    public $tes1tt="wwww";
    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
		$test ="";
        $test1 = "";
        $test2 ="";
        $url = $this->PRE_URL;
        $urlarr=[];
        $html = getSnoopy($url);
        if(empty($html)){
        	$html = getHtml($url);
        }
        

        $url = \QL\QueryList::Query($html, [
                'link' => ['.sort_list > table > tr > td > a:eq(4)','text','',function($total){
//                    $total = "当前 1/5582 页";
                    $total = explode('/',$total);
//                    var_dump($total);die;
                    preg_match("/(\d+)/",$total[1],$max);
//                    var_dump($max);die;
                    $link = [];
                    for($Page = 1; $Page <= $max[1]; $Page++){
                        $link[] = $this->PRE_URL.'?page='.$Page;
                    }
                    return $link;
                }],
        ])->getData(function($item){
                return $item['link'];
        });
        //var_dump($url);die;
        $urlarr = array_merge($urlarr,$url[0]);
        return $urlarr;
    }

    public function house_list($url)
    {
    	$html = getSnoopy($url);
    	if(empty($html)){
    		$html = getHtml($url);
    	}
        //获取单个房源url,getData方法会自动补全本页面的类似url
        $house_info = \QL\QueryList::Query($html,[
            'link' => ['.sort_list > ul > a','href','',function($u){
                return 'http://www.hjtfdc.com/'.$u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
//        var_dump($house_info);die;
        $html = file_get_contents($url);
        preg_match_all("/城区:(.*?)区/",$html,$area);
        $area = $area[1];
//        var_dump($area[1]);die;
        $result = [];
        $i=0;
        foreach($house_info as $v){
            $result[] = $v.'###'.$area[$i];
            $i++;
        }
//        var_dump($result);die;
        return $result;
    }
    public function house_detail($source_url)
    {
        usleep(2000);
//        echo $source_url;
        $source_url = explode('###',$source_url);
        $html = $source_url[0];
        $html = getSnoopy($html);
        if(empty($html)){
        	$html = getHtml($source_url);
        }

        $house_info = \QL\QueryList::Query($html,[
            'house_price' => ['.red20b:eq(0)', 'text', '', function($price){
                return trimall($price);
            }],
            'house_title' => ['.title > h1', 'text', '', function($title){
                return trimall($title);
            }],
            'house_floor' => ['.base_info > dl:eq(1) > dd:eq(4)', 'text', '', function($floor){
                preg_match("/楼层：第(\d+)层，共(\d+)层/",$floor,$arr);
                return trimall($arr[1]);
            }],
            'house_topfloor' => ['.base_info > dl:eq(1) > dd:eq(4)', 'text', '', function($floor){
                preg_match("/楼层：第(\d+)层，共(\d+)层/",$floor,$arr);
                return trimall($arr[2]);
            }],
            'house_toward' => ['.base_info > dl:eq(1) > dd:eq(1)', 'text', '', function($toward){
                preg_match("/(向)：(.*)/",$toward,$result);
                return trimall($result[2]);
            }],
            'house_room' => ['.base_info > dl:eq(1) > dt:eq(0)', 'text', '', function($house){
		$data = explode('室', $house);
            return str_replace('房型：', '', (trimall($data[0])));
		return intval($data[0]);
                preg_match("/房型：
			(.*) 室/",$house ,$arr);
                return trimall($arr[1]);
            }],
            'house_hall' => ['.base_info > dl:eq(1) > dt:eq(0)', 'text', '', function($house){
            	$data = explode('室', $house);
            	$data = explode('厅', $data[1]);
            	return intval($data[0]);
            	
                preg_match("/房型：
			(.*) 室 (\d) 厅 (\d) 卫/",$house ,$arr);
                return trimall($arr[2]);
            }],
            'house_toilet' => ['.base_info > dl:eq(1) > dt:eq(0)', 'text', '', function($house){
            	$data = explode('厅', $house);
            	$data = explode('卫', $data[1]);
            	return intval($data[0]);
            	
            	preg_match("/房型：
			(.*) 室 (\d) 厅 (\d) 卫/",$house ,$arr);
                return trimall($arr[3]);
            }],
            'house_fitment' => ['.base_info > dl:eq(1) > dd:eq(6)', 'text', '', function($fitment){
                preg_match("/装修：(.*)/",$fitment,$arr);
                return trimall($arr[1]);
            }],
            'use_area' => ['.base_info > dl:eq(0) > dd:eq(0)', 'text', '', function($use_area){
                preg_match("/使用面积：(.*)㎡/",$use_area,$arr);
                return trimall($arr[1]);
            }],
            'owner_name' => ['#agentname', 'text', '', function($owner_name){
                return trimall($owner_name);
            }],
            'service_phone' => ['#mobilecode', 'text', '', function($phone){
                return str_replace('转',',',$phone);
                return trimall($phone);
            }],
            'source_name' => ''
            ,
            'house_totalarea' => ['.base_info > dl:eq(1) > dd:eq(2)', 'text', '', function($totalarea){
                preg_match("/建筑面积：(.*)㎡*/",$totalarea,$arr);
                return trimall($arr[1]);
            }],
            'house_number' => ['#wrap > .main_center > .title > p > span:eq(0)', 'text', '', function($number){
                preg_match("/房源编号：(.*)/",$number,$arr);
                return $arr[1];
            }],
            'borough_name' => ['.title > h1', 'text', '', function($title){
                return trimall($title);
            }],
//            'owner_phone' => ['#mobilecode', 'text', '', function($phone){
//                return str_replace('转',',',$phone);
//                return trimall($phone);
//            }],
            'house_desc' => ['div[class="describe mt10"]:eq(0)', 'text', '', function($desc){
                return trimall($desc);
            }],
            'house_pic_unit' => ['#esfhrbxq_116 > .img_2013 > img', 'src', '', function($pic){
                $pic = 'http://www.hjtfdc.com/'.$pic;
//                var_dump($pic);die;
                return trimall($pic);
            }],

        ])->getData(function($data){
            return $data;
        });
        $house_info[0]['cityarea_id'] = $source_url[1];
        $house_info[0]['source_url'] = $source_url[0];
        $house_info[0]['source_name'] = "好家庭";
        preg_match("/esfhrbxq_116([\x{0000}-\x{ffff}]+?)小区简介/u",$html,$imgs);
        preg_match_all("/src=\"showimg([\x{0000}-\x{ffff}]+?)\"/u",$imgs[1],$img);
        $img_team = '';
        foreach($img[1] as $pic){
            $img_team .= "http://www.hjtfdc.com/showimg".$pic."|";
        }
//        var_dump($imgs);die;
        $house_info[0]['house_pic_unit'] = $img_team;
        return $house_info[0];
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 100; $i++){
            $resultData[] = "http://www.hjtfdc.com/fang.asp?page=".$i;
        }
//        dumpp($resultData);die;
        writeLog( 'Hjt_'.__FUNCTION__, $resultData, $this -> _log);
        return $resultData;
    }
}
