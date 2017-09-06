<?php namespace shanghai;
/**
 * @description 上海丁丁合租租房抓取规则
 * @classname 上海丁丁 OK
 */
//http://mbs.zufangzi.com/gms/order/orderApiController/createOrder.do
//http://userapp.iwjw.com:80/ihouse/user/myAgent.rest
class DingdingHezu extends \city\PublicClass {
    Public function house_page() {
        /*
         * http://nj.zufangzi.com/subway/0-22200
         * http://nj.zufangzi.com/subway/0-22200/2/
         * http://nj.zufangzi.com/subway/0-22200/3/
         */
        $urls = \QL\QueryList::run('Request', [
                        'target' => 'http://sh.zufangzi.com/subway/0-00000000000/',
                ])->setQuery([
                        'link' => ['.pull-right .color-yellow','text', '', function($total){
                                $maxPage = intval($total/20);
                                for($minPage = 1; $minPage <= $maxPage; $minPage++){
                                        $url[] = 'http://sh.zufangzi.com/subway/0-00000000000/'.$minPage;
                                }
                                return $url;
                        }],
                ])->getData(function($item){
                        return $item['link'];
                });
        return $urls[0];
    }
    /*
     * 列表页
     */
    public function house_list($url){
        $html = $this->getUrlContent($url);
        preg_match("/J-house-list\">([\x{0000}-\x{ffff}]*?)<div\s*class=\"col-lg-12\s*col-sm-12\">/u", $html, $houseList);
        preg_match_all("/<a\s*href=\"http:\/\/sh.zufangzi.com\/detail\/([\x{0000}-\x{ffff}]*?)\"\s*target=\"_blank\"/u", $houseList[1], $hrefs);
        $count = count($hrefs[1]);
        for($i = 0; $i < $count; $i++){
            $house_info[] = 'http://sh.zufangzi.com/detail/'.$hrefs[1][$i];
        }
        return $house_info;
    }

    /*
     *获取详情页数据
     */
    public function house_detail($source_url) {
        //来源
        $html = $this->getUrlContent($source_url);

        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);

        preg_match("/<li\s*class=\"last-child\">([\x{0000}-\x{ffff}]*?)<div\s*class=\"baseinfo\">/u", $html, $info);
        //普通住宅水岸双桥4800元精装修90.0㎡南北2室1厅1厨1卫出租-距 双桥站 步行3分钟-丁丁租房网

        //装修
        $house_info['source']= 13;
        preg_match("/(精装修|简装|毛坯)/", $info[1], $fitment);
        $house_info['house_fitment']= $fitment[1];

        //出租间面积
        preg_match("/(\d+\.?\d*)平/", $info[1], $area);
        $house_info['house_room_totalarea']= $area[1];

        //朝向
        preg_match("/(东南|东北|西南|西北|南北|东|西|南|北)/", $info[1], $toward);
        $house_info['house_toward']= $toward[1];

        //室厅卫
        preg_match("/(\d+?)室(\d+?)厅(\d+?)卫/u", $info[1], $rht);
        //卧室/居
        $house_info['house_room']= $rht[1];
        //厅
        $house_info['house_hall']= $rht[2];
        //卫生间
        $house_info['house_toilet'] = $rht[3];
        //厨房
        $house_info['house_kitchen']= $rht[4];

        //价格
        preg_match("/(\d+\.?\d*)元/u", $info[1], $price);
        $house_info['house_price']= $price[1];

        //详细信息
        preg_match("/房源信息([\x{0000}-\x{ffff}]*?)配置设施/u", $html, $detail);

        //类型  普通住宅2：别墅  3:写字楼 4:公寓
        preg_match("/(普通住宅|别墅|写字楼|公寓)/", $info[1], $house_type);
        $house_info['house_type'] = trimall($house_type[1]);

        //所在楼层
        preg_match("/(高|中|低)层/", $detail[1], $f);
        $house_info['house_floor']= $f[1];

        //总楼层
        preg_match("/共(\d+?)层/", $detail[1], $ft);
        $house_info['house_topfloor'] = $ft[1];

        //小区名字
        preg_match("/小区：([\x{0000}-\x{ffff}]*?)现状：/u", $detail[1], $borough);
        $borough = trimall(strip_tags($borough[1]));
        $house_info['borough_name'] = $borough;

        //标题
        $house_info['house_title'] = trimall(strip_tags($info[1]));

        //通过API抓取城区商圈
        //抓取经纬度
        $latlng = trimall($html);
        preg_match('/id=\"lngHidder\"value=\"(\d+\.?\d*)\"/u',$latlng,$lng);//经度

        preg_match('/id=\"latHidder\"value=\"(\d+\.?\d*)\"/u',$latlng,$lat);//纬度

        $Map = $this->getUrlContent("http://api.map.baidu.com/geocoder/v2/?location=".$lat[1].",".$lng[1]."&output=json&ak=aqLgbABLabxT9csGOEhrjDFM");
        $map = json_decode($Map,1);
        $cityarea_id = str_replace("区","",$map['result']['addressComponent']['district']);
        $cityarea2_id = explode(",",$map['result']['business'])[0];
        $house_info['cityarea_id'] = $cityarea_id;
        $house_info['cityarea2_id'] = $cityarea2_id;
        //房源编号
        preg_match("/编号：([\x{0000}-\x{ffff}]*?)距/u", $detail[1], $number);
        $house_info['house_number'] = trimall(strip_tags($number[1]));

        //图片
        preg_match("/class=\"attention-tip\">([\x{0000}-\x{ffff}]*?)<div\s*class=\"next\s*J-next\">/u", $html,$pics);
        preg_match_all("/src=\"(.*?)\"/",$pics[1],$src);
        $house_info['house_pic_unit'] = implode('|',$src[1]);

        //入住人限制
        $house_info['sex']= "";
        //入住时间
        $house_info['into_house']= "";
        //付款方式 例如信用卡
        $house_info['pay_method']= "";
        //付款类型 例如 押一付三
        $house_info['pay_type']= "";
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
        //卧室类型 主卧 还是次卧
        $house_info['house_style']= "";
        //是否转租 1.转租  2.非转租
        $house_info['house_relet']="";

        //创建时间
        $house_info['created']= time();
        //更新时间
        $house_info['updated']= time();
        $house_info['house_configpub'] ="";
        $house_info['is_contrast'] = 2;
        $house_info['is_fill'] = 2;
        $house_info['source_url'] = $source_url;
        return $house_info;
//        dump($house_info);die;
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
                    "isOff" => ['div.btn','text','',function($is_off){
                        return preg_match("/已下架/",$is_off);   //下架返回1，未下架返回0
                    }],
                    "404" => ['.error-title','class',''],
                ])->getData(function($item){
                    return $item;
                });
                if($Tag[0]["isOff"]== 0 && $Tag[0]["404"]==NULL){
                    $off_type = 2;
                    return $off_type;
                }
            }
            return $off_type;
        }
        return -1;
    }
}