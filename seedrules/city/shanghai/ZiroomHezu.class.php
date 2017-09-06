<?php namespace shanghai;
/**
 * @description 上海自如友家 合租房抓取规则
 * @classname 上海自如友家 OK
 */
Class ZiroomHezu extends \city\PublicClass{

    protected $url = 'http://sh.ziroom.com';
    /*
     * 抓取数据
     */
    public function house_page(){
        $urls = \QL\QueryList::run('Request', [
            'target' => $this->url.'/z/nl/z2.html',
        ])->setQuery([
            //抓取总条目数，一页显示12个条目，则总页数=总条目数/12
            'link' => ['#page > span:nth-child(9)', 'text', '', function($total){
                preg_match("/共(\d+)页/", $total, $pages);
                $maxPage = $pages[1];
                $urlarr = [];
                for($minPage = 1; $minPage <= $maxPage; $minPage++){
                    $u = $this->url.'/z/nl/z2.html?p='.$minPage;
                    $urlarr[] = $u;
//                     var_dump($urlarr);die;
                }
                return $urlarr;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $urls[0];
    }

    /*
	 * 获取列表页
	 * */
    Public function house_list($url = ''){
        $house_info = array();
        for ($i = 1;$i <= 20;$i++){
            $selector ='li.clearfix:nth-child('.$i.') > div:nth-child(2) > h3:nth-child(1) > a:nth-child(1)';
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
    /*
     * 获取详情页
     *
     *  */
    public function house_detail($detail_url = ''){
        if(!empty($detail_url)){

            $house_info = \QL\QueryList::run('Request', [
                'target' => $detail_url,
            ])->setQuery([
                //标题
                'house_title' => ['.room_name > h2:nth-child(1)', 'text', '',function($house_title){
                    return $house_title;
                }],
                //小区名称
                'borough_name' =>['.node_infor','text','',function($borough_name){
                    $names = explode('&gt;',$borough_name);
                    $name = str_replace('租房信息','',$names[count($names)-1]);
                    return $name;
                }],
                //价格
                'house_price' => ['.room_price', 'text', '',function($house_price){
                    $house_price = str_replace('￥','',$house_price);
                    return $house_price;
                }],
                //面积
                'house_room_totalarea' => ['.detail_room > li:nth-child(1)', 'text', '', function($house_room_totalarea){
                    preg_match("/(\d+)㎡/", $house_room_totalarea, $totalarea);
                    return $totalarea[1];
                }],
                //室
                'house_room' => ['.detail_room > li:nth-child(3)', 'text', '-span', function($house_room){
                    preg_match("/(\d+)室/", $house_room,$room);
                    return $room[1];
                }],
                //厅
                'house_hall' => ['.detail_room > li:nth-child(3)', 'text', '-span', function($house_hall){
                    preg_match("/(\d+)厅/", $house_hall, $hall);
                    return $hall[1];
                }],
                //卫
                'house_toilet' =>[],
                //朝向
                'house_toward' => ['.detail_room > li:nth-child(2)', 'text', '',function($house_toward){
                    return str_replace('朝向：', '', trimall($house_toward));
                }],
                //所在楼层
                'house_floor' => ['.detail_room > li:nth-child(4)', 'text', '', function($house_floor){
                    $house_floor = explode('/', trimall($house_floor));
                    $house_floor = explode('：', $house_floor[0]);
                    return  $house_floor[1];
                }],
                //总楼层
                'house_topfloor' => ['.detail_room > li:nth-child(4)', 'text', '', function($house_topfloor){
                    $house_topfloor = explode('/', trimall($house_topfloor));
                    return str_replace('层', '', $house_topfloor[1]);
                }],
                //联系人姓名
                'owner_name' => ['p.pr:nth-child(3)', 'text','',function($owner_name){
                    $owner = explode('：',$owner_name);
                    return $owner[1];
                }],
                //联系人电话
                'service_phone' => ['.tel', 'text', '',function($service_phone){
                    return trimall($service_phone);
                }],
                //装修
                'house_fitment' =>[],
                //户型图
                'house_pic_layout' =>[],
                //房源图片
                'house_pic_unit'=>['.lof-navigator > li > div:nth-child(1) > img','src','',function($item){
                    return $item;
                }],
                //房源描述
                'house_desc' => ['.aboutRoom', 'text', '-h3',function($house_desc){
                    return trimall($house_desc);
                }],
                //房源编号
                'house_number' => ['.fb', 'text', '-strong', function($house_number){
                    return trimall($house_number);
                }],
                //房源类型
                'house_type' => [],
                //建造年代
                'house_built_year' => [],
                'house_relet' => [],
                'house_style' => [],
                'cityarea_id'=> ['.node_infor > a:nth-child(2)',"text",'',function($item){
                    return str_replace("合租","",$item);
                }],
                'cityarea2_id'=>['.node_infor > a:nth-child(3)',"text",'',function($item){
                    return str_replace("公寓出租","",$item);
                }],
            ])->getData(function($data) use($detail_url){

                return $data;
            });
            foreach((array)$house_info as $key => $value){
                if(isset($house_info[$key]['house_pic_unit'])){
                    $house_pic_unit[] = $house_info[$key]['house_pic_unit'];
                }
            }
            //下架检测
//            $house_info[0]['off_type'] = $this->is_off($detail_url);
            $house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
            $house_info[0]['company_name'] = '自如友家';
            $house_info[0]['source_url'] = $detail_url;
            return $house_info[0];
        }
        else{
            return false;
        }
    }
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $newurl = get_jump_url($url);
            if($newurl == $url){
                $Tag = \QL\QueryList::Query($html,[
                    "isOff" => ['.view','text','',function($item){
                        if(!preg_match("/出租/",$item)){
                            return "";
                        }
                        return "fff";
                    }],
                    "404" => ['.nopage','class',''],#zreserve
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]['404']==NULL && $Tag[0]['sold']==NULL){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return 1;
        }
        return -1;
    }
}