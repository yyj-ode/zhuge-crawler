<?php namespace shanghai;
/**
 * @description 上海房天下成交
 * @classname 上海房天下成交
 */
Class FangDeal extends \baserule\FangDeal{
    protected $housePageUrl = 'http://esf.sh.fang.com/housing/';

    /**
     * 获取列表分页
     */
    public function house_page(){
        $prices = ['0_10000', '10000_15000', '15000_20000', '20000_30000', '30000_40000', '40000_50000', '50000_0'];
        $queryData = $this -> getDis($this -> housePageUrl);
        $dis = $queryData['dis'];
        if( !$this -> housePageUrl || !$dis){
            return false;
        }

        $URL = $this -> housePageUrl;

        $urlarr=array();
        foreach ($dis as $value){
            $page = 1;

            // 类型：1住宅，2别墅
            $type = array(1,2);
            foreach($type as $v){
                $URLPRE = $URL.$value."__".$v."_0_0_0_0";
                $html = gb2312_to_utf8(getHtml($URLPRE.'/'));
                preg_match('/txt\">共(\d+?)页/u',$html,$page);
                $maxPage = $page[1];
                // 价格
                if($maxPage >= 101){
                    foreach($prices as $p){
                        $URLPRE = $URL.$value."__".$v."_0_{$p}_0";
                        $html = gb2312_to_utf8(getHtml($URLPRE.'/'));
                        preg_match('/txt\">共(\d+?)页/u',$html,$page);
                        $maxPage = $page[1];
                        for ($page=1; $page<=$maxPage; $page++){
                            $urlarr[]=$URLPRE.$page."_0_0/";
                        }
                    }

                }else{
                    for ($page=1; $page<=$maxPage; $page++){
                        $urlarr[]=$URLPRE.$page."_0_0/";
                    }
                }

            }
        }
        return $urlarr;
    }
}