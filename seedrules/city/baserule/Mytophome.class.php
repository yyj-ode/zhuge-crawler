<?php
/**
 * Created by PhpStorm.
 * User: baijunfeng
 * Date: 16/7/12
 * Time: 下午4:22
 *  @description 南京满堂红二手房
 * @classname 南京满堂红
 */

namespace baserule;


class Mytophome extends \city\PublicClass
{
    public $source_url;
    private $tag = [
        '证满2年',
        '唯一',
    ];
    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        $html = $this->getUrlContent($this->source_url);
        $url = \QL\QueryList::Query($html,
            [
                'totalNum' => ['#search_box_1 > div.two_li > div.two_li_biao > ul > li.two_li_biao_li02', 'text', '', function($totalNum){
                    $temp_topfloor1 = explode("/",$totalNum);
                    return intval($temp_topfloor1[1]);
                }],
            ])->getData(function($item){
                return $item['totalNum'];
            }
        );
        unset($html);
        $maxPage = $url[0] - 1;
        $urlarr = [];
        for($page= 0; $page<=$maxPage; $page++){
            $limt = $page * 24;
            $urlarr[] = $this->source_url.'?&p='.$limt;
        }
        return $urlarr;
    }

    public function house_list($url)
    {
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['#style1 > ul > li > div.two_word > ul > li.two_word_li01 > a', 'href', '', function($u){
                return $u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $house_info;
    }

    public function house_detail($source_url)
    {
        $html = getSnoopy($source_url);
        $house_info = \QL\QueryList::Query($html,[
            'house_title' => ['#ctn_mt_box > div.ctn_mtl_box > div.maintitle h1', 'text', '', function($title){
                $title = gbk_to_utf8($title);
                return $title;
            }],
            'house_price' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > ul > li:nth-child(1) > span', 'text', '', function($price){
                return $price;
            }],
            'cityarea_id' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > p:nth-child(5) > a:nth-child(1)', 'text', '', function($cityarea_id){
                return gbk_to_utf8($cityarea_id);
            }],

            'cityarea2_id' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > p:nth-child(5) > a:nth-child(2)', 'text', '', function($cityarea2_id){
                return gbk_to_utf8($cityarea2_id);
            }],

            'house_totalarea' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.right_txt_box > div:nth-child(1)', 'text', '', function($house_totalarea){
                $temp_topfloor = explode(";",$house_totalarea);
                $ht = gbk_to_utf8($temp_topfloor[1]);
                return str_replace('平方','',$ht);
            }],

            'house_room' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.right_txt_box > div:nth-child(1)', 'text', '', function($house_room){
                $temp_topfloor = explode(";",$house_room);
                $hr = gbk_to_utf8($temp_topfloor[0]);
                preg_match("/(\d+)房/", $hr, $hrr);
                return $hrr[1];
            }],

            'house_hall' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.right_txt_box > div:nth-child(1)', 'text', '', function($house_hall){
                $temp_topfloor = explode(";",$house_hall);
                $temp_topfloor = gbk_to_utf8($temp_topfloor[0]);
                preg_match("/(\d+)厅/", $temp_topfloor, $hh);
                return $hh[1];
            }],
//            'house_toilet' => ['.house-info-2 > li:nth-child(1)', 'text', '-span', function($house_toilet){
//                preg_match("/(\d+)卫/", $house_toilet, $ht);
//                return $ht[1];
//            }],


            'house_toward' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.prop_info_box > ul > li:nth-child(3)', 'text', '',function($house_toward){
                $house_toward = str_replace('暂无描述','',gbk_to_utf8($house_toward));
                return str_replace('朝向：','',$house_toward);
            }],

            'house_floor' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.prop_info_box > ul > li:nth-child(1)', 'text', '', function($house_floor){
                $house_floor = gbk_to_utf8($house_floor);
                preg_match('/第(\d+)层/',$house_floor,$hf);
                return $hf[1];
            }],

            'house_topfloor' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.prop_info_box > ul > li:nth-child(1)', 'text', '', function($house_topfloor){
                $house_topfloor = gbk_to_utf8($house_topfloor);
                preg_match('/共(\d+)层/',$house_topfloor,$hf);
                return $hf[1];
            }],

            'owner_name' => ['#ctn_mt_box > div.ctn_mtr_box > div.ag_info_box > ul > li:nth-child(1) > a', 'text','',function ($owner_name){
                return gbk_to_utf8($owner_name);
            }],

            'owner_phone' => ['#ctn_mt_box > div.ctn_mtr_box > div.ag_info_box > ul > li:nth-child(3) > span','text','', function ($owner_phone){
                return trimall($owner_phone);
            }],

            'house_pic_unit' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.left_pic_box > p > a', 'href', '', function($house_pic_unit){
                return 'http://nj.mytophome.com'.$house_pic_unit;
            }],

            'house_pic_layout' => [],

            'house_fitment' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.prop_info_box > ul > li:nth-child(2)','text','',function($house_fitment){
                $house_fitment = str_replace('暂无描述','',gbk_to_utf8($house_fitment));
                return str_replace('装修：','',$house_fitment);
            }],
            'borough_name' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > p:nth-child(5) > a:nth-child(3)', 'text', '', function($borough_name){
                return gbk_to_utf8($borough_name);
            }],

 			'house_desc' => ['#detailbox1 > dl > dd:nth-child(4) > p','text','',function($house_desc){
                return gbk_to_utf8(trimall($house_desc));
            }],

            'house_number' => ['#ctn_mt_box > div.ctn_mtl_box > div.maintitle > p', 'text', '', function($house_number){
                $house_number = gbk_to_utf8($house_number);
                $temp_house_number = explode("发布时间：",$house_number);
                $temp_house_number = trimall($temp_house_number[0]);
                return str_replace('编号：','',$temp_house_number);
            }],

            'house_built_year' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > div.prop_info_box > ul > li:nth-child(4)','text','',function($house_built_year){
                $house_built_year = str_replace('楼龄：','',gbk_to_utf8($house_built_year));
                $house_built_year = str_replace('年','',$house_built_year);
                if(is_numeric(trimall($house_built_year))){
                    $year = date("Y");
                    $house_built_year = $year-$house_built_year;
                }else{
                    $house_built_year = '';
                }
                return $house_built_year;
            }],

            'tag' => ['#ctn_mt_box > div.ctn_mtl_box > div.ctn_l_box > div.right_txt_box > p:nth-child(6)','text','',function($tag){
                $tag = str_replace('性质：','',gbk_to_utf8($tag));
                $tags = [];
                $tag = trimall($tag);
                foreach((array)$this->tag as $val){
                    if(isExistsStr($tag, $val)){
                        $tags[] = $val;
                    }
                }
                return implode('#', $tags);
            }],
        ])->getData(function($data){
            $data['house_type'] = '1';
            return $data;
        });
        $house_info[0]['house_pic_unit'] = \QL\QueryList::run('Request', [
            'target' => $house_info[0]['house_pic_unit'],
        ])->setQuery([
            //获取单个图片url
            'link' => ['body > div.show_box > div.show_photo.clearfix > div.photo_right > div > ul > li > a > img', 'src', '', function($u){
                return $u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        foreach($house_info[0]['house_pic_unit'] as $key => $value){
            if($key == 0){
                $house_info[0]['house_pic_layout'] = $value;
            }else {
                $house_pic_unit[] = $value;
            }
        }
        $house_info[0]['content'] = $html;
        $house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
        $house_info[0]['source_url'] = urldecode($source_url);
        return $house_info[0];
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($page= 0; $page<=100; $page++){
            $limt = $page * 24;
            $resultData[] = $this->source_url.'?ob=createDate&ov=13&p='.$limt;
        }
        return $resultData;
    }
}