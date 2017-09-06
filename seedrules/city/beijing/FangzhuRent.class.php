<?php namespace beijing;
/**
 * @description 北京房主儿 整租房抓取规则
 * @classname 北京房主儿
 */
class FangzhuRent extends \city\PublicClass{
    public $PRE_URL = 'http://bj.fangzhur.com/rent/';
    private $current_url = '';
    private $hhhhhh = '';
    private $_log = false;
    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        $p = array(
            't1'  => '普通住宅',
            't10' => '公寓',
            't2'  => '别墅',
            't5'  => '写字楼',
            't7'  => '商铺',
            't11' => '平房/四合院',
            't12' => '其他',
        );
        $urlarr = [];

        foreach ($p as $k2 => $v2){
            $this->current_url = $this->PRE_URL.$k2;
            $url = \QL\QueryList::run('Request', [
                'target' => $this->current_url,
            ])->setQuery([
                'link' => ['#RL_NoPage','text', '', function($total){
                    $total = explode('/', $total);
                    $total = $total[1];
                    $link = [];
                    for($Page = 1; $Page <= $total; $Page++){
                        $link[] = $this->current_url.'pag'.$Page;
                    }
                    return $link;
                }],
            ])->getData(function($item){
                return $item['link'];
            });
            $urlarr = array_merge($urlarr,$url[0]);
        }
        return $urlarr;
    }

    public function house_list($url)
    {
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['.content_b1 .listing ol .property2 .details h4 a', 'aid', '', function($u){
                return $this->PRE_URL.'d-'.$u.'.html';
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        writeLog( 'Fangzhu_'.__FUNCTION__, $house_info, $this -> _log);
        return $house_info;
    }

    public function house_detail($source_url)
    {
        $html = gb2312_to_utf8(getSnoopy($source_url));
        $this->hhhhhh = $html;
        $house_info = \QL\QueryList::Query($html,[
            'house_title' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang40 > h1', 'text', '', function($title){
                return trimall($title);
            }],

            'house_price' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(1) > span > font', 'text', '', function($price){
                return trimall($price);
            }],
            'cityarea_id' => ['body > div.main > div.bangzhu_bzzx > div.xiang > div > span > a:nth-child(3)', 'text', '', function($cityarea_id){
                return trimall($cityarea_id);
            }],
            'cityarea2_id' => ['body > div.main > div.bangzhu_bzzx > div.xiang > div > span > a:nth-child(4)', 'text', '', function($cityarea2_id){
                return trimall($cityarea2_id);
            }],
            'borough_name' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul','html','',function($borough){
                preg_match("/小区：([\x{0000}-\x{ffff}]+?)<\/li>/u",$borough,$bn);
                return strip_tags($bn[1]);
            }],
            'house_totalarea' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '', function($house_totalarea){
                preg_match("/面积：([\x{0000}-\x{ffff}]+?)<\/li>/u",$house_totalarea,$bn);
                return intval($bn[1]);
            }],

            'house_room' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '', function($house_room){
                preg_match("/(\d+)室/", $house_room, $hr);
                return $hr[1];
            }],

            'house_hall' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '', function($house_hall){
                preg_match("/(\d+)厅/", $house_hall, $hh);
                return $hh[1];
            }],
            'house_toilet' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '', function($house_toilet){
                preg_match("/(\d+)卫/", $house_toilet, $ht);
                return $ht[1];
            }],
//          朝向
            'house_toward' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '',function($house_toward){
                preg_match("/朝向：(.*?)<\/li>/u",$house_toward,$bn);
                return $bn[1];
            }],

            'house_floor' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '', function($house_floor){
                preg_match('/第(\d+)层/',$house_floor,$hf);
                return $hf[1];
            }],

            'house_topfloor' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul', 'html', '', function($house_topfloor){
                preg_match('/共(\d+)层/',$house_topfloor,$htf);
                return $htf[1];
            }],

            'owner_name' => '房主儿',

            'owner_phone' => ['.phones', 'text', ''],

            'house_pic_unit' => ['#List1 li a img', 'src', '', function($house_pic_unit){
                return $house_pic_unit;
            }],


            'house_fitment' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul','html','',function($house_fitment){
                preg_match("/装修情况：([\x{0000}-\x{ffff}]+?)<\/li>/u",$house_fitment,$bn);
                return $bn[1];
            }],



            'house_desc' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.sale_xx_left > div.xiang5 > span','text',''],

            'house_number' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang3 > span', 'text', '-span', function($house_number){
                $house_number = explode('：',$house_number);
                return $house_number[1];
            }],

            'house_built_year' => ['body > div.main > div.bangzhu_bzzx > div.expra > div.expra_left > div.xiang4 > div.xiang42 > div.xiang421 > div:nth-child(2) > ul','html','',function($house_built_year){
                preg_match("/建成年代：([\x{0000}-\x{ffff}]+?)<\/li>/u",$house_built_year,$bn);
                return $bn[1];
            }],

        ])->getData(function($data) use($source_url){
            $data['company_name'] = '房主儿';
            $data['source'] = '10';
            $data['source_owner'] = '1';
            return $data;
        });
        foreach((array)$house_info as $key => $value){
            if(isset($house_info[$key]['house_pic_unit'])){
                $house_pic_unit[] = str_replace('_thumb', '', $house_info[$key]['house_pic_unit']);
            }
        }
        $house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
        writeLog( 'Fangzhu_'.__FUNCTION__, $house_info[0], $this -> _log);
        return $house_info[0];
    }

    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 100; $i++){
            $resultData[] = "http://bj.fangzhur.com/rent/c7pag".$i;
        }
        writeLog( 'Fangzhu_'.__FUNCTION__, $resultData, $this -> _log);
        return $resultData;
    }

}