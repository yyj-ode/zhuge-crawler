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
     * 链家二手房wap
     */
    private function Lianjia()
    {
        $source_url = $this->url;
        $tmp = explode('/', $source_url);
        $tmp1 = explode('.', $tmp[4]);
        $house_code = $tmp1[0];
        $wap_api = "http://m.api.lianjia.com/house/chengjiao/detail?house_code=" . $house_code . "&share_agent_ucid=&access_token=&utm_source=&device_id=1f3c9e90-86aa-4d32-9eb0-1a8a3df2b7a6&city_id=320100";
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
     * 房天下二手房
     */
    private function Fang()
    {
        $Tag = \QL\QueryList::Query($this->content, [
            #http://esf.sh.fang.com/chushou/14_97230255.htm
            "isOffImg" => ['.titleSa > img', 'src'],
            #http://esf.sh.fang.com/chushou/10_2508569443.htm
            "isOffText" => ['dd.gray6 > strong', 'text', '', function ($item) {
                return gb2312_to_utf8($item);
            }],
        ])->getData();
        if ($Tag) {
            $this->settingHouseOff();
            $this->off_reason = 2;  //出售
        }
    }
}