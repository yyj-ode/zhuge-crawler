<?php namespace tianjin;
/**
 * @description 天津链家地产二手房抓取规则
 * @classname 天津链家Wap抓取
 */

class Lianjia extends \baserule\Lianjia
{
    public $city_name = 'tj';
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = getSnoopy($url);
            }
            //抓取下架标识
            $off_type = 1;
            $Tag = \QL\QueryList::Query($html,[
                "isOff" => ['.xiajia','class',''],
//                "404" => ['.error-404','class',''],
            ])->getData(function($item){
                return $item;
            });
            if(empty($Tag)){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
}