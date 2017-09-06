<?php namespace tianjin;
    /**
     * @description 天津赶集网 整个人房源抓取规则
     * @classname 天津赶集网
     */
class GjPersonal extends \city\PublicClass
{
    public $PRE_URL = 'http://tj.ganji.com/fang5/';
    private $hhhhhh = '';
    private $_log = false;
    public $current_url = '';
    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        //实测结果：赶集城区显示的数字不准确,设数量为$sum,其总量小于($sum/31)*60%,赶集总量约为39000(2016/9/28)
        //调试单个函数时，如果速度过慢，可以暂时把getHtml函数改成getSnoopy函数来测试，实际运行需改回去，防止IP被封禁
        $cityareas = ['heping','hedong','hexi','nankai','heibei','hongqiao','tanggu','hangu',
                        'dagang','kaifaqu','dongli','xiqing','beichen','jinnan','wuqing',
                        'baodi','jixian','jinghai','ninghe','binhaixinqu'];
        $urlarr = [];
        foreach($cityareas as $cityarea){
            sleep(mt_rand(2, 5));
            $url = $this->PRE_URL.$cityarea.'/a1';
            $html = getHtml($url);
            preg_match("/赶集网为您找到<strong\sclass=\"fc-org\">(\d+)条<\/strong>/",$html,$maxPage);
            $maxPage = floor($maxPage[1]/31*0.6);
            for($i = 1;$i<=$maxPage;$i++){
                $urlarr[] = $url.'o'.$i;
            }
//            var_dump($urlarr);die;
        }
        return $urlarr;
    }

    public function house_list($url)
    {
        sleep(mt_rand(2, 5));
        $html = getHtml($url);
        //调试单个函数时，如果速度过慢，可以暂时把getHtml函数改成getSnoopy函数来测试，实际运行需改回去，防止IP被封禁
        //切忌疯狂刷新,会封IP
        //切记：单独测试house_list函数时,不要用house_page来获得$url,很慢,且容易被封IP,建议使用以下几个url来测试
        //http://tj.ganji.com/fang5/heping/a1o1
        //http://tj.ganji.com/fang5/kaifaqu/a1o4

        //获取单个房源url,getData方法会自动补全本页面的类似url
        $house_info = \QL\QueryList::Query($html,[
            'link' => ['.listBox > ul > li > div > a','href','',function($u){
                //该网站url有两套逻辑：判断$u长度，如果大于50，则直接返回$u
                if(strlen($u)>50){
                    return $u;
                }
                return 'http://tj.ganji.com'.$u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $house_info;
    }

    public function house_detail($source_url)
    {
        //调试单个函数时，如果速度过慢，建议暂时把getHtml函数改成getSnoopy函数来测试，实际运行需改回去，防止IP被封禁
        //切忌疯狂刷新,会封IP！！改回getHtml后，虽然可以防止IP封禁，但效率会降低100倍左右！！(该情况会持续数小时)
        //切记：单独测试house_detail函数时,不要用house_detail获得$url,很慢,且容易被封IP,建议用以下几个url来测试
        //    [0] => http://tj.ganji.com/fang5/2357971333x.htm
        //    [1] => http://tj.ganji.com/fang5/2339141914x.htm
        //    [2] => http://tj.ganji.com/fang5/2311233447x.htm
        //    [3] => http://tj.ganji.com/fang5/2375580095x.htm
        //    [4] => http://tj.ganji.com/fang5/2358177136x.htm
        //    [5] => http://tj.ganji.com/fang5/2349321606x.htm
        //    [6] => http://tj.ganji.com/fang5/2337429426x.htm
        //    [7] => http://tj.ganji.com/fang5/2356917919x.htm
        //    [8] => http://tj.ganji.com/fang5/2319932530x.htm
        //    [9] => http://tj.ganji.com/fang5/2199812399x.htm
        sleep(mt_rand(2, 5));
        $html = getHtml($source_url);
        //注意——1：这里的每个选择器,在拓展新城市的时候,请谨慎使用,直接复制粘贴的话,请用10个左右的页面测试,以确保正确性
        //注意——2：一些网站,页面不只有一个排版,若有几个排版,建议不要使用querylist,容易出错,请使用正则
        $house_info = \QL\QueryList::Query($html,[
            'house_price' => ['ul[class="basic-info-ul"] > li > b', 'text', '', function($price){
                return trimall($price);
            }],
            'house_desc' => ['div[class="summary-cont"]', 'text', '', function($describe){
                return trimall($describe);
            }],
            'house_title' => ['h1[class="title-name"]', 'text', '', function($title){
                return trimall($title);
            }],
            //切记：好多页面只有城区没有商圈，确保该选测器健壮性，防止抓空。万一扑空，使用正则在getData外面弥补
            'cityarea_id' => ['div[class="crumbs clearfix"] > a:eq(3)', 'text', '', function($cityarea){
                $arr = explode('二手房出售',$cityarea);
                return $arr[0];
            }],
            //注：很多页面没有该元素，极易抓空
            'cityarea2_id' => ['div[class="crumbs clearfix"] > a:eq(4)', 'text', '', function($cityarea){
                $arr = explode('二手房出售',$cityarea);
                return $arr[0];
            }],

            //以下元素在不同页面有位置变化，在此表示提醒，后用正则补齐，否则极易抓错
            'house_floor' => '',//所在楼层
            'house_topfloor' => '',//总楼层
            'house_toward' => '',//朝向
            'house_room' => '',//几室
            'house_hall' => '',//几厅
            'house_toilet' => '',//几卫
            'house_fitment' => '',//装修程度
            //'use_area' => '',//使用面积(该网站基本没有这个字段)
            'house_totalarea' => '',//建筑面积
            'owner_name' => '',//业主姓名
            'owner_phone' => '',//业主电话
            'borough_name' => '',//小区名
            //'house_built_year' => '',//(该网站基本没有这个字段)
            //'service_phone' => '',//(该网站基本没有这个字段)
            'house_pic_unit' => '',//房源图片
            //'tag' => '',//(该网站基本没有这个字段)

            //以下字段为固定字段
            'source_name' => '',//渠道名
            'source_url' => '',//详情页url
        ])->getData(function($data){
            //补齐不需要传参的固定字段
            $data['source_name'] = '赶集';
            return $data;
        });

        //补齐需要传参的固定字段
        $house_info[0]['source_url'] = $source_url;

        //补齐非固定位置的元素(需用正则)
        //所在楼层
        preg_match("/(高|中|低)层/", $html, $house_floor);
        $house_info[0]['house_floor'] = $house_floor[1];//很多页面没有这个字段
        //所在楼层
        preg_match("/共(\d+)层/", $html, $house_topfloor);
        $house_info[0]['house_topfloor'] = $house_topfloor[1];
        //总楼层
        preg_match("/(南北|东西|东南|东北|西南|西北|)朝向/", $html, $house_toward);
        $house_info[0]['house_toward'] = $house_toward[1];
        if(empty($house_info[0]['house_toward'])){
            preg_match("/朝(东|南|西|北)/", $html, $house_toward);
            $house_info[0]['house_toward'] = $house_toward[1];
        }
        //几室
        preg_match("/(\d+)室/", $html, $house_room);
        $house_info[0]['house_room']=empty($house_room)?0:$house_room[1];
        //几厅
        preg_match("/(\d+)厅/", $html, $house_hall);
        $house_info[0]['house_hall']=empty($house_hall)?0:$house_hall[1];
        //几卫
        preg_match("/(\d+)卫/", $html, $house_toilet);
        $house_info[0]['house_toilet']=empty($house_toilet)?0:$house_toilet[1];
        //装修程度
        preg_match("/装修程度：<\/span>(.*)<\/li>/",$html,$fitment);
        $house_info[0]['house_fitment'] = $fitment[1];
        //建筑面积
        preg_match("/建筑面积：([\x{0000}-\x{ffff}]+?)<\/li>/u",$html,$area_1);
        preg_match("/<\/span>(\d+)/",$area_1[1],$area_2);
        $house_info[0]['house_totalarea'] = $area_2[1];
        //业主姓名
        preg_match("/在线联系：([\x{0000}-\x{ffff}]+?)<\/i>/u",$html,$owner_name);
        $owner_name = explode('>',$owner_name[1]);
        $house_info[0]['owner_name'] = $owner_name[1];
        //业主联系电话
        preg_match("/联系方式([\x{0000}-\x{ffff}]+?)<\/em>/u",$html,$owner_phone);
        $owner_phone = explode('>',$owner_phone[1]);
        $house_info[0]['owner_phone'] = trimall($owner_phone[3]);
        //小区名
        preg_match("/区：<\/span>([\x{0000}-\x{ffff}]+?)\(/u",$html,$borough_name);
        $borough_name = str_replace('&nbsp;','',$borough_name[1]);
        $borough_name = trimall($borough_name);
        $borough_name = strip_tags($borough_name);
        preg_replace("/<[\x{0000}-\x{ffff}]+>/u","",$borough_name); //------>>标签替换不掉
        $house_info[0]['borough_name'] = $borough_name;
        //房源图片
        preg_match("/cont-box\spics\">([\x{0000}-\x{ffff}]+?)<\/div>/u",$html,$pics);
        preg_match_all("/src=\"([\x{0000}-\x{ffff}]+?)\"/u",$pics[1],$pictures);
        $pics='';
        foreach($pictures[1] as $picture){
            $pics= $pics.$picture.'|';
        }
        $house_info[0]['house_pic_unit'] = $pics;

        //返回结果
        return $house_info[0];
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 63; $i++){
            $resultData[] = $this->PRE_URL.'a1o'.$i;
        }
//        dumpp($resultData);die;
        writeLog( 'Gj_'.__FUNCTION__, $resultData, $this -> _log);
        return $resultData;
    }
}