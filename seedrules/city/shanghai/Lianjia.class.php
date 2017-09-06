<?php namespace shanghai;
/**
 * @description 上海链家地产二手房抓取规则
 * @classname 上海链家
 */

Class Lianjia  extends \city\PublicClass
{
    Public function house_page(){

        $URL = 'http://sh.lianjia.com/ershoufang/';
        $html = getHtml($URL);

        // 条件
        $areaQueryResult = [];
        \QL\QueryList::Query($html,[
            'totalNum' => ['.list-head > h2 > span','text'],
            "district" => ['.option-list:eq(0) > a','gahref'],
            "price" => ['.option-list:eq(1) > a','gahref'],
            "area" => ['.option-list:eq(2) > a','gahref'],
            'room' => ['.option-list:eq(3) > a','gahref'],
            'list' => ['#house-lst > li > .info-panel > h2 > a ', 'href']
        ])->getData(function($data)use( &$areaQueryResult ){
            if($data['totalNum']){
                $areaQueryResult['totalNum'] = $data['totalNum'];
            }

            if($data['district'] && $data['district'] != 'district-nolimit'){
                $areaQueryResult['district'][] = $data['district'];
            }

            if($data['price'] && $data['price'] != 'sale-price-nolimit'){
                $areaQueryResult['price'][] = $data['price'];
            }

            if($data['area'] && $data['area'] != 'area-nolimit'){
                $areaQueryResult['area'][] = $data['area'];
            }

            if($data['room'] && $data['room'] != 'room-nolimit'){
                $areaQueryResult['room'][] = $data['room'];
            }

            if($data['list']){
                $areaQueryResult['list'][] = $data['list'];
            }

            return $data;
        });

        // 分页
        $listCount = count($areaQueryResult['list']);
        $addrUrl = [];
        foreach($areaQueryResult['district'] as $district) {
            // 商圈
            $sunUrl = $URL . $district;
            echo $sunUrl,PHP_EOL;
            $sunHtml = getHtml($sunUrl);
            for($i=0; $i<5; $i++){
                $areaQueryResult['plate'] = [];
                \QL\QueryList::Query($sunHtml, [
                    'plate' => ['.sub-option-list > a', 'gahref'],
                ])->getData(function ($data) use (&$areaQueryResult) {
                    if ($data['plate'] && $data['plate'] != 'plate-nolimit') {
                        $areaQueryResult['plate'][] = $data['plate'];
                    }
                    return $data;
                });
                if($areaQueryResult['plate']) break;
                sleep(2);
            }

            foreach ($areaQueryResult['plate'] as $plate) {
                // 面积
                foreach ($areaQueryResult['area'] as $area) {
                    // 价格
                    foreach ($areaQueryResult['price'] as $price) {
                        $addrUrl[] = $URL . $plate . '/' . $area . $price;
                    }
                }
            }
        }

        return $addrUrl;
    }
    /*
     * 获取列表页
    */
    public function house_list($url){
        for($i=0; $i<5; $i++){
            $html=getHtml($url);
            $resultQuery = [];
            \QL\QueryList::Query($html,[
                "totalNum" => ['.list-head > h2 > span','text'],
                'totalPage' => ["a[gahref='results_totalpage']", 'text'],
                'currLastPage' => ["a[gahref^='results_d']:last", 'text'],
                'list' => ['#house-lst > li > .info-panel > h2 > a ', 'href']
            ])->getData(function($data)use(&$resultQuery){
                $data['list']&& $resultQuery['list'][] = 'http://sh.lianjia.com'.$data['list'];
                isset($data['totalNum']) && $resultQuery['totalNum'] = $data['totalNum'];
                isset($data['totalPage']) && $resultQuery['totalPage'] = $data['totalPage'];
                isset($data['currLastPage']) && $resultQuery['currLastPage'] = $data['currLastPage'];
                return $data;
            });

            if($resultQuery) break;
            sleep(2);
        }

        ######################## 分页  ##############################3
        $urlBaseName = pathinfo($url, PATHINFO_BASENAME);
        if(preg_match('/d\d+/', $urlBaseName)){
            return $resultQuery['list'];
        }

        ######################## 未分页  ##############################3
        // 分页
        $page = isset($resultQuery['totalPage']) ? $resultQuery['totalPage'] : $resultQuery['currLastPage'];

        // 错误
        if( ceil($resultQuery['totalNum'] / count($resultQuery['list'])) != $page){
            $errorUrl['pageError'][] = [
                'total' => $resultQuery,
                'addr' =>  $url,
            ];
        }

        $result = [];
        for($i=1;$i<=$page;$i++){
            $pageurl =$url."d".$i."/";
            echo $pageurl,PHP_EOL;

            $html=getHtml($pageurl);
            \QL\QueryList::Query($html,[
                'list' => ['#house-lst > li > .info-panel > h2 > a ', 'href']
            ])->getData(function($data)use(&$result){
                $data['list']&& $result['list'][] = 'http://sh.lianjia.com'.$data['list'];
                return $data;
            });
        }

        return $result['list'];
    }



    /*
     * 获取详情
    */
    public function house_detail($source_url){
        $html = getHtml($source_url);
        $house_info['content'] = $html;
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
        $house_info['source'] = 1;
        $house_info['company'] = "链家官网";
        //标题
        preg_match("/<h1\s*class=\"main\"\s*([\x{0000}-\x{ffff}]+?)<\/h1>/u", $html, $title);

        $title = strip_tags($title[0]);
        $title = str_replace(array("\t", "\r", " "), "", $title);
        $title = SBC_DBC($title);
        $house_info['house_title'] =trimall(HTMLSpecialChars($title));

        preg_match("/houseInfo([\x{0000}-\x{ffff}]+?)brokerInfo/u", $html, $detail);
        $info = strip_tags($detail[0]);
        preg_match("/小区：([\x{0000}-\x{ffff}]+?)地址：/u", $info, $cb);
        preg_match("/\s*([\x{0000}-\x{ffff}]+?)\（/u", $cb[1], $bn);
        $house_info['borough_name']=trimall($bn[1]);
        preg_match_all("/\（([\x{0000}-\x{ffff}]+?)\）/u", $cb[1], $ci);
        $ccc = explode(' ', array_pop($ci[1]));
        $house_info['cityarea2_id'] =$ccc[1];
        $house_info['cityarea_id'] =$ccc[0];

        $info = str_replace(array("\t", "\r", " "), "", $info);

        $info = SBC_DBC($info);
        //价格
        preg_match("/(\d+\.?\d*)万/", $info, $price);
        $house_info['house_price']=$price[1];

        //总面积
        preg_match("/(\d+\.?\d*)平/", $info, $totalarea);
        $house_info['house_totalarea']=$totalarea[1];

        preg_match("/(\d+?)室/", $info, $room);
        preg_match("/(\d+?)厅/", $info, $hall);
        //室
        $house_info['house_room']=$room[1];
        //厅
        $house_info['house_hall']=$hall[1];

        //朝向
        preg_match("/朝向:([\x{0000}-\x{ffff}]+?)\s*首付/u", $info, $toward);
        $house_info['house_toward']=trimall($toward[1]);

        //楼层
        preg_match("/(高|中|低)层/", $info, $floor);
        preg_match("/\/(\d+?)层/", $info, $topfloor);
        $house_info['house_floor']=$floor[1];
        $house_info['house_topfloor']=$topfloor[1];

        //建造年份
        preg_match("/(\d+)年/", $info, $year);
        $house_info['house_built_year']=$year[1];

        preg_match("/evaluate\">([\x{0000}-\x{ffff}]*?)houseRecord\">/u", $html, $contact);
        $contact = trimall($contact[0]);
        preg_match("/phone\">([\x{0000}-\x{ffff}]*?)<\/div>/u",$contact, $con);
        $phone = strip_tags($con[1]);
        $house_info['owner_phone'] = str_replace("转", ",", $phone);

        preg_match("/brokerName\">([\x{0000}-\x{ffff}]*?)tag\s*first/u", $html, $jjr);
        $house_info['owner_name'] = trimall(strip_tags($jjr[1]));

        preg_match("/id=\"album-view-wrap\">([\x{0000}-\x{ffff}]*?)<\/ul>/u", $html, $p_tags);
        preg_match_all("/img-title=\"([\x{0000}-\x{ffff}]*?)\"\s*src=\"([\x{0000}-\x{ffff}]*?)\"\s*onerror/u",$p_tags[1],$pt);

        $house_info['tag'] = $this->getTags('', $html);
        $imgs = [];
        \QL\QueryList::Query($html,[
            'img' => ['div.container:nth-child(2) > div:nth-child(1) > div > img','src'],
        ])->getData(function($data)use( &$imgs ){
            $data['img'] && $imgs[] = $data['img'];
            return $data;
        });

        //匹配到的图片title待以后扩展使用
        $house_info['house_pic_unit']= implode("|", $imgs);

        preg_match_all("/<p\s*class=\"text\-comment\-all[\x{0000}-\x{ffff}]*?<\/p>/u", $html, $content);
        foreach($content[0] as $description){
            $desc[] = strip_tags($description);
        }

        //匹配video_url
        preg_match('/vid:\"([^\"]+)/', $html,$vid);
        $vedioapi = 'http://vod.open.youku.com/videoinfo/public_video_stream_v2?vid='.$vid[1];
        $vhtml = file_get_contents($vedioapi);
        // echo $vhtml;die;
        echo preg_match('/play_url":"([^"]+)/', $vhtml,$info);
        // var_dump($info);die;
        $house_info['vedio_url'] = str_replace('\\', '', $info[1]);
        $house_info['source_url'] = $source_url;

        return $house_info;
    }


    //检测该房源是否下架
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = getHtml($url);
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

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 100; $i++){
            $resultData[] = "http://sh.lianjia.com/ershoufang/d{$i}s7";
        }

        return $resultData;
    }

    /*
     * 抓取房源对应标签
     */
    public function getTags($web_url,$html=''){
        if(empty($html)){
            $html = getHtml($web_url);
        }

        $tags = [];
        \QL\QueryList::Query($html,[
            'tag' => ['.featureTag > span', 'text']
        ])->getData(function($item)use(&$tags) {
            $item['tag'] && $tags[] = $item['tag'];
            return $item;
        });
        return implode("#",$tags);
    }
}