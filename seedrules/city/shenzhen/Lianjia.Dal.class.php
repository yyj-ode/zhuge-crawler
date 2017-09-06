<?php namespace shenzhen;
/**
 * Created by PhpStorm.
 * User: zhangjg
 * Date: 16/7/04
 * Time: 下午2:27
 * @description 深圳链家地产二手房抓取规则
 * @classname 深圳 =======《链家api抓取》=======深圳
 */


class Lianjia extends \city\PublicClass
{
    public $PRE_URL = 'http://m.api.lianjia.com/house/ershoufang/searchv2?channel=ershoufang&city_id=440300&limit_count=20&limit_offset=10&access_token=&utm_source=&device_id=1f3c9e90-86aa-4d32-9eb0-1a8a3df2b7a6';

    /**
     * 获取列表分页
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        //      return $this->callNewData(); //getSnoopy
        $result = json_decode(getSnoopy($this->PRE_URL), true);
        $total = $result['data']['total_count'];
        $limit_count = 100;
        $maxPage = floor($total / $limit_count);
        $link = [];
        for ($Page = 1; $Page <= $maxPage + 1; $Page++) {
            $temp = ($Page - 1) * $limit_count;
            $urls[] = "http://m.api.lianjia.com/house/ershoufang/searchv2?channel=ershoufang&city_id=440300&limit_count=" . $limit_count . "&limit_offset=" . $temp . "&access_token=&utm_source=&device_id=1f3c9e90-86aa-4d32-9eb0-1a8a3df2b7a6";
        }
        return $urls;
    }

    /*
	 * 获取列表页
	 */

    public function house_list($url = '')
    {
        if (!isExistsStr($url, "m.api.lianjia.com")) {
            $html = file_get_contents($url);
            $house_info = array();
            $house_info = \QL\QueryList::Query($html, [
                //获取单个房源url
                'link' => ['.listContent > li > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)', 'href', '', function ($u) {
                    return $u;
                }],
            ])->getData(function ($item) {
                return $item['link'];
            });
        } else {
            $url_lj = "http://sz.lianjia.com/ershoufang/";
            $json_2 = json_decode(getSnoopy($url), 1);
            foreach ($json_2['data']['list'] as $arr) {
                $house_info[] = $url_lj . $arr['house_code'] . ".html";
            }
        }
        return $house_info;
    }

    /*
	 * 获取详情
	*/
    public function house_detail($source_url)
    {
        $house_info = array();
        $json = array();
        $tmp = explode('/', $source_url);
        $tmp1 = explode('.', $tmp[4]);
        $house_code = $tmp1[0];

        $wap_api = "http://m.api.lianjia.com/house/ershoufang/detail?house_code=" . $house_code . "&share_agent_ucid=&access_token=&utm_source=&device_id=1f3c9e90-86aa-4d32-9eb0-1a8a3df2b7a6&city_id=440300";
        $wap_api_chengjiao = "http://m.api.lianjia.com/house/chengjiao/detail?house_code=" . $house_code . "&share_agent_ucid=&access_token=&utm_source=&device_id=1f3c9e90-86aa-4d32-9eb0-1a8a3df2b7a6&city_id=440300";
        $json = json_decode(getSnoopy($wap_api), 1);

        //判断接口是否有数据,如果没有调用下架接口查看数据.
        if ($json['errno'] == 20004) {
            $json = json_decode(getSnoopy($wap_api_chengjiao), 1);
            if ($json['errno'] == 0) {
                $house_info['off_type'] = 1;
                $house_info['off_reason'] = 1;
            } else {
                return 1;
            }
        }

        $json2 = $json['data'];
        $house_info['source'] = 1;
        $house_info['company'] = "链家官网";
        //标题
        $house_info['house_title'] = $json2['title'];
        $house_info['borough_name'] = $json2['community_name'];
        $house_info['cityarea2_id'] = $json2['bizcircle_name'];
        $house_info['cityarea_id'] = $json2['district_name'];
        $house_info['house_price'] = $json2['price'] / 10000;
        //总面积
        $house_info['house_totalarea'] = $json2['area'];
        //室
        $house_info['house_room'] = $json2['blueprint_bedroom_num'];
        //厅
        $house_info['house_hall'] = $json2['blueprint_hall_num'];
        //朝向
        $house_info['house_toward'] = $json2['orientation'];
        preg_match("/(高|中|低)楼层/", $json2['floor_state'], $floor);
        preg_match("/\/(\d+?)层/", $json2['floor_state'], $topfloor);
        //楼层
        $house_info['house_floor'] = $floor[1];
        $house_info['house_topfloor'] = $topfloor[1];
        //建造年份
        $house_info['house_built_year'] = $json2['building_finish_year'];
        $house_info['owner_phone'] = $json2['agent']['mobile_phone'];
        $house_info['owner_name'] = $json2['agent']['name'];
        $house_info['house_number'] = $json2['house_code'];
        $house_info['house_fitment'] = $json2['decoration'];
        foreach ($json2['picture_list'] as $pic) {
            if ($pic['type'] == 'blueprint') {
                $house_pic_layout[] = $pic['url'];
            } else {
                $house_pic_unit[] = $pic['url'];
            }
        }
        $house_info['source_url'] = $json2['m_url'];
        //匹配到的图片title待以后扩展使用
        $house_info['house_pic_unit'] = implode("|", $house_pic_unit);
        $house_info['house_pic_layout'] = implode("|", $house_pic_layout);
        $house_info['house_desc'] = trimall($json2['agent_house_comment'][0]['content']);
        $array_par1 = array('is_sales_tax', 'is_sole', 'is_subway_house', 'is_key', 'is_school_house');
        $array_par = array('is_sales_tax' => "满五年唯一", 'is_sole' => "独家", 'is_subway_house' => '地铁房', 'is_key' => "随时看房", 'is_school_house' => "学区房");
        $tags_temp = "";
        foreach ($json2['tags'] as $val) {
            if (in_array($val, $array_par1)) {
                $tags_temp .= $array_par[$val] . "#";
            }

        }
        $house_info['tag'] = $tags_temp;
        return $house_info;
    }

    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData($num = 100)
    {
        $url = 'http://sz.lianjia.com/ershoufang/pg{$page}co32/';
        $data = [];
        for ($i = 1; $i <= $num; $i++) {
            $data[] = str_replace('{$page}', $i, $url);
        }
        return $data;
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
}