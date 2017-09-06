<?php
/**
 * Created by PhpStorm.
 * User: baijunfeng
 * Date: 16/8/2
 * Time: 下午4:47
 */

namespace shenzhen;


class ZhongyuanDeal extends \baserule\ZhongyuanDealBase
{
    public $URL = 'http://sz.centanet.com/';
    public function house_detail($source_url)
    {
        $info = explode('-',$source_url);
        $source_url = $this->URL."page/v1/ajax/dealrecord.aspx?cestcode={$info['1']}&posttype=S&pageindex=1&pagesize=30";
        $house_info = \QL\QueryList::run('Request',['target' => $source_url,])->setQuery([
            'finish_time' => ['div.tablerecord-list > .tablerecond-item > a > span:nth-child(4)', 'text','',function($data){
                return str_replace('/','-',$data);
            }],
            'finish_price' => ['div.tablerecord-list > .tablerecond-item > a > span:nth-child(5)', 'text','',function($data){
                return str_replace('万','',$data);
            }],
            'house_area' => ['div.tablerecord-list > .tablerecond-item > a > span:nth-child(3)', 'text','',function($data){
                return str_replace('平','',$data);
            }],
            'house_room' => ['div.tablerecord-list > .tablerecond-item > a > span:nth-child(1)', 'text','',function($data){
                $data = explode('室',$data);
                return $data[0];
            }],
            'house_hall' => ['div.tablerecord-list > .tablerecond-item > a > span:nth-child(1)', 'text','',function($data){
                $data = explode('室',$data);
                return str_replace('厅','',$data[1]);
            }],
            'house_toward' => ['div.tablerecord-list > .tablerecond-item > a > span.w_2', 'text','',function($floor){
                $floor = explode('|',$floor);
                return $floor[0];
            }],
            'house_floor' => ['div.tablerecord-list > .tablerecond-item > a > span.w_2', 'text','',function($floor){
                $floor = explode('|',$floor);
                return $floor[1];
            }],
            'broker_name' => ['div.tablerecord-list > .tablerecond-item > a > span:nth-child(7)', 'text'],
        ])->getData(function($data) use($source_url){
            $data['company_name'] = '中原';
            return $data;
        });
        writeLog('zhongyuandeal_house_detail', ['url' => $source_url, 'datas'=> $house_info[0]], $this -> _log);
        return $house_info;
    }
}