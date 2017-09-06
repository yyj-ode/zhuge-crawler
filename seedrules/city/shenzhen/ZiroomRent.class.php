<?php namespace shenzhen;
/**
 * @description 深圳自如 整租房抓取规则
 * @classname 深圳自如
 */

Class ZiroomRent  extends \city\PublicClass{
    Public function house_page(){
        //从端口抓取
        //翻页规律：$Parameters ["start"]=0,1,2…..  $Parameters ["length"]=10(每一页抓取的数量可自定义)
        $maxPage = 100 ;
        $urlarr =array();
        for($page = 1; $page <= $maxPage; $page ++) {
            //将页码拼接到url中
            $urlarr[]= "http://interfaces.ziroom.com/index.php?_p=api_mobile&_a=searchHouse&city_code=440300|".$page;
        }
        return $urlarr;
    }

    /*
     * 获取列表页
     */
    public function house_list($url){
//        $url = 'http://interfaces.ziroom.com/index.php?_p=api_mobile&_a=searchHouse&city_code=440300|1';
        $source_url = explode("|",$url)[0];
        $page = explode("|",$url)[1];
        $house_info = array ();
        $Parameters = array ();
        $Parameters ["start"] = $page;
        $Parameters ["length"] = 10;
        $Parameters ["house_tags"] = 1;
        //合租为0整租为1
        $Parameters ["house_type"] = 1;
//        $Parameters ["house_code"] = '440300';
        $Parameters ["house_huxing"] = 0;
        $Parameters ["house_keywords"] = 0;
        $Parameters ["max_area"] = 0;
        $Parameters ["max_lat"] = 0;
        $Parameters ["max_lng"] = 0;
        $Parameters ["max_rentfee"] = 0;
        $Parameters ["min_area"] = 0;
        $Parameters ["min_lat"] = 0;
        $Parameters ["min_lng"] = 0;
        $Parameters ["min_rentfee"] = 0;
        $Parameters ["subway_station_name"] = 0;
        $Parameters ["timestamp"] = 1436967831;
        $Parameters ["uid"] = 0;
        $Parameters ["sign"] = '009f2792a9dcc58e20924d473f2a1001';
        $data = \QL\QueryList::run(
            'Request',[
                'target'=>$source_url,
                'referrer'=>"http://www.ziroom.com/",
                'method'=>'POST',
                'params'=>$Parameters
                //'user_agent'=>
            ]
        )->getHtml(0);
        $json = json_decode($data);
        $result=objarray_to_array($json)['data'];
        foreach ($result as $res){
            $house_info[]="http://sz.ziroom.com/z/vr/". $res['house_code'].".html";
        }
        return $house_info;
    }

    /*
     * 获取详情页
     *
     *  */
    public function house_detail($detail_url){
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
                'house_totalarea' => ['.detail_room > li:nth-child(1)', 'text', '', function($house_room_totalarea){
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