
<?php

/**
 * 检测房源下架
 */
class houseOffClass
{
    private $off_type = 2;
    private $off_reason = 1;
    private $city = '';
    private $url = '';
    private $content = '';

    /**
     * 检查房源是否下架
     * @param type $source 渠道
     * @param type $url 链接
     * @return type 类型
     */
    public function checkHouseOff($source = '', $url = '', $content = '')
    {
        if (!empty($source) && !empty($url)) {
            $this->url = $url;
            $this->content = $content;
            $func = explode('/', $source);
            $this->city = $func[0];
            $func = ucfirst(strtolower($func[1]));
            if (!empty($func)) {
                $this->$func();
            }
        }
        return [$this->off_type, $this->off_reason];
    }

    public function __call($name, $arguments)
    {

    }

    private function querlistRun($rules = '')
    {
        if (!empty($rules) && is_array($rules)) {
            $Tag = \QL\QueryList::run('Request', [
                'target' => $this->url,
            ])->setQuery($rules)->getData(function ($item) {
                return $item;
            });
            if (!empty($Tag)) {
                return $Tag;
            }
        }
        return false;
    }


    /**
     * 设置下架
     */
    private function settingHouseOff()
    {
        $this->off_type = 1;
    }

    /**
     * 链家二手房
     */
    private function LianjiaBefore()
    {
        $Tag = $this->querlistRun([
            'house-shelves' => ['.house-shelves', 'class', ''],
            'pic-cj' => ['.pic-cj', 'class', ''],
        ]);
        if ($Tag) {
            $this->settingHouseOff();
            if (!empty($Tag[0]['house-shelves'])) {
                $this->off_reason = 1;  //下架
            } else {
                $this->off_reason = 2;  //成交
            }
        }
    }

    /**
     * 链家二手房wap
     */
    private function Lianjia()
    {
        $source_url = $this->url;
        $tmp = explode('/', $source_url);
        $tmp1 = explode('.', $tmp[4]);
        $house_code = $tmp1[0];
        $wap_api = "http://m.api.lianjia.com/house/chengjiao/detail?house_code=" . $house_code . "&share_agent_ucid=&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271&city_id=110000";
        $off_json = $this->content;
        if ($off_json['errno'] == 20004) {
            $json = json_decode(getSnoopy($wap_api), 1);
            if ($json['errno'] == 0) {
                $this->settingHouseOff();
                $this->off_reason = 2;//成交
            } else {
                $this->settingHouseOff();
                $this->off_reason = 1;//下架
            }
        }
    }

    /**
     * 我爱我家二手房
     */
    private function Wiwj()
    {
        $Tag = $this->querlistRun([
            "isOff" => ['.house_updown', 'class', ''],
//                "404" => ['.main_top','class',''],
        ]);
        if ($Tag) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /**
     * 58二手房
     */
    private function Five8Personal()
    {
        $html = $this->content;
        //抓取下架标识
        if (preg_match("/ico_error/", $html)) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }
    /**
     * 58整租
     */
    private function Five8PersonalRent()
    {
    	$html = $this->content;
    	//抓取下架标识
    	if (preg_match("/ico_error/", $html)) {
    		$this->settingHouseOff();
    		$this->off_reason = 1;  //下架
    	}
    }
    /**
     * 嗨租整租
     */
    private function HaizhuRent()
    {
    	$html = $this->content;
    	//抓取下架标识
    	if (preg_match("/愿，住的好一点/", $html)) {
    		$this->settingHouseOff();
    		$this->off_reason = 1;  //下架
    	}
    }
    /**
     * 房主整租
     */
    private function FangzhuRent()
    {
    	$html = $this->content;
    	//抓取下架标识
    	if (preg_match("/IOS/", $html)) {
    		$this->settingHouseOff();
    		$this->off_reason = 1;  //下架
    	}
    }    
    /**
     * 酷房整租
     */
    private function Kufang()
    {
        $Tag = \QL\QueryList::Query($this->url, [
            "isOff" => ['.big_qvkuai_top', 'text', '', function ($item) {
                return preg_match("/您访问的房源已售出/", $item);
            }],
        ])->getData(function ($item) {
            return $item;
        });
        if ($Tag[0]['isOff']) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }

        if ($this->off_type != 1) {

            $Tag = \QL\QueryList::Query($this->url, [
                "isOff" => ['.contenttop_err', 'text', '', function ($item) {
                    return true;
                }],
            ])->getData(function ($item) {
                return $item;
            });
            if ($Tag[0]['isOff']) {
                $this->settingHouseOff();
                $this->off_reason = 1;  //下架
            }
        }

    }

    /**
     * 房天下二手房
     */
    private function Fang(){
        $Tag = \QL\QueryList::Query($this->content,[
            #http://esf.sh.fang.com/chushou/14_97230255.htm
            "isOffImg" => ['.titleSa > img','src'],
            #http://esf.sh.fang.com/chushou/10_2508569443.htm
            "isOffText" => ['dd.gray6 > strong','text', '', function($item){
                return gb2312_to_utf8($item);
            }],
        ])->getData();
        if($Tag){
            $this->settingHouseOff();
            $this->off_reason = 2;  //出售
        }
    }

    /**
     * 房天下整租
     */
    private function FangRent()
    {

    }
    /***
     *
     * 租房
     *
     */

    /***
     * 自如整租
     */
    private function ZiroomRent()
    {
        $tag = \QL\QueryList::Query($this->content, [
            "isOff" => ['.view', 'text', '', function ($item) {
                return preg_match("/出租/", $item);
            }],
        ])->getData(function ($item) {
            return $item;
        });
        if ($tag[0]['isOff']) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /***
     * 中原整租
     */
    private function ZhongyuanRent()
    {
        preg_match('/zufang\/([\x{0000}-\x{ffff}]*?)\./u', $this->url, $ID);
        $value = $ID[1];
        $result_temp = json_decode(getXmlJsonSnoopy("http://mobileapi.centanet.com/010/api/Post?PostId=" . $value), 1);
        usleep(20000);
        if ($result_temp['RCode'] == 400) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /***
     * 我爱我家整租
     */
    private function WiwjRent()
    {
        $tag = \QL\QueryList::Query($this->content, [
            "isOff" => ['.house_updown', 'class', ''],
        ])->getData(function ($item) {
            return $item;
        });
        if ($tag) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /***
     * Q房整租
     */
    private function QfangRent()
    {
        $url = getHtml($this->url);
        $json_2 = json_decode($url, true);
        if ($json_2['status'] != 'C0000' || $json_2['message'] == '房源已下架或已删除') {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /**
     * 链家整租
     */
    private function LianjiaRent()
    {
        $Tag = \QL\QueryList::Query($this->content, [
        	'unshelf' 		=> ['.shelves', 'class', ''],
            'house-shelves' => ['.removeIcon', 'class', ''],
            'pic-cj' => ['.clinch-deal', 'class', ''],
        	'disparue'=>['.middle','text','',function($item){
        		return preg_match("/网址有错误/", $item);
        	}],
        ])->getData();
        if ($Tag) {
            $this->settingHouseOff();
            if (!empty($Tag[0]['house-shelves']) || $Tag[0]['disparue'] ||!empty($Tag[0]['unshelf'])) {
                $this->off_reason = 1;  //下架
            } else {
                $this->off_reason = 2;  //成交
            }
        }
    }

    /**
     * 酷房整租
     */
    private function KufangRent()
    {
        $Tag = \QL\QueryList::Query($this->content, [
            "isOff" => ['.big_qvkuai_top', 'text', '', function ($item) {
                return preg_match("/您访问的房源已售出/", $item);
            }],
        ])->getData(function ($item) {
            return $item;
        });
        if ($Tag[0]['isOff']) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /**
     * 爱屋吉屋整租
     */
    private function IwjwRent()
    {
        $Tag = \QL\QueryList::Query($this->content, [
            "isOff" => ['a.sellBtnView1:nth-child(3)', 'text', '', function ($text) {
                if (preg_match('/已租出/', $text)) {
                    return $off_type = 1;
                }
            }],
        ])->getData(function ($item) {
            return $item;
        });
        if ($Tag[0]['isOff']) {
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }
}