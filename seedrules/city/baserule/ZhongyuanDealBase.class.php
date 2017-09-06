<?php namespace baserule;
/**
 * Created by PhpStorm.
 * User: baijunfeng
 * Date: 16/8/1
 * Time: 下午3:20
 */

class ZhongyuanDealBase extends \city\PublicClass
{
    public $URL;

    public function house_page()
    {
        $url = $this->URL.'xiaoqu/';
        $html = $this->getUrlContent($url);
        $page_info = \QL\QueryList::Query($html,[
            'max' => ['#form1 > div.centmain-wraper > div > div.result-lists > div.select-bar.clearfix > p.pagerNum.fr > span', 'text','',function($max){
                $max = explode('/',$max);
                return $max[1];
            }],

        ])->getData(function($data){
            return $data['max'];
        });
        unset ($html);
        for($i = 1;$i<=$page_info[0];$i++){
            $urlarray[] = $url."g{$i}/";
        }
        return $urlarray;
    }

    public function house_list($url)
    {
        $list_info = \QL\QueryList::run('Request',['target' => $url,])->setQuery([
            'max' => ['#form1 > div.centmain-wraper > div > div.result-lists > div.house-main > div.house-listBox > div > p > a', 'href','',function($max){
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
        $source_url = $this->URL."page/v1/ajax/dealrecord.aspx?cestcode={$info['1']}&posttype=S&pageindex=1&pagesize=30";
        $house_info = \QL\QueryList::run('Request',['target' => $source_url,])->setQuery([
            'finish_time' => ['.table-record .deal-items> td:nth-child(4) > a', 'text','',function($data){
                return str_replace('/','-',$data);
            }],
            'finish_price' => ['.table-record .deal-items > td:nth-child(5) > a > span', 'text','',function($data){
                return str_replace('万','',$data);
            }],
            'house_area' => ['.table-record .deal-items > td:nth-child(3) > a', 'text','',function($data){
                return str_replace('平','',$data);
            }],
            'house_room' => ['.table-record .deal-items > td:nth-child(1) > a', 'text','',function($data){
                $data = explode('室',$data);
                return $data[0];
            }],
            'house_hall' => ['.table-record .deal-items > td:nth-child(1) > a', 'text','',function($data){
                $data = explode('室',$data);
                return str_replace('厅','',$data[1]);
            }],
            'house_toward' => ['.table-record .deal-items> td:nth-child(2) > p > a:nth-child(1)', 'text'],
            'house_floor' => ['.table-record .deal-items > td:nth-child(2) > p > a:nth-child(2)', 'text','',function($floor){
                return str_replace('|','',$floor);
            }],
            'broker_name' => ['.table-record .deal-items > td:nth-child(7)', 'text'],
        ])->getData(function($data) use($source_url){
            $data['company_name'] = '中原';
            return $data;
        });
        writeLog('zhongyuandeal_house_detail', ['url' => $source_url, 'datas'=> $house_info[0]], $this -> _log);
        return $house_info;
    }

}