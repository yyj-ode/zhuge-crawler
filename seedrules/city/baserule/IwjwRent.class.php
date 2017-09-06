<?php
namespace baserule;
/**
 * Created by PhpStorm.
 * User: Jason_kong
 * Date: 16/8/16
 * Time: 下午11:55
 */

class IwjwRent extends \city\PublicClass
{
    protected $log = false; // 是否开启日志
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

        $url = $this -> host . '/chuzu/' . $this -> city.'/';
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

        writeLog( 'IwjwRent_'.__FUNCTION__, ['error_url'=>$error, 'totalNum' => $totalNum], $this -> log);
        return $urlarr;
    }


    /**
     * 获取列表页
     */
    public function house_list($url){
        $queryData = $this -> getHtmlQueryData( $url );

        // 抓取空日志
        if(!$queryData) writeLog( 'IwjwRent_'.__FUNCTION__, ['url'=>$url, 'msg' => '内容为空'], $this -> log);

        //echo 'house_list:' . $url.'/'.PHP_EOL;
        return $queryData['list'];
    }

    /**
     * 获取详情
     */
    public function house_detail($source_url){
        $html = getHtml($source_url);
        // 内容为空日志
        if(!$html)  writeLog( 'IwjwRent_'.__FUNCTION__, ['url'=>$source_url, 'msg' => '内容为空'], $this ->log);


       		$house_info = 
       		\QL\QueryList::Query($html,[
						'house_title' => ['.detail-title-h1', 'text', '-i', function($title){
							return str_replace(array("\t", "\n", "\r", " "),"", $title);
						}],
														
						'house_price' => ['.g-fence > span:nth-child(1) > i:nth-child(1)', 'text', '', function($price){
							return $price;
						}],
						'cityarea_id' => ['a.mod-detail-nav-a:nth-child(3)', 'text', '', function($cityarea_id){
							return str_replace('租房','',$cityarea_id);
						}],
							
						'cityarea2_id' => ['a.mod-detail-nav-a:nth-child(4)', 'text', '', function($cityarea2_id){
							return str_replace('租房','',$cityarea2_id);
						}],
						'house_totalarea' => ['span.thin:nth-child(3) > i:nth-child(1)', 'text', '', function($house_totalarea){
							return str_replace(' m²', '', $house_totalarea);
						}],
	
						'house_room' => ['span.thin:nth-child(2) > i:nth-child(1)', 'text', '', function($house_room){
// 							preg_match("/(\d+)室/", $house_room, $hr);
							return $house_room;
						}],
	
						'house_hall' => ['span.thin:nth-child(2) > i:nth-child(2)', 'text', '', function($house_hall){
							return $house_hall;
						}],
						'house_toilet' => ['span.thin:nth-child(2) > i:nth-child(3)', 'text', '', function($house_toilet){
							return $house_toilet;
						}],
							
	
						'house_toward' => ['div.item-infos:nth-child(4) > p:nth-child(1)', 'text', '-i',function($house_toward){
							return $house_toward;
						}],
						'house_floor' => ['div.item-infos:nth-child(1) > p:nth-child(1)', 'text', '-i', function($house_floor){
							$temp_topfloor1 = explode("/",$house_floor);
							return str_replace('层', '', $temp_topfloor1[0]);
						}],
						'house_topfloor' => ['div.item-infos:nth-child(1) > p:nth-child(1)', 'text', '-i', function($house_topfloor){
							$temp_topfloor2 = explode("/",$house_topfloor);
							return trimall(str_replace('层', '', $temp_topfloor2[1]));
						}],
	
// 						'owner_name' => ['.mr-t', 'text','',function ($owner_name){
// 							return $owner_name;
// 						}],
	
// 						'owner_phone' => ['.house-broker-tel','text','-a', function ($owner_phone){
// 							return trimall($owner_phone);
// 						}],
						'house_pic_unit' => ['li.img-li > img:nth-child(1)', 'data-src', '', function($house_pic_unit){
							return $house_pic_unit;
						}],
	
						'house_pic_layout' => [],
	
						'house_fitment' => ['div.item-infos:nth-child(5) > p:nth-child(1)','text','-i'],
						'borough_name' => ['a.mod-detail-nav-a:nth-child(5)', 'text', '', function($borough_name){
							return str_replace('租房','',$borough_name);
						}],
	
						'house_type' => [],
	
						'house_built_year' => ['div.infos-mods:nth-child(2) > p:nth-child(3) > span:nth-child(1)','text','-i',function($house_built_year){
				   return str_replace('年', '', $house_built_year);
						}],
	
						'house_relet' => [],
	
						'house_style' => [],
						])->getData(function($data) use($source_url){
							$data['company_name'] = '爱屋吉屋';
							$data['source']= 6;
							$data['source_url'] = $source_url;
							return $data;
						});
						foreach((array)$house_info as $key => $value){
							if(isset($house_info[$key]['house_pic_unit'])){
								if(!strstr($house_info[$key]['house_pic_unit'],'http:')){
									$house_pic_unit[] = "http:".$house_info[$key]['house_pic_unit'];
								}else{
									$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
								}
								
							}
						}
						$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
						
						if(strstr($house_pic_unit[0],"layout")){
							$house_info[0]['house_pic_layout'] = $house_pic_unit[0];
							unset($house_pic_unit[0]);
						}
						$html1 = file_get_contents($html);
						preg_match("/朝向：([\x{0000}-\x{ffff}]*?)<\/p>/u", $html1, $toward);
						preg_match("/装修：([\x{0000}-\x{ffff}]*?)<\/p>/u", $html1, $fit);
						if(strstr($toward[1],"—")){
							$toward[1]="";
						}else{
							$toward[1] = str_replace("</i>","",$toward[1]);
						}
						if(strstr($fit[1],"—")){
							$fit[1]="";
						}else{
							$fit[1]=str_replace("</i>","",$fit[1]);
						}
						$house_info[0]['house_toward'] = $toward[1];
						$house_info[0]['house_fitment'] = $fit[1];

        $house_info[0]['content'] = $html;
        return $house_info[0];
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
                $resultData[] = $this -> host."/chuzu/" . $this -> city ."/o1/";
            }else{
                $resultData[] = $this -> host."/chuzu/" . $this -> city ."/o1p{$i}/";
            }
        }

        writeLog('IwjwRent_'.__FUNCTION__, $resultData, $this -> log);
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