<?php
/**
 * Created by PhpStorm.
 * User: baijunfeng
 * Date: 16/7/13
 * Time: 下午2:45
 * @description 南京365二手房
 * @classname 南京365
 */

namespace baserule;


class House365 extends \city\PublicClass
{
    public $PRE_URL = 'http://nj.sell.house365.com/district';
    private $current_url = '';
    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        $dis = array(
            '1' => '鼓楼',
            '2' => '玄武',
            '4' => '雨花台',
            '6' => '建邺',
            '7' => '秦淮',
            '8' => '江宁',
            '9' => '栖霞',
            '10' => '浦口',
            '11' => '六合',
            '13' => '溧水',
            '14' => '高淳',
            '15' => '其他',
        );
        $p = array(
            '1' => '40万以下',
            '2' => '40-60万',
            '3' => '60-90万',
            '4' => '90-120万',
            '5' => '120-150万',
            '6' => '150-200万',
            '7' => '200-300万',
            '8' => '300-500万',
            '9' => '500万以上',
        );

        $urlarr = [];
        foreach ($dis as $k1 => $v1){
            foreach ($p as $k2 => $v2) {
                $this->current_url = $this->PRE_URL . '_d' . $k1 . "-i1/dl_x1-j{$k2}";
                $source_url = $this->current_url.'.html';
                $url = \QL\QueryList::run('Request', [
                    'target' => $source_url,
                ])->setQuery([
                    'link' => ['#secend_search_nav > div.lb_zdfy.fr > p > span:nth-child(2)', 'text', '', function ($total) {
                        $link = [];
                        for ($Page = 1; $Page <= $total; $Page++) {
                            $link[] = $this->current_url."-p{$Page}.html";
                        }
                        return $link;
                    }],
                ])->getData(function ($item) {
                    return $item['link'];
                });
                $urlarr = array_merge($urlarr, $url[0]);
            }
        }
        return $urlarr;
    }

    public function house_list($url)
    {
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['#qy_list_cont > div.info_list', 'data-url', '', function($u){
                return $u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $house_info;
    }

    public function house_detail($source_url)
    {
        $house_info = [];
        $tmp = explode('/', $source_url);
        $tmp1 = explode('.', $tmp[3]);
        $house_code = str_replace('s_','',$tmp1[0]);
        $wap_api = "http://mtapi.house365.com/?method=secondhouse.getHouseDetail&client=tf&city=nj&id={$house_code}&name=HouseSell";
        $json = json_decode($this->getUrlContent($wap_api), 1);
        $house_info['content'] = $json;
        if(!empty($json)){
            $house_info['source'] = 1;
            //标题
            $house_info['house_title'] = $json['title'];
            $house_info['borough_name'] = $json['blockinfo']['blockname'];
            $house_info['cityarea2_id'] = $json['streetname'];
            $house_info['cityarea_id'] = $json['district'];
            $house_info['house_price'] = $json['price'];
            //总面积
            $house_info['house_totalarea'] = $json['buildarea'];
            //室
            $house_info['house_room'] = $json['room'];
            //厅
            $house_info['house_hall'] = $json['hall'];
            //卫
            $house_info['house_toilet'] = $json['toilet'];
            //朝向
            $house_info['house_toward'] = $json['forward'];
            //楼层
            $story = explode('/',$json['story']);
            $floor = explode('-',$json['story'][0]);
            $house_info['house_floor'] = $floor[0];
            $house_info['house_topfloor'] = $story[1];
            //建造年份
            $house_info['house_built_year'] = $json['buildyear'];
            //经纪人电话姓名
            $house_info['owner_phone'] = $json['brokerinfo']['telno'];
            $house_info['owner_name'] = $json['brokerinfo']['truename'];
            $house_info['house_number'] = $json['id'];
            $house_info['house_fitment'] = $json['fitment'];
            $house_info['house_pic_unit'] = implode("|", $json['pics']);
            $house_info['source_url'] = $source_url;
            //匹配到的图片title待以后扩展使用
            $house_info['house_desc']= $json['remarknew'];
            $tages = explode('、',$json['h_chara']);
            $house_info['tag'] = implode('#',$tages);
            return $house_info;
        }
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($page= 1; $page<=100; $page++){
            $resultData[] = $this->PRE_URL ."_i1/dl_x1-p{$page}.html";
        }
        return $resultData;
    }
}