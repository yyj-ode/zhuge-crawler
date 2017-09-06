<?php namespace shenzhen;
/**
 * @description 深圳链家地产整租房抓取规则
 * @classname 深圳链家(k-ok)
 */

Class LianjiaRent extends \city\PublicClass{

    Public function house_page(){
        $PRE_URL = 'http://sz.lianjia.com/zufang/';
        //获取搜索条件
        $this->get_condition($PRE_URL);
        $num = count($this->url_list);
        $urlarr = [];
        for($n=0; $n<$num; $n++){
            //从当前条件首页抓取最大页
            $maxPage = $this->get_maxPage($this->url_list[$n]);
            for($page=1; $page<=$maxPage; $page++){
                $urlarr[] = $this->url_list[$n]."p".$page."/";
            }
        }
        return $urlarr;
    }

    /*
     * 获取列表页
    */
    public function house_list($url){
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['#house-lst > li > div:nth-child(2) > h2:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
                return $u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
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
            'house_price' => ['span.total', 'text', '', function($price){
                return $price;
            }],

            'house_totalarea' => ['p.lf:nth-child(1)', 'text', '', function($house_totalarea){
                return str_replace('平米', '', $house_totalarea);
            }],

            'house_room' => ['p.lf:nth-child(2)', 'text', '-i', function($house_room){
                preg_match("/(\d+)室/", $house_room, $hr);
                return $hr[1];
            }],

            'house_hall' => ['p.lf:nth-child(2)', 'text', '-i', function($house_hall){
                preg_match("/(\d+)厅/", $house_hall, $hh);
                return $hh[1];
            }],

            'house_kitchen' => ['p.lf:nth-child(2)', 'text', '-i', function($house_kitchen){
                preg_match("/(\d+)厨/", $house_kitchen, $hk);
                return $hk[1];
            }],
            'house_toilet' => ['p.lf:nth-child(2)', 'text', '-i', function($house_toilet){
                preg_match("/(\d+)卫/", $house_toilet, $ht);
                return $ht[1];
            }],


            'house_toward' => ['p.lf:nth-child(4)', 'text', '-i'],

            'house_floor' => ['p.lf:nth-child(3)', 'text', '-i', function($house_floor){
                preg_match('/(低楼层|中楼层|高楼层)/',$house_floor,$hf);
                return $hf[1];
            }],

            'house_topfloor' => ['p.lf:nth-child(3)', 'text', '-i', function($house_topfloor){
                preg_match('/共(\d+)层/',$house_topfloor,$htf);
                return $htf[1];
            }],

            'owner_name' => ['.brokerName > span:nth-child(1)', 'text', ],

            'service_phone' => ['div.phone', 'text', '', function ($op){
                $op = trimall($op);
                return str_replace('转',',',$op);
            }],

            'house_pic_unit' => ['.thumbnail > ul:nth-child(1) > li > img:nth-child(1)', 'src', '', function($house_pic_unit){
                return $house_pic_unit;
            }],

            'house_pic_layout' => [],

            'house_fitment' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(9)','text','-span'],

            'borough_name' => ['.zf-room > p:nth-child(7) > a:nth-child(2)', 'text', ''],

            'house_desc' => ['div.noData:nth-child(2)','text',''],

            'house_number' => ['.houseRecord > span:nth-child(2)', 'text', '-a -br', function($house_number){
                return trimall($house_number);
            }],

            'house_type' => [],

            'house_built_year' => ['div.area:nth-child(3) > div:nth-child(2)','text','',function($house_built_year){
                preg_match('/(\d+)年建/',$house_built_year,$hby);
                return $hby[1];
            }],

            'house_relet' => [],

            'house_style' => [],
            'cityarea_id'=>['.zf-room > p:nth-child(8) > a:nth-child(2)','text',''],
            'cityarea2_id'=>['.zf-room > p:nth-child(8) > a:nth-child(3)','text','']
        ])->getData(function($data) use ($source_url){
            $data['company_name'] = '链家';
            //下架检测
//            $data['off_type'] = $this->is_off($source_url);
            return $data;
        });
        $house_pic_unit = [];
        foreach((array)$house_info as $key => $value){
            if(isset($house_info[$key]['house_pic_unit'])){
                $house_pic_unit[] = $house_info[$key]['house_pic_unit'];
            }
        }
        $house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
        return $house_info[0];
    }

    /*
     * 获取各类搜索条件
     */
    //用于存放各个搜索条件对应的列表页第一页
    private  $url_list = array();
    Public function get_condition($PRE_URL){
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
        foreach($dis[1] as $DIS){
            foreach($area[1] as $AREA){
                foreach($room[1] as $ROOM){
                    $this->url_list[] = $PRE_URL.$DIS.'/'.$ROOM.$AREA;
                }
            }
        }
    }

    /*
     * 获取搜索条件下的最大页
     */
    Public function get_maxPage($url){
        $maxPage = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'num' => ['.list-head > h2:nth-child(1) > span:nth-child(1)', 'text', '', function($u){
                return ceil($u/30);
            }],
        ])->getData(function($item){
            return $item['num'];
        });
        return $maxPage[0];
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