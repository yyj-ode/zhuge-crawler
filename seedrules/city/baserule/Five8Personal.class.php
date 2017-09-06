<?php namespace baserule;
/**
 * Created by PhpStorm.
 * User: zhangjg
 * Date: 2016/8/9
 * Time: 14:23
 */


class Five8Personal extends \city\PublicClass
{
    protected $log = false; // 是否开启日志
    public $city_name = '';
    public $dis = [];

    /**
     * 获取种子分页
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_page()
    {
        
        for ($i = 0; $i <= 100; $i++) {
            $house_info[] = 'http://' . $this->city_name . '.58.com/ershoufang/0/pn' . $i . '/?PGTID=0d30000c-0000-110b-940a-229a430108e3&ClickID=1';
        }
        if (!$house_info)
            writeLog('Five8Personal' . __FUNCTION__, ['url' => $house_info], $this->log);
        return $house_info;
    }

    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    Public function house_list($url = '')
    {
        $html = getSnoopy($url);
        #$html = $this->getUrlContent($url);
        $lists = [];
        \QL\QueryList::Query($html, [
            "list" => ['#infolist td.t > p.bthead > a', 'infoid'],
        ])->getData(function ($item) use (&$lists) {
            if ($item['list']) {
                $url = "http://" . $this->city_name . ".58.com/ershoufang/{$item['list']}x.shtml";
                $lists[] = $url;
            }
        });
        if (!$lists)
            writeLog('Five8Personal' . __FUNCTION__, ['url' => $url], $this->log);
        return $lists;
    }

    /**
     * 获取详情页信息
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_detail($source_url)
    {
        $pathinfo = pathinfo($source_url);

        $source_url_m = "http://m.58.com/" . $this->city_name . "/ershoufang/" . $pathinfo['filename'] . ".shtml";

        #$html = $this->getUrlContent($source_url_m);
        $html = getSnoopy($source_url_m);
        $house_info = [];
        \QL\QueryList::Query($html, [

            'house_title' => ['#titlename', 'text', '', function ($data) use (&$house_info) {
                $house_info['house_title'] = $data;
            }],
            'owner_name' => ['#contactbar .llname', 'text', '', function ($data) use (&$house_info) {
                $house_info['owner_name'] = $data;
            }],
            'owner_phone' => ['#contactbar .llnumber', 'text', '', function ($data) use (&$house_info) {
                $house_info['owner_phone'] = $data;
            }],
            'borough_name' => ['#xiaoquName', 'text', '', function ($data) use (&$house_info) {
                $house_info['borough_name'] = $data;
            }],
            //价格
            'house_price' => ['.infor-price li:contains("售价")', 'text', '', function ($data) use (&$house_info) {
                preg_match("/(\d+\.?\d*)万/", $data, $house_price);
                $house_info['house_price'] = $house_price[1];
            }],
            //面积
            'house_totalarea' => ['.infor-price li:contains("面积")', 'text', '', function ($data) use (&$house_info) {
                preg_match("/(\d+\.?\d*)㎡/", $data, $house_totalarea);
                $house_info['house_totalarea'] = $house_totalarea[1];
            }],
        ])->getData();
        $house_info['content'] = $html;
        $house_info['source'] = 10;
        $house_info['source_owner'] = 5;
        $house_info['is_contrast'] = 9;//未经58检测
        $house_info['company'] = "58同城个人房源";
        preg_match("/id\=\"titlename\">([\x{0000}-\x{ffff}]+?)<\/h1>/u", $html, $title);
        //标题
        preg_match("/area-infor\swhitebg\smt15([\x{0000}-\x{ffff}]+?)comm-perip\swhitebg\smt15/u", $html, $detail);
        $info = strip_tags($detail[0]);
        $info = str_replace(array("\t", "\n", "\r", " "), "", $info);
        $info = SBC_DBC($info);
        //总面积
        preg_match("/(\d+\.?\d*)㎡/", $info, $totalarea);
        $house_info['house_totalarea'] = $totalarea[1];
        //小区

        if (!$house_info["borough_name"]) {

            $html = getSnoopy($source_url);

            \QL\QueryList::Query($html, [
                'borough_name' => ['.xiaoqu .xiaoqu_txt p:contains("小区名称") > a', 'text', '', function ($data) use (&$house_info) {
                    $house_info['borough_name'] = $data;
                }],
            ])->getData();
        }

        preg_match("/户型([\x{0000}-\x{ffff}]+?)室/u", $info, $room);
        $rooms = str_replace(array("\t", "\n", "\r", " "), "", $room[1]);
        if (is_numeric($rooms)) {
            //室
            $house_info['house_room'] = $rooms;
        } else {
            switch (trimall($rooms)) {
                case "一":
                    $rooms = 1;
                    break;
                case "二":
                    $rooms = 2;
                    break;
                case "三":
                    $rooms = 3;
                    break;
                case "四":
                    $rooms = 4;
                    break;
                case "五":
                    $rooms = 5;
                    break;
                case "六":
                    $rooms = 6;
                    break;
                case "七":
                    $rooms = 7;
                    break;
                case "八":
                    $rooms = 8;
                    break;
                case "九":
                    $rooms = 9;
                    break;
                case "十":
                    $rooms = 10;
                    break;
                default:
                    $rooms = null;
            }
            $house_info['house_room'] = $rooms;
        }

        preg_match("/(\d+?)厅/", $info, $hall);
        //厅
        $house_info['house_hall'] = $hall[1];
        //卫
        preg_match("/厅(\d+?)卫/", $info, $toilet);
        $house_info['house_toilet'] = trimall($toilet[1]);
        //装修
        preg_match("/装修：([\x{0000}-\x{ffff}]+?)<\/li>/u", $html, $fitment);
        $fitments = strip_tags($fitment[1]);
        $fitments = str_replace(array("\t", "\n", "\r", " "), "", $fitments);
        $fitments = SBC_DBC($fitments);
        preg_match("/朝向:([\x{0000}-\x{ffff}]*?)楼层:/u", $info, $toward);
        $house_info['house_toward'] = str_replace("暂无信息", "", $toward[1]);;

        //楼层
        \QL\QueryList::Query($html, [
            'floor' => ['ul.infor-other > li:eq(2) > i', 'text']
        ])->getData(function ($item) use (&$house_info) {
            if ($item['floor']) {
                preg_match('/(.+)\(.*(\d+).*\)/', $item['floor'], $match);
                $house_info['house_floor'] = $match[1];
                $house_info['house_topfloor'] = $match[2];
            }
        });

        //城区，商圈
        preg_match("/位置：([\x{0000}-\x{ffff}]+?)<\/li>/u", $detail[0], $area);
        $area_arr = explode('-', strip_tags($area[1]));
        // var_dump($area);die;
        $house_info['cityarea2_id'] = trimall($area_arr[1]);
        $house_info['cityarea_id'] = trimall($area_arr[0]);
        //联系人


        if ($house_info['owner_phone'] == $house_info['owner_name']) {
            $house_info['owner_name'] = '';
        }
        //房源描述
        preg_match("/id=\"descript([\x{0000}-\x{ffff}]+?)div>/u", $detail[0], $desc);
        $desc = strip_tags($desc[1]);
        $desc = str_replace(array("\t", "\r", " ", "万"), "", $desc);
        $desc = SBC_DBC($desc);
        $house_info['house_desc'] = trimall($desc);
        //图片
        preg_match("/<ul\sclass\=\"imglist\">([\x{0000}-\x{ffff}]+?)<\/ul>/u", $html, $pics);
        preg_match_all("/data\-url\=\"([\x{0000}-\x{ffff}]+?)\">/u", $pics[1], $pic);
        $picture = array_merge($pic[1]);
        $picture = array_unique($picture);
        $house_info['house_pic_unit'] = implode("|", $picture);
        if (empty($house_info['house_pic_unit'])) {
            $house_info['house_pic_unit'] = '';
        }
        $house_info['tag'] = $this->getTags($html);
        if (!$house_info)
            writeLog('Five8Personal' . __FUNCTION__, ['url' => $source_url], $this->log);
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
        for ($i = 0; $i <= 30; $i++) {
            $house_info[] = 'http://' . $this->city_name . '.58.com/ershoufang/0/pn' . $i . '/?PGTID=0d30000c-0000-110b-940a-229a430108e3&ClickID=1';
        }
        if (!$house_info)
            writeLog('Five8Personal' . __FUNCTION__, ['url' => $house_info], $this->log);
        return $house_info;

    }

    /*
    * 抓取房源对应标签
    */
    public function getTags($html)
    {
        $tags = [];
        \QL\QueryList::Query($html, [
            'tag' => ['.infor-keyword > li', 'text']
        ])->getData(function ($item) use (&$tags) {
            $item['tag'] && $tags[] = $item['tag'];
            return $item;
        });
        return implode("#", $tags);
    }
}