<?php
namespace baserule;
/**
 * Created by PhpStorm.
 * User: lihongdong
 * Date: 16/7/19
 * Time: 下午11:55
 */

class Iwjw extends \city\PublicClass
{
    protected $log = true; // 是否开启日志
    protected $host = 'http://www.iwjw.com';
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

        $url = $this -> host . '/sale/' . $this -> city.'/';
        $queryData = $this -> getHtmlQueryData($url);


        $urlarr=array();
        $error = $totalNum = [];
        foreach($queryData['dis'] as $dv){
            $disUrl = $url.$dv;
            $queryData = $this -> getHtmlQueryData($disUrl.'/');
            // 内容为空
            if(!$queryData){
                $error['empty'][] = $disUrl.'/';
                continue;
            }

            // 商圈
            if($queryData['totalnum'] > 3000 ){
                foreach($queryData['plate'] as $plate){
                    $plateQueryUrl = $url.$plate.'/';
                    echo $plateQueryUrl.PHP_EOL;
                    $plateQuery = $this -> getHtmlQueryData($plateQueryUrl);

                    // 内容为空
                    if(!$plateQuery){
                        $error['empty'][] = $plateQueryUrl;
                        continue;
                    }

                    $totalNum[] = $plateQuery['totalnum'];

                    // 分页超出最大值
                    if($plateQuery['totalnum'] > 3000){
                        $error['maxPage'][] = $plateQueryUrl;
                        $page = 100;
                    }else{
                        $page = ceil($plateQuery['totalnum'] / 30);
                    }

                    for($i=1; $i<=$page; $i++){
                        if($i == 1){
                            $urlarr[] = $url.$plate.'/';
                        }else{
                            $urlarr[] = $url.$plate.'p'.$i.'/';
                        }
                    }
                }
            }
            else{ // 城区
                echo $disUrl.'/'.PHP_EOL;
                $totalNum[] = $queryData['totalnum'];
                $page = ceil($queryData['totalnum'] / 30);
                for($i=1; $i<=$page; $i++){
                    if($i == 1){
                        $urlarr[] = $disUrl.'/';
                    }else{
                        $urlarr[] = $disUrl.'p'.$i.'/';
                    }
                }
            }
        }

        writeLog( 'Iwjw_'.__FUNCTION__, ['error_url'=>$error, 'totalNum' => $totalNum], $this -> log);
        return $urlarr;
    }


    /**
     * 获取列表页
     */
    public function house_list($url){
        $queryData = $this -> getHtmlQueryData( $url );

        // 抓取空日志
        if(!$queryData['list']) writeLog( 'Iwjw_'.__FUNCTION__, ['url'=>$url, 'msg' => '内容为空'], $this -> log);

        //echo 'house_list:' . $url.'/'.PHP_EOL;
        return $queryData['list'];
    }

    /**
     * 获取详情
     */
    public function house_detail($source_url){
        $html = getHtml($source_url);

        $house_info = [];
        \QL\QueryList::Query($html,[
            'house_title' => ['.detail-title-h1', 'text', '', function($data)use(&$house_info){
                $house_info['house_title'] = trim($data);
            }],

            'house_price' => ['p.g-fence:nth-child(1) > span:nth-child(1) > i:nth-child(1)', 'text', '', function($data)use(&$house_info){
                $house_info['house_price'] = $data;
            }],

            'cityarea_id' => ['a.mod-detail-nav-a:nth-child(3)', 'text', '', function($data)use(&$house_info){
                $house_info['cityarea_id'] = str_replace('二手房','',$data);
            }],

            'cityarea2_id' => ['a.mod-detail-nav-a:nth-child(4)', 'text', '', function($data)use(&$house_info){
                $house_info['cityarea2_id'] = str_replace('二手房','',$data);
            }],

            'house_totalarea' => ['span.thin:nth-child(3) > i:nth-child(1)', 'text', '', function($data)use(&$house_info){
                $house_info['house_totalarea'] = str_replace('平米', '', $data);
            }],

            'house_room' => ['span.thin:nth-child(2) > i:nth-child(1)', 'text', '', function($data)use(&$house_info){
                $house_info['house_room'] = $data;
            }],

            'house_hall' => ['span.thin:nth-child(2) > i:nth-child(2)', 'text', '', function($data)use(&$house_info){
                $house_info['house_hall'] = $data;
            }],
            'house_toilet' => ['span.thin:nth-child(2) > i:nth-child(3)', 'text', '', function($data)use(&$house_info){
                $house_info['house_toilet'] = $data;
            }],

            'house_pic_unit' => ['li.img-li > img:nth-child(1)', 'data-src', '', function($data)use(&$house_info){
                if(substr($data, 0, 4) == 'http'){
                    $house_info['house_pic_unit'][] = $data;
                }else{
                    $house_info['house_pic_unit'][] = 'http:'.$data;
                }
            }],

            'borough_name' => ['a.detail-more:nth-child(2)', 'text', '', function($data)use(&$house_info){
                $house_info['borough_name'] = $data;
            }],

            'house_built_year' => ['div.infos-mods:nth-child(2) > p:nth-child(3) > span:nth-child(1)','text','-i', function($data)use(&$house_info){
                $house_info['house_built_year'] = str_replace('年', '', $data);
            }],

            'infos' => ['div.list-infos','html', '',function($data)use(&$house_info){
                preg_match("/<p.*?><i\sclass=\"pname\">楼层：<\/i>(.*?)<\/p>/isu", $data, $match);
                list($floor, $topFloor) = explode("/",$match[1]);
                $house_info['house_floor'] = str_replace(['层', '--'], '', $floor);
                $house_info['house_topfloor'] = str_replace(['层', '--'], '', $topFloor);

                preg_match("/<p.*?><i\sclass=\"pname\">装修：<\/i>(.*?)<\/p>/isu", $data, $match);
                $house_info['house_fitment'] = str_replace([ '—'], '', $match[1]);

                preg_match("/<p.*?><i\sclass=\"pname\">朝向：<\/i>(.*?)<\/p>/isu", $data, $match);
                $house_info['house_toward'] = str_replace([ '—'], '', $match[1]);
            }],

            'tag' => ['div.list-infos > div.item-state.item-infos > em', 'text', '',function($data)use(&$house_info){
                in_array($data, $this -> tag) &&  $house_info['tag'][] = $data;
            }],
        ])->getData();

        //匹配video_url
        preg_match('/hd:\s+\"([^\"]+)/', $html,$video);
        $house_info['video_url'] = $video[1];

        // 内容为空日志
        if(!$house_info)  writeLog( 'Iwjw_'.__FUNCTION__, ['url'=>$source_url, 'html'=>$html, 'msg' => '内容为空'], $this ->log);

        $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
        $house_info['company_name'] = '爱屋吉屋';
        //本渠道没有房源户型图
        //$house_info['house_pic_layout'] = '';
        //$house_info['house_kitchen'] = '';
        //$house_info['owner_phone'] = '';
        //$house_info['owner_name'] = '';
        //$house_info['house_desc'] = '';
        //$house_info['house_style'] = '';

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
        for($i = 1; $i <= 100; $i++){
            if($i == 1){
                $resultData[] = $this -> host."/sale/" . $this -> city ."/o1/";
            }else{
                $resultData[] = $this -> host."/sale/" . $this -> city ."/o1p{$i}/";
            }
        }

        writeLog('Iwjw_'.__FUNCTION__, $resultData, $this -> log);
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
        \QL\QueryList::Query($html, [
            'totalnum' => ['.relative-num', 'data-num'],
            'dis' => ['ul.find-list > li.list-item > a', 'data-url'],
            'plate' => ['div.find-second > ul.find-list > li a', 'data-url'],
            'list' => ['ol.ol-border > li.list-item > a.hPic ', 'href'], // 列表
        ])->getData(function ($data) use (&$queryData) {
            // 总数
            isset($data['totalnum']) && $queryData['totalnum'] = $data['totalnum'];

            // 城区
            isset($data['dis']) && $queryData['dis'][] = trim($data['dis'], '/');

            // 商圈
            isset($data['plate']) && $queryData['plate'][] = trim($data['plate'], '/');

            // 列表
            isset($data['list']) && $queryData['list'][] = $this -> host . $data['list'];
        });
        return $queryData;
    }

}