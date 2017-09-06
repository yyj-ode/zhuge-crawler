<?php
/**
 * 检测房源下架
 */
class houseOffClass{
    private $off_type = 2;
    private $off_reason = 1;
    private $city = '';
    private $url = '';

    /**
     * 检查房源是否下架
     * @param type $source 渠道
     * @param type $url 链接
     * @return type 类型
     */
    public function checkHouseOff($source = '', $url = ''){
        if(!empty($source) && !empty($url)){
            $this->url = $url;
            $func = explode('/', $source);
            $this->city = $func[0];
            $func = ucfirst(strtolower($func[1]));
            if(!empty($func)){
                $this->$func();
            }
        }
        return [$this->off_type, $this->off_reason];
    }
    
    public function __call($name, $arguments) {
        
    }
    
    private function querlistRun($rules = ''){
        if(!empty($rules) && is_array($rules)){
            $Tag = \QL\QueryList::run('Request', [
                'target' => $this->url,
            ])->setQuery($rules)->getData(function($item){return $item;});
            if(!empty($Tag)){
                return $Tag;
            }
        }
        return false;
    }


    /**
     * 设置下架
     */
    private function settingHouseOff(){
        $this->off_type = 1;
    }
    
    /**
     * 链家二手房
     */
    private function LianjiaBefore(){
        if($this->city == 'beijing'){
            $Tag = $this->querlistRun([
                'house-shelves' => ['.house-shelves','class',''],
                'pic-cj' => ['.pic-cj','class',''],
            ]);
            if($Tag){ 
                $this->settingHouseOff();
                if(!empty($Tag[0]['house-shelves'])){
                    $this->off_reason = 1;  //下架
                }else{
                    $this->off_reason = 2;  //成交
                }
            }
        }
    }

    /**
     * 链家二手房wap
     */
    private function Lianjia(){
        if($this->city == 'beijing'){
            $source_url = $this->url;
            $tmp = explode('/', $source_url);
            $tmp1 = explode('.', $tmp[4]);
            $house_code = $tmp1[0];
            $wap_api = "http://m.api.lianjia.com/house/chengjiao/detail?house_code=".$house_code."&share_agent_ucid=&access_token=&utm_source=&device_id=58423a7e-4f27-42a1-9a14-c97337719271&city_id=110000";

            $json = json_decode(getSnoopy($wap_api), 1);

                if($json['data']['house_code'] == $house_code){
                    $this->settingHouseOff();
                    $this->off_reason = 2;  //成交
                }

        }
    }

    /**
     * 我爱我家二手房
     */
    private function Wiwj(){
        if($this->city == 'beijing' || $this->city = 'nanjing'){
            $Tag = $this->querlistRun([
                "isOff" => ['.house_updown','class',''],
//                "404" => ['.main_top','class',''],
            ]);
            if($Tag){
                $this->settingHouseOff();
                $this->off_reason = 1;  //下架
            }
        }
    }
    
    /**
     * 58二手房
     */
    private function Five8Personal(){
        if($this->city == 'beijing'){
            $html = file_get_contents($this->url);
            //抓取下架标识
            if (preg_match("/ico_error/", $html)){
                $this->settingHouseOff();
                $this->off_reason = 1;  //下架
            }
        }
    }
    
    /**
     * 58二手房
     */
    private function Fang(){
        
    }
    
    /**
     * 房天下整租
     */
    private function FangRent(){
        
    }
    
    
    
}