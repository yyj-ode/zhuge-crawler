<?php
namespace baserule;
/**
 * Created by PhpStorm.
 * User: lihongdong
 * Date: 16/7/19
 * Time: 下午11:55
 */

class Wukong extends \city\PublicClass
{
    protected $log = true; // 是否开启日志
    protected $host = 'http://www.wkzf.com';
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
        sleep(rand(1,5));
        if(!$this ->city) exit('城市不存在');

        $url = $this -> host . '/' . $this -> city.'/esf';
        $queryData = $this -> getHtmlQueryData($url);


        $urlarr=array();
        $error = $totalNum = [];
        foreach($queryData['dis'] as $dv){
            $disUrl = $url.'/'.$dv;
            $queryData = $this -> getHtmlQueryData($disUrl);
            // 内容为空
            if(!$queryData){
                $error['empty'][] = $disUrl;
                continue;
            }

            // 商圈
            if($queryData['totalnum'] > 2000 ){
                foreach($queryData['plate'] as $plate){
                    $plateQueryUrl = $disUrl . '-' . $plate;
//                    echo $plateQueryUrl.PHP_EOL;
                    $plateQuery = $this -> getHtmlQueryData($plateQueryUrl);

                    // 内容为空
                    if(!$plateQuery){
                        $error['empty'][] = $plateQueryUrl;
                        continue;
                    }

                    $totalNum[] = $plateQuery['totalnum'];

                    // 分页超出最大值
                    if($plateQuery['totalnum'] > 2000){
                        $error['maxPage'][] = $plateQueryUrl;
                    }

                    $urlarr = array_merge($urlarr, $this -> getPageData($disUrl.'/', $queryData['totalnum']));
                }
            }
            else{ // 城区
                echo $disUrl.PHP_EOL;
                $totalNum[] = $queryData['totalnum'];
                $urlarr = array_merge($urlarr, $this -> getPageData($disUrl.'/', $queryData['totalnum']));
            }
        }

        writeLog( 'wukong_'.__FUNCTION__, ['error_url'=>$error, 'totalNum' => $totalNum], $this -> log);
        return $urlarr;
    }


    /**
     * 获取列表页
     */
    public function house_list($url){
        $queryData = $this -> getHtmlQueryData( $url );

        // 抓取空日志
        if(!$queryData['list']) writeLog( 'wukong_'.__FUNCTION__, ['url'=>$url, 'msg' => '内容为空'], $this -> log);

        return $queryData['list'];
    }

    /**
     * 获取详情
     */
    public function house_detail($source_url){
        // 内容为空日志

        $html = getHtml( $source_url );
        //$html = $this -> getUrlContent($source_url);

        $house_info = [];
        \QL\QueryList::Query($html, [
            'house_title' => ['.newHouseInfo > h2:nth-child(1)', 'text'],
            'house_price' => ['.totalPrice', 'text', '-em -span '],
            'cityarea_id' => ['.breadCrumbs > a:nth-child(5)', 'text' ],
            'cityarea2_id' => ['.breadCrumbs > a:nth-child(7)', 'text'],
            'house_totalarea' =>  ['.houseData li:contains("建筑面积")', 'text', '-span'],
            'room_hall_toilet' => ['.houseData li:contains("户型")', 'text', '-span'],
            'house_toward' => ['.houseData li:contains("朝向")', 'text', '-span'],
            'house_floor' => ['.houseData li:contains("所在楼层")', 'text', '-span'],
            'house_pic_unit' => ['div.mainPhotoPlayItem > div:nth-child(1) > img:nth-child(1)', 'src'],
            'house_fitment' => ['.houseData li:contains("装修")', 'text', '-span'],
            'borough_name' => ['a.active', 'text', '-span'],
            'house_desc' => ['.showUl > li:nth-child(1) > p:nth-child(2)','text',''],
            'house_number' => ['.houseDataMore > p:nth-child(2)', 'text'],
            'house_type' => ['.houseData li:contains("类型")', 'text', '-span'],
            'house_built_year' => ['ul.xiaoquInfo:nth-child(3) > li:nth-child(2)','text','-span'],
            'tag' => ['div.houseTag > span', 'text'],
        ])->getData(function ($data) use (&$house_info) {
            if( isset($data['house_title']) ){
                $house_info['house_title'] = str_replace(array("\t","<span>","</span>", "\n", "\r", " "),"", $data['house_title']);
            }

            if( isset($data['house_price']) ){
                $house_info['house_price'] =  str_replace(['￥','万'],'',$data['house_price']);
            }

            if( isset($data['cityarea_id']) ){
                $house_info['cityarea_id'] = str_replace('二手房','',$data['cityarea_id']);
            }

            if( isset($data['cityarea2_id']) ){
                $house_info['cityarea2_id'] = str_replace('二手房','',$data['cityarea2_id']);
            }

            if( isset($data['house_totalarea']) ){
                $house_info['house_totalarea'] = str_replace('㎡', '', $data['house_totalarea']);
            }

            if( isset($data['room_hall_toilet']) ){
                preg_match("/(\d+)室/", $data['room_hall_toilet'], $hr);
                $house_info['house_room'] = $hr[1];
            }

            if( isset($data['room_hall_toilet']) ){
                preg_match("/(\d+)厅/", $data['room_hall_toilet'], $hh);
                $house_info['house_hall'] = $hh[1];
            }

            if( isset($data['room_hall_toilet']) ){
                preg_match("/(\d+)卫/", $data['room_hall_toilet'], $ht);
                $house_info['house_toilet'] = $ht[1];
            }

            if( isset($data['house_floor']) ){
                $house_info['house_floor'] = str_replace('层', '', trim($data['house_floor'], '-'));
            }

            if( isset($data['house_pic_unit']) ){
                $house_info['house_pic_unit'][] = $data['house_pic_unit'];
            }

            if( isset($data['house_fitment']) ){
                $house_info['house_fitment'] = $data['house_fitment'];
            }

            if( isset($data['borough_name']) ){
                $house_info['borough_name'] =$data['borough_name'];
            }

            if( isset($data['house_desc']) ){
                $house_info['house_desc'] = $data['house_desc'];
            }
            if( isset($data['house_number']) ){
                $number = explode("房源编号：",$data['house_number']);
                $house_info['house_number'] = trimall($number[1]);
            }

            if( isset($data['house_type']) ){
                $house_info['house_type'] = $data['house_type'];
            }

            if( isset($data['house_built_year']) ){
                $house_info['house_built_year'] = $data['house_built_year'];
            }

//            if(isset($data['tag']) && in_array($data['tag'], $this -> tag)){
//                $house_info['tag'][] = $data['tag'];
//            }

        });

        if(!$house_info)  writeLog( 'wukong_'.__FUNCTION__, ['url'=>$source_url, 'msg' => '内容为空'], $this ->log);

        $house_info['company_name'] = '悟空找房';
        $house_info['house_pic_unit'] = isset($house_info['house_pic_unit']) ? implode('|', $house_info['house_pic_unit']) : '';

        return $house_info;
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){
        $resultData = [];
        for($i = 1; $i <= 100; $i++){
            $resultData[] = $this -> host.'/'.$this -> city."/esf/p{$i}-o0";
        }

        writeLog('wukong_'.__FUNCTION__, $resultData, $this -> log);
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
        //$html = $this -> getUrlContent($url);

        \QL\QueryList::Query($html, [
            'totalnum' => ['.searchFilterbox > .filterfr > span', 'text', '', function($data)use(&$queryData){
                preg_match('/(\d+)/', $data, $match);
                $queryData['totalnum'] = $match[1];
            }],

            'dis' => ['div.searchFilter > dl:eq(0) > dd:eq(0) > a', 'href', '', function($data)use(&$queryData){
                preg_match('/esf\/(.*)/', $data, $match);
                if($match[1]) $queryData['dis'][] = $match[1];
            }],

            'plate' => ['div.searchFilter > dl:eq(0) > dd:eq(1) > a', 'href', '', function($data)use(&$queryData){
                preg_match('/esf\/.+-(.*)/', $data, $match);
                if($match[1]) $queryData['plate'][] = $match[1];
            }],

            // 列表
            'list' => ['li > div:nth-child(2) > a:nth-child(1)', 'href','', function($data)use(&$queryData){
                $queryData['list'][] = $this -> host . $data;
            }],
        ])->getData();

        return $queryData;
    }

    /**
     * 分页
     * @param $url
     * @param $totalNum
     * @return array
     */
    private function getPageData($url, $totalNum){
        $result = [];
        $page = ceil($totalNum / 20);
        for($i=1; $i<=$page; $i++){
            $result[] =$url.'p'.$i;
        }

        return $result;
    }

}