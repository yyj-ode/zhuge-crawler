<?php namespace baserule;
/**
 * Created by PhpStorm.
 * User: zhangjg
 * Date: 2016/8/8
 * Time: 19:35
 */


class Lianjia extends \city\PublicClass
{
    protected $log = false; // 是否开启日志
    public $city_name = '';
    private $city_id = array("bj" => '110000', "gz" => '440100', "sz" => '440300', "nj" => '320100',"tj" => '120000');

    public function __construct($path = '')
    {
        parent::__construct($path);
        $this->city_id = $this->city_id[$this->city_name];
    }


    /**
     * 获取种子分页
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_page()
    {
        $PRE_URL = 'http://m.api.lianjia.com/house/ershoufang/searchv2?channel=ershoufang&city_id=' . $this->city_id . '&limit_count=1&limit_offset=10&access_token=&utm_source=&device_id=58423a712e-4f27-42a1-9a14-c97337719271';
        #$result = json_decode(getSnoopy($PRE_URL), true);
        $result = json_decode(getSnoopy($PRE_URL), true);
        $total = $result['data']['total_count'];
        $limit_count = 100;
        $maxPage = floor($total / $limit_count);
        for ($Page = 1; $Page <= $maxPage + 1; $Page++) {
            $temp = ($Page - 1) * $limit_count;
            $urls[] = "http://m.api.lianjia.com/house/ershoufang/searchv2?channel=ershoufang&city_id=" . $this->city_id . "&limit_count=" . $limit_count . "&limit_offset=" . $temp . "&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271";
        }
        if (!$urls)
            writeLog('Lianjia' . __FUNCTION__, ['url' => $urls], $this->log);
        return $urls;
    }


    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    Public function house_list($url = '')
    {
        if (!isExistsStr($url, "m.api.lianjia.com")) {
            $html = getHtml($url);
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
            $url_lj = "http://" . $this->city_name . ".lianjia.com/ershoufang/";
            #$json_2 = json_decode(getSnoopy($url), 1);
            $json_2 = json_decode(getHtml($url), 1);
            foreach ($json_2['data']['list'] as $arr) {
                $house_info[] = $url_lj . $arr['house_code'] . ".html";
            }
        }
        #提供处理隐藏房源
//        if (!empty($house_info)) {
//            $redis = new \redis();
//            $redis->connect("192.168.1.17", "6379");
//            $redis->select(7);
//            foreach ($house_info as $key => $value) {
//                $redis->hset($this->city_name . "-Lianjia-urls", md5($value), $value);
//            }
//        }
//        if (!$house_info)
//            writeLog('Lianjia' . __FUNCTION__, ['url' => $url], $this->log);
        return $house_info;
    }

    /**
     * 获取详情页信息
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_detail($source_url)
    {
        $house_info = array();
        $tmp = explode('/', $source_url);
        $tmp1 = explode('.', $tmp[4]);
        $house_code = $tmp1[0];

        $wap_api = "http://m.api.lianjia.com/house/ershoufang/detail?house_code=" . $house_code . "&share_agent_ucid=&access_token=&utm_source=&device_id=1f3c9e90-86aa-4d32-9eb0-1a8a3df2b7a6&city_id=" . $this->city_id;
        $json = json_decode(getHtml($wap_api), 1);

        $json2 = $json['data'];
        $house_info['content'] = $json;
        $house_info['source'] = 1;
        $house_info['company'] = "链家官网";
        $house_info['source_owner'] = 0;
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
        if(empty($house_info['house_desc'])){
            $house_info['house_desc'] = trimall($json2['yezhu_comment']['content']);
        }
        $array_par1 = array('is_sales_tax', 'is_sole', 'is_subway_house', 'is_key', 'is_school_house');
        $array_par = array('is_sales_tax' => "满五年唯一", 'is_sole' => "独家", 'is_subway_house' => '地铁房', 'is_key' => "随时看房", 'is_school_house' => "学区房");
        $tags_temp = "";
        foreach ($json2['tags'] as $val) {
            if (in_array($val, $array_par1)) {
                $tags_temp .= $array_par[$val] . "#";
            }

        }
        $house_info['tag'] = $tags_temp;

        if (!$house_info)
            writeLog('Lianjia' . __FUNCTION__, ['url' => $source_url], $this->log);
        return $house_info;

    }

    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function callNewData()
    {
        $url = 'http://' . $this->city_name . '.lianjia.com/ershoufang/pg{$page}co32/';
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data[] = str_replace('{$page}', $i, $url);
        }
        if (!$data)
            writeLog('Lianjia' . __FUNCTION__, ['url' => $url], $this->log);
        return $data;
    }

}