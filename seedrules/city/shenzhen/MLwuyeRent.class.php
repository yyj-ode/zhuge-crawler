<?php namespace shenzhen;
/**
 * @description 深圳美联物业整租房抓取规则
 * @classname 深圳美联物业(k-ok)
 */

Class MLwuyeRent extends \city\PublicClass{
    
    public function house_page(){
        //从端口抓取
        //翻页规律：$Parameters ["start"]=0,1,2…..  $Parameters ["length"]=10(每一页抓取的数量可自定义)
        $maxPage = 125;
        $urlarr = [];
        for($page = 1; $page <= $maxPage; $page ++) {
            $urlarr[] = "http://www.1200.com.cn//rent/queryListData?pageSize=100&curPage=".$page."&listingType=RENT&propertyType=apartment";
        }
        return $urlarr;
    }
    /*
     * 获取列表页
     */
    public function house_list($url){
        $json = json_decode ( $this->getUrlContent( $url), 1 );
        $result=$json['items'];
        foreach ($result as $res){
            $house_info[]="http://www.1200.com.cn/oldhouse/getInfoById/". $res['id']."?type=zf";
        }
        return $house_info;
    }

    /*
     * 获取详情页
     */
    public function house_detail($source_url){
        //$source_url = 'http://www.1200.com.cn/oldhouse/getInfoById/fa46f187-6c38-4bf8-ace2-733c127710c7?type=zf';
        $html = $this->getUrlContent($source_url);
        $house_info = [];
//        $house_info["off_type"] = $this->is_off($source_url,$html);
        //标题
        preg_match("/class=\"infotitle\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $tit);
        preg_match("/<\/span>([\x{0000}-\x{ffff}]*?)<\/h3/u", $tit[1], $title);
        $house_info['house_title'] = trimall(strip_tags($title[1]));
        //房源id
        preg_match("/编号：([\x{0000}-\x{ffff}]*?)发布/u", $tit[1], $houseId);
        $house_info['house_id'] = trimall($houseId[1]);
        //详情
        preg_match("/class=\"Buildingright\">([\x{0000}-\x{ffff}]*?)<div\sclass=\"pages\">/u", $html, $detail);
        $info = trimall(strip_tags($detail[1]));
        //价格
        preg_match("/(\d+\,\d+)元\//u", $info, $price);
        $house_info['house_price'] = str_replace(",","",$price[1]);
        //面积
        preg_match("/(\d+)㎡/u", $info, $area);
        $house_info['house_totalarea'] = $area[1];
        //室
        preg_match("/(\d+)房/u", $info, $room);
        $house_info['house_room'] = $room[1];
        //厅
        preg_match("/(\d+)厅/u", $info, $hall);
        $house_info['house_hall'] = $hall[1];
        $house_info['house_toilet'] = '';
        $house_info['house_kitchen'] = '';
        //住房类型
        preg_match("/类型：([\x{0000}-\x{ffff}]*?)朝向/u", $info, $type);
        $house_info['house_type'] = $type[1];
        //楼层
        preg_match("/(低层|中层|高层)/u", $info, $floor);
        $house_info['house_floor'] = str_replace('层','',$floor[1]);
        preg_match("/(\d+)层/u", $info, $topfloor);
        $house_info['house_topfloor'] =  $topfloor[1];
        //朝向
        preg_match("/(东南|西南|东北|西北|东西|南北|东|西|南|北)/u", $info, $toward);
        $house_info['house_toward'] = $toward[1];
        //年代
        preg_match("/(\d+)年/u", $info, $year);
        $house_info['house_build_year'] = $year[1];
        //装修
        preg_match("/(毛坯|普装|精装|豪装)/u", $info, $fitment);
        $house_info['house_fitment'] = $fitment[1];

        //agent
        preg_match("/(\d{11})/u", $info, $phone);
        $house_info['owner_phone'] = $phone[1];
        preg_match("/class=\"infoName\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $agent);
        $house_info['owner_name'] = trimall(strip_tags($agent[1]));
        //小区名称
        preg_match("/楼盘名称：([\x{0000}-\x{ffff}]*?)地址/u", $info, $borough);
        $borough = explode("(",$borough[1]);
        $house_info['borough_name'] = $borough[0];
        //商圈
        preg_match("/class=\"information\">([\x{0000}-\x{ffff}]*?)<li>从业年限/u", $html, $cityinfo);
        preg_match("/隶属分行：([\x{0000}-\x{ffff}]*?)<\/li>/u", $cityinfo[1], $city);
        $city = explode("(",$city[1]);
        $city = explode("-",$city[0]);
        $house_info['cityarea2_id'] =trimall($city[1]);
        $house_info['cityarea_id'] = trimall($city[0]);
        //图片
        preg_match("/id=\"imul\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $picss);
        preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\"/u", $picss[1], $pics);
        $pics = array_unique($pics[1]);
        $house_info['house_pic_unit'] = implode('|',$pics);
        $house_info['house_pic_layout'] = '';
        //desc
        preg_match("/综合亮点[\x{0000}-\x{ffff}]*?<\/tbody>/u", $html, $desc);
        $desc = str_replace('&nbsp;','',trimall(strip_tags($desc[0])));
        $house_info['house_desc'] = $desc;

        $house_info['app_url'] = '';
        $house_info['borough_id'] = '';

        $house_info['source'] = 19;
        //source_owner 区分业主来源  1,房主儿网 2，爱直租
        $house_info['source_owner'] = '';

        //$house_info['sex'] = '';
        //$house_info['into_house'] = '';
        //$house_info['pay_method'] = $result['rentPayType'];
        //$house_info['tag'] = '';
        //$house_info['comment'] = '';
        //$house_info['house_number'] = '';
        //$house_info['deposit'] = '';
        // $house_info['is_ture'] = '';
        $house_info['created'] = time();
        $house_info['updated'] = time();
        /* $house_info['house_configroom'] = str_replace('、','#',$result_2[0]['room_config']['room']);
        $house_info['house_configroom'] = str_replace('等','',$house_info['house_configroom']);
        $house_info['house_configpub'] = str_replace('、','#',$result_2[0]['room_config']['public']);
        $house_info['house_configpub'] = str_replace('等','',$house_info['house_configpub']); 
        $house_info['house_relet'] = '';*/
        $house_info['wap_url'] = '';
        //$house_info['pub_time'] = '';
        $house_info['is_contrast'] = 2;
        $house_info['is_fill'] = 2;
        return $house_info;
    }
    //判断该房源是否下架
    public function is_off($url,$html=""){
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            $newurl = get_jump_url($url);
            if($newurl == $url){
                if(preg_match("/房源编号(\w+)/", $html)){
                    return 1;
                }else{
                    return 2;
                }
            }else{
                return 1;
            }
        }
    }

}