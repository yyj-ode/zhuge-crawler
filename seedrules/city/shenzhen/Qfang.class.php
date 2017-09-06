<?php namespace shenzhen;
/**
 * @description 深圳Q房地产二手房抓取规则
 * @classname 深圳 =======《Q房》=======深圳
 */

Class Qfang extends \city\PublicClass
{
    public function house_page()
    {
        $gzurl = "http://shenzhen.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=1&dataSource=shenzhen&pageSize=20";
        $api = getHtml($gzurl);
        $apiarr = json_decode($api, true);
        $maxPage = $apiarr['result']['pageCount'];
        $urlarr = [];
        for ($page = 1; $page <= $maxPage; $page++) {
            $urlarr[] = "http://shenzhen.qfang.com/appapi/v3/room/list?bizType=SALE&currentPage=" . $page . "&dataSource=shenzhen&pageSize=20";
        }
        return $urlarr;
    }

    /*
     * 获取列表页
    */
    public function house_list($url)
    {
        $lists = [];
        $baseName = pathinfo($url, PATHINFO_BASENAME);
        // 最新的
        if (preg_match('/^f\d+$/', $baseName)) {
            $html = getHtml($url);
            \QL\QueryList::Query($html, [
                'list' => ['#cycleListings > ul > li  .pic-house > a', 'href',]
            ])->getData(function ($item) use (&$lists) {
                $item['list'] && $lists[] = 'http://shenzhen.qfang.com' . $item['list'];
            });
        } else {
            $requestList = json_decode(getHtml($url), true);
            $list = $requestList['result']['list'];

            foreach ($list as $element) {
                $id = $element['id'];
                $lists[] = "http://shenzhen.qfang.com/sale/" . $id;
            }
        }
        return $lists;

    }

    /*
     * 获取详情
    */
    public function house_detail($source_url)
    {

        $id = pathinfo($source_url, PATHINFO_BASENAME);
        $apiUrl = "http://shenzhen.qfang.com/appapi/v3/room/detail?bizType=SALE&dataSource=shenzhen&id=" . $id . "&pageSize=20&qchatPersonId=60057&which=5";

        // api接口
        $requestApi = json_decode(getHtml($apiUrl), true);

        $result_2 = $requestApi['result'];
        if (!preg_match('/Q房网·深圳/u', $result_2['broker']['company'])) {
            echo $result_2['broker']['company'] . PHP_EOL;
            return false;
        }

        // pc
        $pc_html = getHtml($source_url);
        $house_info = [];
        $house_info['house_title'] = trimall(HTMLSpecialChars($result_2['title']));
        $house_info['house_price'] = $result_2['price'] / 10000;

        //下架检测
//        $house_info['off_type'] = $this->is_off($house_info['source_url'],$pc_html);
        //dump($pc_html);die;
        $pattern = "/(\d{4})年/u";
        preg_match($pattern, $pc_html, $out);
        $house_info['house_built_year'] = $out[1];

        $house_info['house_totalarea'] = $result_2['area'];
        $house_info['house_room'] = $result_2['bedRoom'];
        $house_info['house_hall'] = $result_2['livingRoom'];
        $house_info['house_toilet'] = $result_2['bathRoom'];
        $house_info['house_fitment'] = $result_2['decoration'];
        $house_info['house_desc'] = trimall(HTMLSpecialChars($result_2['description']));
        $house_info['house_toward'] = $result_2['direction'];
        $house_info['house_floor'] = $result_2['floor'];
        $house_info['house_topfloor'] = $result_2['totalFloor'];
        $house_info['source'] = 5;
        $broker = $result_2['broker'];
        $house_info['owner_name'] = $broker['name'];
        $house_info['owner_phone'] = $broker['phone'];
        //dump($result_2);die;
        $house_info['cityarea2_id'] = $result_2['garden']['region']['name'];
        $house_info['borough_name'] = $result_2['garden']['name'];
        $json_3 = json_decode(getSnoopy("http://shenzhen.qfang.com/appapi/v3/garden/detail?dataSource=shenzhen&gardenId=" . $result_2['garden']['id']), 1);
        $house_info['cityarea_id'] = $result_2['garden']['region']['parent']['name'];
        $house_pic_unit_array = array();

        $house_info['house_pic_layout'] = str_replace('{size}', '600x450', $result_2['layoutIndexPicture']);
        $pics = $result_2['roomPictures'];
        if (!empty($pics)) {
            foreach ($pics as $index => $img) {
                $img_url = str_replace('{size}', '600x450', $img['url']);
                if ($img_url != $house_info['house_pic_layout']) {
                    $house_pic_unit_array[] = $img_url;

                }
            }
        }
        $house_pic_unit_array = array_unique($house_pic_unit_array);
        if (empty($house_pic_unit_array)) {
            preg_match('/guideMinmapCon[\x{0000}-\x{ffff}]*?<\/ul>/u', $pc_html, $piclink);
            preg_match_all('/data\-src=\"([\x{0000}-\x{ffff}]*?)\"/u', $piclink[1], $pic);
            $house_pic_unit_array = array_unique($pic[1]);
        }
        $house_info['house_pic_unit'] = implode('|', $house_pic_unit_array);
        return $house_info;
    }

    //判断该房源是否下架
    public function is_off($url, $html = '')
    {
        if (!empty($url)) {
            if (empty($html)) {
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $off_type = 1;
            $newurl = get_jump_url($url);
            $oldurl = str_replace('shtml', 'html', $url);
            if ($newurl == $oldurl) {
                $Tag = \QL\QueryList::Query($html, [
                    "isOff" => ['.remove_over', 'class', ''],
                    "404" => ['.error-404', 'class', ''],
                ])->getData(function ($item) {
                    return $item;
                });
                if (empty($Tag)) {
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
    public function callNewData()
    {
        $resultData = [];
        for ($i = 1; $i <= 100; $i++) {
            $resultData[] = "http://shenzhen.qfang.com/sale/f{$i}";
        }

        return $resultData;
    }
}