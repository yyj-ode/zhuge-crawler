<?php namespace baserule;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/15
 * Time: 10:53
 */
Class Zhongyuannew extends \city\PublicClass{
    public $city_name;
    private $city_id = array("bj" => "010", "gz" => "440100", "sz " => "0755", "nj" => "025","sh" => "021","tj" => "022");

    private $url;

    public function __construct($path = '')
    {
        parent::__construct($path);
        $this->city_id = $this->city_id[$this->city_name];
        $this->url = "http://{$this->city_name}.centanet.com/ershoufang/";
    }

    public function house_page(){
        $resultData = [];

        $html = getHtml($this->url);
        $page_info = \QL\QueryList::Query($html,[
            'max' => ['#form1 > div.centmain-wraper > div > div.result-lists > div.select-bar.clearfix > p.pagerNum.fr > span', 'text','',function($max){
                $max = explode('/',$max);
                return $max[1];
            }],

        ])->getData(function($data){
            return $data['max'];
        });
        unset ($html);

        for($i = 1; $i <= $page_info[0]; $i++){
            $resultData[] = $this->url."g{$i}/";
        }
        writeLog('Zhongyuan_page' . __FUNCTION__, ['url' => $resultData], true);
        return $resultData;
    }
    /*
     * 获取列表页
    */
    Public function house_list($url){
        for($i = 0;$i<3;$i++){
            $html = getHtml($url);
            $house_info = \QL\QueryList::Query($html,[
                'list' => ['.house-listBox > div.house-item', 'id']
            ])->getData(function($data){
                return $this->url.$data['list'].'.html';
            });
            unset($html);
            if($house_info){
                break;
            }
            sleep(2);
        }
        if(!$house_info) writeLog( 'Zhongyuan_list'.__FUNCTION__, ['url'=>$url, 'msg' => '种子为空'], true);

        return $house_info;
    }

    /*
     * 获取详情
    */
    public function house_detail($source_url){
        //$html = getHtml($source_url);
        $html = getSnoopy($source_url);
        $house_info = [];

        preg_match('/<p\x*class=\"hotlineA\".*p>/',$html,$item);
        $text1 = '/<p.*?imobile.*</';
        preg_match($text1, $html, $result);

        //电话
        $phtml = 'http://wap.centanet.com/'.$this->city_name.'/ershoufang/'.preg_replace('/[^@]+\//','',$source_url);
        $rules = array('text' => array('p[id="J_current_detail_button_400"]','text'));
        $house_info['owner_phone'] = \QL\QueryList::Query($phtml,$rules)->data[0]['text'];

        $house_info['created'] = time();
        $house_info['updated'] = time();
        \QL\QueryList::Query($html,[
            //标题
            'house_title' => ['.f18.fl', 'text', '', function($data)use(&$house_info){
                $house_info['house_title'] = trim($data);
            }],

            //标签
            'tag' => ['.labeltag', 'html', '', function($data)use(&$house_info){
                $arr = str_replace('</span>','#',$data);
                $house_info['tag'] = $arr;
            }],

            //价格
            'house_price' => ['.roombase-price .cRed', 'text', '', function($data)use(&$house_info){
                $house_info['house_price'] = str_replace('万','',$data);
            }],

            //城区
            'cityarea_id' => ['div[class="fl breadcrumbs-area f000 "]', 'text', '', function($data)use(&$house_info){
                $data = explode('&gt;',trim($data));
                $house_info['cityarea_id'] = trimall($data[2]);
            }],

            //商圈
            'cityarea2_id' => ['div[class="fl breadcrumbs-area f000 "] .f000', 'text', '', function($data)use(&$house_info){
                $house_info['cityarea2_id'] = trim($data);
            }],

            //面积
            'house_totalarea' => ['.roombase-price .f000', 'text', '', function($data)use(&$house_info){
                $house_info['house_totalarea'] = str_replace('平', '', $data);
            }],

            //室
            'house_room' => ['.roombase-price', 'text', '', function($data)use(&$house_info){
                preg_match("/(\d+)室/", $data, $r);
                $house_info['house_room'] =$r[1];
            }],

            //厅
            'house_hall' => ['.roombase-price', 'text', '', function($data)use(&$house_info){
                preg_match("/(\d+)厅/", $data, $r);
                $house_info['house_hall'] = $r[1];
            }],

            //卫
            'house_toilet' => ['.roombase-price', 'text', '', function($data)use(&$house_info){
                preg_match("/(\d+)卫/", $data, $r);
                $house_info['house_toilet'] = $r[1];
            }],
            //小区名
            'borough_name' => ['div[class="fl breadcrumbs-area f000 "] .f666', 'text', '', function($data)use(&$house_info){
                $house_info['borough_name'] = $data;
            }],

            //建成年代
            'house_built_year' => ['ul[class="hbase_txt clearfix"]','text','-i', function($data)use(&$house_info){
                preg_match("/(\d+)年/", $data, $r);
                $house_info['house_built_year'] = $r[1];
            }],

            //经纪人/房源人员名字
            'owner_name' => ['a[class="f000 f18"] b', 'text', '', function($data)use(&$house_info){
                $house_info['owner_name'] = $data;
            }],

            //所在楼层
            'house_floor' => ['ul[class="hbase_txt clearfix"]', 'text', '', function($data)use(&$house_info){
                $data = trimall($data);
                preg_match("/楼层：(.*?)层\(共(\d+)层/",$data,$arr);
                $house_info['house_floor'] = $arr[1];
                //总楼层
                $house_info['house_topfloor'] = $arr[2];
            }],

            //朝向
            'house_toward' => ['ul[class="hbase_txt clearfix"]', 'text', '', function($data)use(&$house_info){
                $data = trimall($data);
                preg_match("/朝向：(东|西|南|北|东南|西南|东北|西北|东西|南北)/", $data, $r);
                $house_info['house_toward'] = $r[1];
            }],

            //装修情况
            'house_fitment' => ['ul[class="hbase_txt clearfix"]', 'text', '', function($data)use(&$house_info){
                $data = trimall($data);
                preg_match("/装修：(精装|简装|豪装|毛坯)/", $data, $r);
                $house_info['house_fitment'] = $r[1];
            }],

            //服务商圈
            'fuwu_shq' => ['dl[class="roompeple szroompeple clearfix"]', 'text', '', function($data)use(&$house_info){
                $data = trimall($data);
                preg_match("/所在分行：(\S+)服务板块/", $data, $r);
                $house_info['fuwu_shq'] = $r[1];
            }],

//            //房源人员电话(false)
//            'owner_phone' => ['#J_post400_init', 'text', '', function($data)use(&$house_info){
//                $data = trimall($data);
//                $house_info['owner_phone'] = $data;
//            }],

            //房源描述
            'house_desc' => ['div[class="mid f666"]', 'text', '', function($data)use(&$house_info){
                $data = trimall($data);
                $house_info['house_desc'] = $data;
                //来源

                $house_info['source'] = 2;
                $house_info['source_owner'] = 0;
                $house_info['is_fill'] = 2;
                $house_info['company_name']='中原';
            }],


        ])->getData();
        $house_info['content'] = $html;
        return $house_info;
    }


    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        $newurl = $this->url.'u7';
        for($i = 1; $i <= 100; $i++){
            $resultData[] = $newurl."g{$i}/";
        }
        writeLog('Zhongyuan_new_page' . __FUNCTION__, ['url' => $resultData], true);
        return $resultData;
    }
}
