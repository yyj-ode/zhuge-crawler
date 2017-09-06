<?php namespace shenzhen;
/**
 * @description 深圳家家順二手房抓取规则
 * @classname 深圳 =======《家家順》=======深圳
 */

Class jjshome extends \city\PublicClass
{
    Public function house_page()
    {
//        $url = 'http://www.jjshome.com/esf/';
//        $urlarr = [];
//        for ($i = 1; $i <= 10; $i++) {
//            for ($j = 255; $j <= 261; $j++) {
//                $urlarr[] = $url . 'a' . $i . 'p' . $j;
//            }
//        }

        $minPage = empty($_GET['minPage']) ? 1 : $_GET['minPage'];
        $maxPage = empty($_GET['maxPage']) ? 3100 : $_GET['maxPage'];
        $urlarr = [];
        for ($page = $minPage; $page < $maxPage; $page++) {
            $urlarr[] = "http://shenzhen.jjshome.com/esf/n" . $page;
        }
        return $urlarr;
    }

    /*
     * 获取列表页
     */
    public function house_list($url)
    {
        #$html = $this->getUrlContent($url);
        $html = getHtml($url);
        $house_info = \QL\QueryList::Query($html, [
            'list' => ['.list-left > div > div.img > a', 'href'],
        ])->getData(function ($data) {
            return 'http://shenzhen.jjshome.com' . $data['list'];
        });

        return $house_info;
    }

    /*
     * 获取详情
     */
    public function house_detail($source_url)
    {
        #$html = $this->getUrlContent($source_url);
        $html = getHtml($source_url);
        $house_info = [];
        \QL\QueryList::Query($html, [
            'house_title' => ['.assessTitle', 'text'],
            'borough_name' => ['.text .', 'text'],
        ])->getData(function ($data) use (&$house_info) {
            if (isset($data['house_title'])) {
                $house_info[$data['house_title']] = $data['house_title'];
            }
            if (isset($data['borough_name'])) {
                $house_info[$data['borough_name']] = $data['borough_name'];
            }
        });

        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url);
        // 来源
        $house_info ['source'] = 12;
        // 经济公司
        $house_info ['company_name'] = '家家順';
        //标题
        preg_match("/assessTitle\">([\x{0000}-\x{ffff}]+?)<\/span>/u", $html, $title);

        $title = strip_tags($title[1]);

        //$title = explode("二手房", $title);
        $house_info ['house_title'] = trimall(HTMLSpecialChars($title));

        //<meta name="description" content="该房简介：1室1厅1卫，40m²，65万，1.6万/平，低层 / 3层，普装，2010年，点击查看更多爱盛家园小区信息与参考房价">
        preg_match("/text\sc666\">([\x{0000}-\x{ffff}]*?)<div\sclass=\"yuyue\sfl\">/u", $html, $meta);
        $details = $meta[1];
        $info = trimall(strip_tags($details));
        //小区名
        preg_match("/小区：([\x{0000}-\x{ffff}]+?)查看地图/u", $info, $title);
        $house_info ['borough_name'] = $title[1];
        preg_match("/(\d+\.?\d*)万/", $info, $price);
        $house_info ['house_price'] = $price[1];
        preg_match("/(\d+)室/", $info, $room);
        preg_match("/(\d+)厅/", $info, $hall);
        preg_match("/(\d+)卫/", $info, $toilet);
        preg_match("/(\d+\.?\d*)㎡/", $info, $total);
        preg_match("/(中|高|低)楼层/", $info, $floor);
        preg_match("/(\d+)层/", $info, $topflooor);
        preg_match("/(普通装修|精装修|豪华装修|毛坯)/", $info, $fitment);

        preg_match("/(\d{4})年/", $details, $year);
        // 室
        $house_info ['house_room'] = $room [1];
        // 厅
        $house_info ['house_hall'] = $hall [1];
        // 卫
        $house_info ['house_toilet'] = $toilet [1];
        // 面积
        $house_info ['house_totalarea'] = $total [1];
        // 建造年份
        $house_info ['house_built_year'] = $year [1];
        // 所在楼层
        $house_info ['house_floor'] = $floor [1];
        // 总楼层
        $house_info ['house_topfloor'] = $topflooor [1];
        // 装修情况
        $house_info ['house_fitment'] = $fitment [1];

        preg_match("/朝向：([\x{0000}-\x{ffff}]+?)楼层/u", $info, $toward);
        // 装修情况
        $house_info ['toward'] = trimall(strip_tags($toward[1]));


        preg_match("/小区：([\x{0000}-\x{ffff}]+?)>>/u", $info, $city);

        $city = explode('（', $city[1]);
        $city = explode('）', $city[1]);
        $city = explode('-', $city[0]);
        $house_info ['cityarea_id'] = trim(strip_tags($city [0]));
        $house_info ['cityarea2_id'] = trim(strip_tags($city [1]));

        //图片，房型图
        preg_match("/slt\_disc\">([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $pics);
        preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\"/u", $pics[1], $picss);

        $house_info ['house_pic_layout'] = $picss[1][0];
        unset ($picss[1][0]);
        $house_info ['house_pic_unit'] = implode("|", $picss[1]);

        //经纪人信息

        //房源描述，多描述用|分割
        preg_match_all("/fp-con\">([\x{0000}-\x{ffff}]*?)<p\sclass=\"tr\spb10\">/u", $html, $descs);
        $desc = array();
        foreach ($descs[1] as $k => $v) {
            $desc[] = trimall(strip_tags($v));
        }
        $house_info ['house_desc'] = implode("|", $desc);
        $house_info ['is_contrast'] = 2;
        $house_info ['is_fill'] = 2;
        $house_info ['house_relet'] = '';
        $house_info ['house_style'] = '';
        //创建时间
        $house_info ['created'] = time();
        //更新时间
        $house_info ['updated'] = time();

        if (!$house_info) {
            writeLog('jjshome_' . __FUNCTION__, $source_url, $this->_log);
        }
        return $house_info;
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData()
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data [] = "http://shenzhen.jjshome.com/esf/s10n{$i}";
        }
        writeLog('jjshome_' . __FUNCTION__, $data, $this->_log);
        return $data;
    }
}