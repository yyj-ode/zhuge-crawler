<?php
namespace baserule;
/**
 * Created by PhpStorm.
 * User: lihongdong
 * Date: 16/7/19
 * Time: 下午11:55
 */

class Landzestate extends \city\PublicClass
{
    protected $log = true; // 是否开启日志
    protected $host = 'http://www.landzestate.com';
    protected $city = false;
    private $tag = [
        '满二',
        '满五唯一',
        '学区房',
        '电梯房',
        '地铁房',
        '地铁',
        '学区',
    ];

    Public function house_page(){
        if(!$this ->city) exit('城市不存在');

        $url = $this -> host . '/' . $this -> city.'/xiaoshou';
        $queryData = $this -> getHtmlQueryData($url);

        $maxPage = ceil($queryData['totalNum'] / 20);
        $urlarr=array();
        for($i=1; $i<=$maxPage; $i++){
            $urlarr[]= $this ->host . "/{$this -> city}/xiaoshou/p/".$i;
        }

        writeLog( 'Landzestate_'.__FUNCTION__, ['queryData'=>$queryData, 'url' => $urlarr], $this -> log);
        return $urlarr;
    }


    /**
     * 获取列表页
     */
    public function house_list($url){
        for($i=0; $i<3; $i++){
            $queryData = $this -> getHtmlQueryData( $url );
            if($queryData['list']) break;
            sleep(2);
        }

        // 抓取空日志
        if(!$queryData['list']) writeLog( 'Landzestate_'.__FUNCTION__, ['url'=>$url, 'msg' => '内容为空'], $this -> log);

        //echo 'house_list:' . $url.'/'.PHP_EOL;
        return $queryData['list'];
    }

    /**
     * 获取详情
     */
    public function house_detail($source_url){
        for($i=0; $i<3; $i++){
            $detail = file_get_contents($source_url);
            if($detail) break;
            sleep(2);
        }

        if(!$detail) return [];
        $house_info = [];
        $house_info['source'] = $this->getSource();
        $house_info['company_name']="丽兹行";
        //下架检测-暂未找到下架表示
        //$house_info['off_type'] = $this->is_off($source_url);

        #======================================================================
        \QL\QueryList::Query($detail,[
            'house_title' => ['div.pdlr10 > h4.ffw.fwb', 'text', '', function($data)use(&$house_info){
                $house_info['house_title'] = trim($data);
            }],

            'house_price' => ['div.bgf1f1f1 span.acff0000.lh30.fz24.fwb.ffw', 'text', '', function($data)use(&$house_info){
                preg_match("/(\d*)万/",$data,$price);
                $house_info['house_price'] = getValue($price, 1);
            }],

            'root_hall_floor' => ['div.pdlr10 > div:eq(1)', 'html', '', function($data)use(&$house_info){
                preg_match_all("/\<span\s*class=\"ac666\s*lh30\s*ffw\">([\x{0000}-\x{ffff}]*?)span\>/u",$data,$match);

                // 室
                preg_match("/\d*室/",strip_tags(getValue($match[0], 0)),$room1);
                $layout1 = str_replace("室","", getValue($room1, 0));
                $house_info['house_room'] = $layout1;

                //厅
                preg_match("/\d*厅/",strip_tags(getValue($match[0], 0)),$room2);
                $layout2 = str_replace("厅","",getValue($room2, 0));
                $house_info['house_hall'] = $layout2;

                $floor = $this -> searchLike($match[0], '层');
                //所在楼层
                preg_match("/([\x{0000}-\x{ffff}])\/*?层\//u",strip_tags( $floor ),$flo1);
                $house_info['house_floor'] = getValue($flo1, 1);

                //总楼层
                preg_match("/\/(\d+)层/",strip_tags( $floor ),$flo2);
                $house_info['house_topfloor'] = getValue($flo2, 1);
            }],

            'area_toward' => ['div.pdlr10 > div:eq(2)', 'html', '', function($data)use(&$house_info){
                preg_match_all("/\<span\s*class=\"ac666\s*lh30\s*ffw\">([\x{0000}-\x{ffff}]*?)span\>/u",$data,$match);

                //面积
                $house_info['house_totalarea'] = strip_tags( str_replace("㎡","",$this -> searchLike($match[0], '㎡')) );

                //朝向
                $house_info['house_toward'] = strip_tags($this -> searchLike($match[0], '东#南#西#北'));
            }],

            'owner_phone' => ['div.pdlr10 > div:eq(3) div.pull-left', 'text', '', function($data)use(&$house_info){
                $house_info['owner_phone'] = trimall( $data);
            }],

            'owner_name' => ['div.fz12.ffw.lh30 > a > p', 'text', '', function($data)use(&$house_info){;
                $house_info['owner_name'] = $data;
            }],


            'borough' => ['div.pdlr10 > div:eq(4) p:eq(0)', 'text', '', function($data)use(&$house_info){
                $house_info['borough_name'] = trimall( $data);
            }],

            'house_built_year' =>  ['div.pdlr10 > div:eq(5) p:eq(0)', 'text', '', function($data)use(&$house_info){
                preg_match("/(\d*)/",$data,$match);
                $house_info['house_built_year'] = $match[1];
            }],

            'house_fitment' => ['div.pdlr10 > div:eq(5) p:eq(1)', 'text', '', function($data)use(&$house_info){
                $house_info['house_fitment'] = trimall( $data);
            }],

            'cityarea' => ['div.pdlr10 > div:eq(6) p', 'text', '', function($data)use(&$house_info){
                list($cityarea_id, $cityarea2_id)=explode('，',$data);
                $house_info['cityarea_id'] = $cityarea_id;
                $house_info['cityarea2_id'] = $cityarea2_id;
            }],

            'house_desc' => ['p.fz12.lh24.mt15', 'text', '', function($data)use(&$house_info){
                $house_info['house_desc'] .= trimall( $data);
            }],

            'house_pic_unit' => ['#spec-list > ul:eq(0) > li img ', 'src', '', function($data)use(&$house_info){
                $house_info['house_pic_unit'][] = $data;
            }],



        ])->getData();
        $house_info['house_pic_unit'] = implode("|", array_unique( $house_info['house_pic_unit'] ) );
        $pic_panding = substr($house_info['house_pic_unit'],1,6);
        if($pic_panding=='Public'){
            $house_info['house_pic_unit']='';
        }
        if(!$detail)  writeLog( 'Landzestate_'.__FUNCTION__, ['url'=>$source_url, 'msg' => '内容为空'], $this ->log);
        return $house_info;
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 29; $i++){
            $resultData[] = "http://www.landzestate.com/{$this -> city}/xiaoshou/sort/7/p/{$i}";
        }

        return $resultData;
    }



    /**
     * querylist匹配数据
     * @param $url 访问地址
     * @return array
     */
    protected function getHtmlQueryData($url){

        $queryData = [];
        $html = getHtml($url);
        \QL\QueryList::Query($html,[
            'totalNum' => ['span.fwb', 'text', '', function($data)use(&$queryData){
                $queryData['totalNum'] = $data;
            }],
            'list' => ['div  a', 'href', '', function($data)use(&$queryData){
                if( preg_match('/xiaoshou\/houseId/',$data ) ){
                    $queryData['list'][] = $this->host . $data;
                }
            }],
        ])->getData();
        $queryData['list'] = array_unique( $queryData['list']);
        return $queryData;
    }

    //下架判断
    /*
    public function is_off($url,$html=''){
        return 2;//TODO 未找到下架房源
         if(!empty($url)){
             if(empty($html)){
                 $html = $this->getUrlContent($url);
             }
             //抓取下架标识
             $off_type = 1;
             $newurl = get_jump_url($url);
             $oldurl = str_replace('shtml','html',$url);
             if($newurl == $oldurl){
                 $Tag = \QL\QueryList::Query($html,[
                     "isOff" => ['.contenttop_err','text','',function ($item){
                         return preg_match("/存在/",$item);
                     }],
                     ])->getData(function($item){
                         return $item;
                     });
                     foreach ($Tag[0] as $key=>$value) {
                         if($key == "isOff" && $value == 1){
                             return 1;
                         }else{
                             return 2;
                         }
                     }
                     return 2;
             }
         }
    }*/

    /**
     * 数组模糊查询
     */
    protected function searchLike($data, $searchValue){
        $search = explode('#', $searchValue);
        foreach($data as $k => $v){
            foreach($search as $sv){
                if(stristr ( $v ,  $sv ) !== false ){
                    return $v;
                }
            }
        }

        return '';
    }

}