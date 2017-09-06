<?php namespace guangzhou;
/**
 * @description 广州链家二手房抓取规则
 * @classname 广州链家
 */

Class Lianjiaold  extends \city\PublicClass{
    public function house_page(){
        $PRE_URL = 'http://gz.lianjia.com/ershoufang/';
        //获取搜索条件
        $url_list = $this->get_condition($PRE_URL);
        $num = count($url_list);
        $urlarr = [];
        for($n=0; $n<1; $n++) {
            //从当前条件首页抓取最大页
            $maxPage = $this->get_maxPage($url_list[$n]);

            for ($page = 1; $page <= $maxPage; $page++) {
                $urlarr[] = $url_list[$n] . "pg" . $page . "/";
            }
        }
        return $urlarr;
    }
    /*
     * 获取列表页
    */
    public function house_list($url){
        $html=$this->getUrlContent($url);
        $house_info =[];
        preg_match("/<ul\s*id=\"house-lst[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $out);
        preg_match_all("/data\-id=\"(\w*?)\"/", $out[0], $ids);
        //preg_match_all("/title=\"[\x{0000}-\x{ffff}]*?\"/u", $out[0], $titles);
        foreach ($ids[1] as $k=>$v){
            $house_info[] = "http://gz.lianjia.com/ershoufang/".$v.".html";
            //$this->house_info[$k]['house_title'] = str_replace(array('title=', '"'), '', $titles[0][$k]);
        }
        return $house_info;
    }
    /*
     * 获取详情
    */
    public function house_detail($source_url){
        $html = $this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
        $house_info['source'] = 1;
        $house_info['company'] = "链家官网";
        //标题
        preg_match("/<title>([\x{0000}-\x{ffff}]+?)<\/title>/u", $html, $title);
        /*$title = strip_tags($title[0]);
        $title = str_replace(array("\t", "\r", " "), "", $title);
        $title = SBC_DBC($title);*/
        $house_title = explode('_',trimall(HTMLSpecialChars($title[1])));
        $house_info['house_title'] = $house_title[0];

        preg_match("/class=\"price([\x{0000}-\x{ffff}]+?)houseRecord/u", $html, $detail);
        $info = strip_tags($detail[0]);
        $info = str_replace(array("\t", "\r", " "), "", $info);
        $info = trimall(SBC_DBC($info));
        //价格
        preg_match("/(\d+\.?\d*)万/", $info, $price);
        $house_info['house_price']=$price[1];

        //总面积
        preg_match("/(\d+\.?\d*)平米/", $info, $totalarea);
        $house_info['house_totalarea']=$totalarea[1];

        preg_match("/(\d+?)室/", $info, $room);
        preg_match("/(\d+?)厅/", $info, $hall);
        //室
        $house_info['house_room']=$room[1];
        //厅
        $house_info['house_hall']=$hall[1];

        //朝向
        preg_match("/(东西|南北|东南|东北|西南|西北)/u", $info, $toward);
        if(empty($toward[1])){
            preg_match("/(东|西|南|北)/u", $info, $toward);
        }
        $house_info['house_toward']=$toward[1];

        preg_match("/(毛坯|简装|精装|豪装)/u", $info, $fitment);
        $house_info['house_fitment']=$fitment[1];
        //楼层
        preg_match("/(高|中|低)楼层/", $info, $floor);
        preg_match("/共(\d+?)层/", $info, $topfloor);
        $house_info['house_floor']=$floor[1];
        $house_info['house_topfloor']=$topfloor[1];

        //建造年份
        preg_match("/(\d+)年/", $info, $year);
        $house_info['house_built_year']=$year[1];

        preg_match("/小区名称:([\x{0000}-\x{ffff}]+?)查看地图/u", $info, $borough);
        $house_info['borough_name']=$borough[1];

        preg_match("/区域:([\x{0000}-\x{ffff}]+?)&nbsp;([\x{0000}-\x{ffff}]+?)&nbsp;/u", $info, $cityarea);
        $house_info['cityarea_id'] =$cityarea[1];
        $house_info['cityarea2_id'] =$cityarea[2];

        preg_match("/brokerName\">([\x{0000}-\x{ffff}]*?)<\/a>/u", $html, $name);
        $name = trimall(strip_tags($name[1]));
//        var_dump($name);exit;
        if(preg_match('/电话/u',$name)){
            $name = '';
        }
        $house_info['owner_name'] = $name;

        preg_match("/class=\"phone\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $phone);
        $phone = str_replace("转",",",trimall(strip_tags($phone[1])));

        $house_info['owner_phone'] = $phone;

        preg_match("/class=\"slide\">[\x{0000}-\x{ffff}]*?<\/ul>/u", $html, $p_tags);
        preg_match_all("/src=\"(\S*?)\"/", $p_tags[0], $ps);
        $pics = array_merge($ps[1]);
        $pics = array_unique($pics);
        foreach($pics as $k=>$v){
            if(preg_match('/Frame/',$v)){
                $house_info['house_pic_layout'] = $v;
                unset($pics[$k]);
            }
        }
        $house_info['house_pic_unit']= implode("|", $pics);

        preg_match("/featureContent\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $content);
        $desc = trimall(strip_tags($content[1]));
        if(preg_match('/暂无/u',$desc)){
            $desc = '';
        }
        $house_info['house_desc']= $desc;
        //dump($this->house_info);die;
        return $house_info;
    }
    /*
     * 获取各类搜索条件
     */

    Public function get_condition($PRE_URL){
        $html = $this->getUrlContent($PRE_URL);
        preg_match('/区域：([\x{0000}-\x{ffff}]+?)筛选/u',$html,$message);
        preg_match_all('/option\-list([\x{0000}-\x{ffff}]+?)<\/dl>/u',$message[1],$condition);
        $condition = $condition[1];
        //城区搜索条件
        preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[0],$dis);
        preg_match_all('/<a\shref=\"\/ershoufang\/([\x{0000}-\x{ffff}]+?)\//u',$dis[1],$dis);
        //面积搜索条件
        preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[2],$area);
        preg_match_all('/<a\shref=\"\/ershoufang\/a(\d+)\//u',$area[1],$area);
        //房型搜索条件
        preg_match('/不限([\x{0000}-\x{ffff}]+?)<\/div>/u',$condition[3],$room);
        preg_match_all('/<a\shref=\"\/ershoufang\/l(\d+)\//u',$room[1],$room);
        $url_list = array();
        foreach($dis[1] as $DIS){
            foreach($area[1] as $AREA){
                foreach($room[1] as $ROOM){
                    $url_list[] = $PRE_URL.$DIS."/l".$ROOM."a".$AREA."/";
                }
            }
        }
        return $url_list;
    }

    /*
     * 获取搜索条件下的最大页
     */
    Public function get_maxPage($url){
        $html = $this->getUrlContent($url);
        preg_match('/page\-data=\'([\x{0000}-\x{ffff}]+?)\'/u',$html,$page);
        $result = json_decode($page[1],1);
        $maxPage = $result['totalPage'];
        //如果最大页抓空，返回1
        if(!empty($maxPage)){
            return $maxPage;
        }else{
            return 0;
        }
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
            if($newurl == $oldurl){   //在链家跳转是生效的
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