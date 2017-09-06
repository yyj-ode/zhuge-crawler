<?php namespace shanghai;
/**
 * @description 上海我爱我家整租房源抓取规则
 * @classname 上海我爱我家
 */

class WiwjRent extends \city\PublicClass{
    protected $url = 'http://sh.5i5j.com';

    public function house_page(){
        $urls = \QL\QueryList::run('Request', [
            'target' => $this->url.'/rent',
        ])->setQuery([
            //抓取总条目数，一页显示12个条目，则总页数=总条目数/12
            'link' => ['.font-houseNum', 'text', '', function($total){
                $maxPage = intval($total/12);
                for($minPage = 1; $minPage <= $maxPage; $minPage++){
                    $urlarr[] = $this->url.'/rent/w1n'.$minPage;
                    $url = $this->url.'/rent/w1n'.$minPage;
                }
                return $urlarr;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $urls[0];
    }
    Public function house_list($url = ''){
        $house_info = array();
        for ($i = 1;$i <= 12;$i++){
            $selector ='ul.list-body > li:nth-child('.$i.') > div:nth-child(2) > h2:nth-child(1) > a:nth-child(1)';
            if(!empty($url)){
                $house_list = \QL\QueryList::run('Request', [
                    'target' => $url,
                ])->setQuery([
                    //获取单个房源url
                    'link' => [$selector, 'href', '', function($u){
                        return $this->url.$u;
                    }],
                ])->getData(function($item){
                    return $item['link'];
                });
                $house_info[] = $house_list[0];
            }
            /*else{
                return false;
            }*/
        }
        return $house_info;
    }
    
    public function house_detail($detail_url = ''){
        if(!empty($detail_url)){
            $html = file_get_contents($detail_url);
            $house_info = \QL\QueryList::run('Request', [
                'target' => $detail_url,
            ])->setQuery([
                //标题
                'house_title' => ['.house-tit', 'text', '',function($house_title){
                    return $house_title;
                }],
                //价格
                'house_price' => ['.font-price', 'text', '',function($house_price){
                    return $house_price;
                }],
                //面积
                'house_totalarea' => ['.house-info-2 > li:nth-child(3)', 'text', '', function($house_totalarea){
                    preg_match("/(\d+)平米/", $house_totalarea, $totalarea);
                    return $totalarea[1];
                }],
                //室
                'house_room' => ['.house-info-2 > li:nth-child(1)', 'text', '', function($house_room){
                    preg_match("/(\d+)室/", $house_room,$room);
                    return $room[1];
                }],
                //厅
                'house_hall' => ['.house-info-2 > li:nth-child(1)', 'text', '', function($house_hall){
                    return preg_match("/(\d+)厅/", $house_hall, $hall);
                    return $hall[1];
                }],
                //卫
                'house_toilet' => ['.house-info-2 > li:nth-child(1)', 'text', '', function($house_toilet){
                    return preg_match("/(\d+)卫/", $house_toilet,$toilet);
                    return $toilet[1];
                }],
                //朝向
                'house_toward' => ['.house-info-2 > li:nth-child(5)', 'text', '',function($house_toward){
                    return str_replace('朝向：', '', $house_toward);
                }],
                //所在楼层
                'house_floor' => ['li.house-info-li2:nth-child(6)', 'text', '', function($house_floor){
                    $house_floor = explode('/', $house_floor);
                    $house_floor = explode('：', $house_floor[0]);
                    return str_replace('部', '', $house_floor[1]);
                }],
                //总楼层
                'house_topfloor' => ['li.house-info-li2:nth-child(6)', 'text', '', function($house_topfloor){
                    $house_topfloor = explode('/', $house_topfloor);
                    return str_replace('层', '', $house_topfloor[1]);
                }],
                //联系人姓名
                'owner_name' => ['.mr-t', 'text','',function($owner_name){
                    return $owner_name;
                }],
                //联系人电话
                'owner_phone' => ['.house-broker-tel', 'text', '',function($owner_phone){
                    return $owner_phone;
                }],
                //房源户型图
                'house_pic_layout' => ['#auto-loop > li:nth-child(1)', 'data-src', '', function($house_pic_layout){
                    return $house_pic_layout;
                }],
                //装修
                'house_fitment' => ['li.house-info-li2:nth-child(2)','text','',function($house_fitment){
                    return str_replace('装修：','',$house_fitment);
                }],
                //房源描述
                'house_desc' => ['.zufang-view-icon1 > p:nth-child(3)', 'text', '',function($house_desc){
                    $house_desc = trimall($house_desc);
                    return $house_desc;
                }],
                //房源编号
                'house_number' => ['.house-code > span:nth-child(2)', 'text', '-br', function($house_number){
                    $house_number = explode('房源编号：',$house_number);
                    return $house_number[1];
                }],
                'borough_name' => ['.house-info > li:nth-child(3)','text','',function($borough_name){
                return str_replace('小区：', '', $borough_name);
                }],
                //房源类型
                'house_type' => [],
                //建造年代
                'house_built_year' => ['li.house-info-li2:nth-child(4)','text','',function($house_built_year){
                    preg_match("/(\d+)年/",$house_built_year,$build_year);
                    return $build_year[1];
                }],
                'cityarea_id' => ['section.w-full > div:nth-child(1) > a:nth-child(4)','text','',function($area){
                    return str_replace("租房",'',$area);
                }],
                'cityarea2_id' => ['section.w-full > div:nth-child(1) > a:nth-child(5)','text','',function($area2){
                    return str_replace('租房','',$area2);
                }],
                'house_relet' => [],
                'house_style' => [],
            ])->getData(function($data) use($detail_url){
                //下架检测
//                $data['off_type'] = $this->is_off($detail_url);
                return $data;
            });
            //房源图片
            preg_match("/pic-list([\x{0000}-\x{ffff}]*?)<\/section>/u",$html,$pic_list);
            preg_match_all("/data-src\=\"([\x{0000}-\x{ffff}]*?)\">/u",$pic_list[0],$pics);
            $house_pic_unit = $pics[1][1];
            foreach($pics[1] as $k=>$p){
                if($k!=0){
                    $house_pic_unit = $house_pic_unit.'|'.$p;
                }
            }
            $house_info[0]['house_pic_unit'] = $house_pic_unit;
            $house_info[0]['company_name'] = '快有家';
            $house_info[0]['source_url'] = $detail_url;
//             var_dump($house_info);exit;
//             return $house_info[0];
        }
//         else{
//             return false;
//         }
        return $house_info[0];
    }
    //下架判断
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
                    "isOff" => ['.house_updown','class',''],
                    "404" => ['.main_top','class',''],
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