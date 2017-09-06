<?php namespace guangzhou;
/**
 * @description 广州链家整租抓取规则
 * @classname 广州链家(k-ok)
 */

Class LianjiaRent extends \city\PublicClass{
    /*
     * 抓取分页
     */
    public function  house_page(){
        $PRE_URL = 'http://gz.lianjia.com/zufang/';
        $html = $this->getUrlContent($PRE_URL);
        preg_match('/区域：([\x{0000}-\x{ffff}]+?)筛选/u',$html,$message);
        preg_match_all('/option\-list([\x{0000}-\x{ffff}]+?)<\/dl>/u',$message[1],$condition);
        $condition = $condition[1];
        //城区搜索条件
        preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[0],$dis);
        preg_match_all('/<a\shref=\"\/zufang\/([\x{0000}-\x{ffff}]+?)\//u',$dis[1],$dis);
        //面积搜索条件
        preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[2],$area);
        preg_match_all('/<a\shref=\"\/zufang\/([\x{0000}-\x{ffff}]+?)\//u',$area[1],$area);
        //房型搜索条件
        preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[3],$room);
        preg_match_all('/<a\shref=\"\/zufang\/([\x{0000}-\x{ffff}]+?)\//u',$room[1],$room);
        $urlarr=array();
        foreach($dis[1] as $DIS){
            foreach($area[1] as $AREA){
                foreach($room[1] as $ROOM){
                    $homeUrl = $PRE_URL.$DIS.'/'.$ROOM.$AREA;
                    //抓取当前搜索条件下的最大页
                    $html = file_get_contents($homeUrl);
                    preg_match('/page\-data=\'([\x{0000}-\x{ffff}]+?)\'/u',$html,$page);
                    $result = json_decode($page[1],1);
                    $maxPage = empty($result['totalPage'])?0:$result['totalPage'];
                    for($page=1;$page<=$maxPage;$page++){
                        $urlarr[]=$homeUrl."p".$page."/";
                    }
                }
            }
        }
        return $urlarr;

    }

    /*
     * 获取列表页
    */
    public function house_list($url){
        $html=$this->getUrlContent($url);
        $house_info = [];
        preg_match("/<ul\s*id=\"house-lst[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $out);
        preg_match_all("/data\-id=\"(\w+?)\"/", $out[0], $ids);
        foreach ($ids[1] as $k=>$v){
            $house_info[$k] = "http://gz.lianjia.com/zufang/".$v.".html";
        }
        return $house_info;
    }

    /*
     * 获取详情
    */
    public function house_detail($source_url){
        $house_info = \QL\QueryList::run('Request',[
            'target' => $source_url,
        ])->setQuery([
            'house_title' => ['.main', 'text', '', function($title){
                return str_replace(array("\t", "\n", "\r", " "),"", $title);
            }],

            'house_price' => ['.total', 'text', ''],

            'house_totalarea' => ['p.lf:nth-child(1)', 'text', '-i', function($house_totalarea){
                return str_replace('平米', '', $house_totalarea);
            }],

            'house_room' => ['p.lf:nth-child(2)', 'text', '-i', function($house_room){
                preg_match("/(\d+)室/", $house_room, $room);
                return trimall($room[1]);
            }],

            'house_hall' => ['p.lf:nth-child(2)', 'text', '-i', function($house_hall){
                preg_match("/(\d+)厅/", $house_hall,$hall);
                return trimall($hall[1]);
            }],

            'house_kitchen' => ['p.lf:nth-child(2)', 'text', '-i', function($house_toward){
                preg_match("/(\d+)卫/", $house_toward, $toward);
                return trimall($toward[1]);
            }],

            'house_toward' => ['p.lf:nth-child(4)', 'text', '-i'],

            'house_floor' => ['p.lf:nth-child(3)', 'text', '-i', function($floor){
                preg_match('/(低|中|高)/u',$floor,$house_floor);
                return $house_floor[1];
            }],

            'house_topfloor' => ['p.lf:nth-child(3)', 'text', '-i', function($floor){
                preg_match('/(\d+)/u',$floor,$topfloor);
                return $topfloor[1];
            }],

            'owner_name' => ['a.name', 'text', ],

            'owner_phone' => ['div.phone:nth-child(3)', 'text', '-span' ,function($phone){
                return trimall($phone);
        }],

            'house_pic_unit' => ['.thumbnail ul li img', 'src', '', function($house_pic_unit){
                return $house_pic_unit;
            }],

            'house_pic_layout' => [],

            'house_fitment' => [],

            'borough_name' => ['.zf-room > p:nth-child(7) > a:nth-child(2)', 'text', ''],

            'house_desc' => ['.featureContent > ul:nth-child(1)', 'text', 'span',function($desc){
                return trimall($desc);
            }],

            'cityarea_id' => ['.zf-room > p:nth-child(8) > a:nth-child(2)','text',''],

            'cityarea2_id' => ['.zf-room > p:nth-child(8) > a:nth-child(3)','text',''],

            'house_number' => ['.houseNum', 'text', '', function($house_number){
//				preg_match("/<input\s*id=\"HouseID\"\s*type=\"hidden\"\s*value=\"(\w+?)\"/u", $house_number, $number);
                $house_number = explode('：', $house_number);
                preg_match("/\w+/", $house_number[1], $number);
                return $number[0];
            }],

            'house_type' => [],

            'house_built_year' => [],

            'house_relet' => [],

            'house_style' => [],
        ])->getData(function($data) use($source_url){
            $data['company_name'] = '链家';
            //下架检测
//            $data['off_type'] = $this->is_off($source_url);
            return $data;
        });
        foreach((array)$house_info as $key => $value){
            if(isset($house_info[$key]['house_pic_unit'])){
                $house_pic_unit[] = $house_info[$key]['house_pic_unit'];
            }
        }
        $house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
        return $house_info[0];
    }
    //统计官网数据
    public function house_count(){
        $PRE_URL = 'http://gz.lianjia.com/zufang/';
        $totalNum = $this->queryList($PRE_URL, [
            'total' => ['.list-head > h2:nth-child(1) > span:nth-child(1)','text'],
        ]);
        return $totalNum;
        // 	    return 0;
    }
    //检测该房源是否下架
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
                    "isOff" => ['.pic-cj','class',''],
                    "404" => ['.sub-tle','text',''],
                    "shelves" => ['.shelves','class',''],
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