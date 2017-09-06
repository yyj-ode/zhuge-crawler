<?php
/**
 * Created by PhpStorm.
 * User: baijunfeng
 * Date: 16/8/2
 * Time: 下午4:44
 */

namespace shanghai;


class ZhongyuanDeal extends \baserule\ZhongyuanDealBase
{
    public $URL = 'http://sh.centanet.com/';
    public function house_page()
    {
        $url = $this->URL.'xiaoqu/';
        $html = $this->getUrlContent($url);
        $page_info = \QL\QueryList::Query($html,[
            'max' => ['#form1 > div.main-wrapper.gride_col.clearfix > h3 > em', 'text','',function($max){
                return $max;
            }],

        ])->getData(function($data){
            return $data['max'];
        });
        unset ($html);
        $page_info = ceil($page_info[0]/10);
        for($i = 1;$i<=$page_info;$i++){
            $urlarray[] = $url."g{$i}/";
        }
        return $urlarray;
    }

    public function house_list($url)
    {
        $list_info = \QL\QueryList::run('Request',['target' => $url,])->setQuery([
            'max' => ['#linkEstateName', 'href','',function($max){
                $max = explode('/',$max);
                return $max[2];
            }],

        ])->getData(function($data){
            return $data['max'];
        });
        foreach($list_info as $key => $value){
            $urlarray[] = $this->URL."xiaoqu/{$value}";
        }
        return $urlarray;
    }

    public function house_detail($source_url)
    {
        $info = explode('-',$source_url);
        $house_info = [];
        $source_url = $this->URL."page/v1/ajax/morerecord.aspx?cestcode={$info['1']}&posttype=S&pageno=0&pagesize=20&showDetailPeople=True&IsDetail=True";
        $html = getSnoopy($source_url);
        $house_url = \QL\QueryList::Query($html,[
            'url' => ['tr > td:nth-child(7) > a', 'href'],
        ])->getData(function($data) use($source_url){
            return 'http://sh.centanet.com'.$data['url'];
        });
        unset($html);
        foreach($house_url as $key => $value){
            $house_html = $this->getUrlContent($value);
            $house = \QL\QueryList::Query($house_html,[
                'finish_time' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > div > h1 > span.f000', 'text','',function($data){
                    $data = str_replace('成交','',$data);
                    return str_replace('/','-',$data);
                }],
                'finish_price' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > p > b', 'text','',function($data){
                    return trimall(str_replace('万','',$data));
                }],
                'house_area' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > div > h1 > span:nth-child(2)', 'text', '',function($data){
                    return trimall(str_replace('平','',$data));
                }],
                'house_room' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > div > h1 > span:nth-child(1) > b', 'text'],

                'house_floor' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > div > h1 > span:nth-child(4)', 'text','',function($floor){
                    $floor = explode('/',$floor);
                    return $floor[0];
                }],
                'house_topfloor' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > div > h1 > span:nth-child(4)', 'text','',function($floor){
                    $floor = explode('/',$floor);
                    return $floor[1];
                }],
                'house_toward' => ['#form1 > div.topB > div.Minfobox.Minfobox2 > div > div > h1 > span:nth-child(3)','text','',function($toward){
                    return trimall($toward);
                }],
                'broker_name' => ['#form1 > div.main-wrapper2.clearfix > div.sidebar.fr.sidebarB > div.person-info.box.person-info2 > div > dl > dd > h5 > a', 'text'],
            ])->getData(function($data) use($source_url){
                $data['company_name'] = '中原';
                return $data;
            });
            $house_info[] = $house[0];
        }
        writeLog('shanghaideal_house_detail', ['url' => $source_url, 'datas'=> $house_info[0]], $this -> _log);
        return $house_info;
    }
}