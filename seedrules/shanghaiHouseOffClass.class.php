<?php
/**
 * 检测房源下架
 */
class houseOffClass{
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
    public function checkHouseOff($source = '', $url = '', $content = ''){
        if(!empty($source) && !empty($url)){
            $this->url = $url;
            $this->content = $content;
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
        
    }

    /**
     * 链家二手房
     */
    private function Lianjia(){
        $Tag = \QL\QueryList::Query($this->content,[
            'house-shelves' => ['#album-box .tag_yixiajia','class'],
            'pic-cj' => ['.pic-cj','class'],
        ])->getData();

        if($Tag){
            $this->settingHouseOff();
            if(!empty($Tag[0]['house-shelves'])){
                $this->off_reason = 1;  //下架
            }else{
                $this->off_reason = 2;  //成交
            }
        }
    }

    /**
     * 我爱我家二手房
     */
    private function Wiwj(){
        #sh.5i5j.com/exchange/128902019
        $Tag = \QL\QueryList::Query($this->content,[
            "isOff" => ['.house_updown','class',''],
        ])->getData();
        if($Tag){
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }

    }

    /**
     * 爱屋及乌二手房
     */
    private function Iwjw(){

    }

    /**
     * 安个家二手房
     */
    private function Angejia(){

    }

    /**
     * 房多多二手房
     */
    private function Fdd(){
        $Tag = \QL\QueryList::Query($this->content,[
            #http://esf.fangdd.com/shanghai/1260755.html
            "isOffImg" => ['.house__status > img','src'],
        ])->getData();
        if($Tag){
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /**
     * 汉宇二手房
     */
    private function Hanyu(){
        if(trim($this->content) == '房源不存在或已过期'){
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /**
     * Q房二手房
     */
    private function Qfang(){

    }

    /**
     * 悟空找房二手房
     */
    private function Wukong(){

    }

    /**
     * 中原二手房
     */
    private function Zhongyuan(){

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
     * 58二手房
     */
    private function Five8Personal(){
        #http://m.58.com/bj/ershoufang/24394589787315x.shtml(暂未使用)
        #http://m.58.com/bj/ershoufang/23940770526014x.shtml
        $tag = \QL\QueryList::Query($this->content,[
            "isOff" => ['#ct  .msg_txt > img','alt'],
        ])->getData();
        if($tag){
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }

    /**
     * 酷房二手房
     */
    private function Kufang(){
        $tag = \QL\QueryList::Query($this->content,[
            "isOff" => ['.big_qvkuai_top','text'],
            #"error" => ['.contenttop_err','text'],
        ])->getData();
        if($tag){
            $this->settingHouseOff();
            $this->off_reason = 1;  //下架
        }
    }
    
}