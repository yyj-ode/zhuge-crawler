<?php
/**
 * Created by PhpStorm.
 * User: baijunfeng
 * Date: 16/8/2
 * Time: 下午4:38
 */

namespace guangzhou;


class ZhongyuanDeal extends \baserule\ZhongyuanDealBase
{
    public $URL = 'http://gz.centanet.com/xiaoqu/';
    public function house_page()
    {
        $are = array("tianhe","yuexiu","haizhu","baiyun","panyu","huadu","liwan","huangpu","zengcheng","nanhai","conghua","nansha","shunde","chancheng","zhongshan","sanshui","qingyuan","zhaoqing","yangjiang","gaoming");
        $urlarray = [];
        foreach ($are as $key => $value){
            for($j = 1;$j<7;$j++){
                $url = $this->URL."house/{$are[$key]}-u{$j}";
                $html = $this->getUrlContent($url.'/');
                $page_info = \QL\QueryList::Query($html,[
                    'max' => ['body > div.bodybg_white > div.main-wrapper > h3 > em', 'text','',function($max){
                        return $max;
                    }],
                ])->getData(function($data){
                    return $data['max'];
                });
                unset ($html);
                if(intval($page_info[0]>0)){
                    $max = ceil(intval($page_info[0])/10);
                    for($p = 1;$p<=$max;$p++){
                        $urlarray[] = $url."-p{$p}/";
                    }
                }
            }
        }
        return $urlarray;
    }

    public function house_list($url)
    {
        $html = $this->getUrlContent($url);
        $list_info = \QL\QueryList::Query($html,[
            'salenum' => ['#ListHouse > li > div > div.communitylist_mid > h2 > span:nth-child(2) > a', 'text','',function($max){
                return urlencode($max);
            }],

        ],'',null,null,true)->getData(function($data){
            return $data['salenum'];
        });
        unset ($html);
        foreach($list_info as $key => $value){
            $urlarray[] = "http://gz.centanet.com/ctrxiaoqu/DetailTrendData/{$value}";
        }
        return $urlarray;
    }


    public function house_detail($source_url)
    {
        $html = getSnoopy($source_url);
        $house_info = \QL\QueryList::Query($html,[
            'finish_time' => ['table.mbox_table > tbody > tr> td:nth-child(1)', 'text','',function($data){
                return $data;
            }],
            'finish_price' => ['table.mbox_table > tbody > tr> td:nth-child(6) > b', 'text', '', function($data){
                return str_replace('万','',$data);
            }],
            'house_area' => ['table.mbox_table > tbody > tr > td:nth-child(5)', 'text'],
            'house_room' => ['table.mbox_table > tbody > tr > td:nth-child(3)', 'text', '', function($data){
                return str_replace('居','',$data);
            }],
            'house_toward' => ['table.mbox_table > tbody > tr > td:nth-child(2)', 'text'],
            'house_floor' => ['table.mbox_table > tbody > tr > td:nth-child(4)', 'text','',function($floor){
                return $floor;
            }],
        ])->getData(function($data) use($source_url){
            $data['broker_name'] = '';
            $data['company_name'] = '中原';
            return $data;
        });
        unset ($html);
        writeLog('zhongyuandeal_house_detail', ['url' => $source_url, 'datas'=> $house_info[0]], $this -> _log);
        return $house_info;
    }
}