<?php namespace tianjin;
/**
 * @description 天津房天下二手房抓取规则
 * @classname 天津房天下(k-ok)
 */

class Fang  extends \baserule\Fang
{
    public $host = 'http://esf.tj.fang.com/';
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = getSnoopy($url);
            }
            //抓取下架标识
            $off_type = 1;
            preg_match('/已下架/', $html,$Tag);
            if(empty($Tag)){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
}
