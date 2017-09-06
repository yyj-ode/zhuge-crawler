<?php namespace baserule;
/**
 * @description 中原二手房抓取规则
 * @classname 中原
 */
Class Zhongyuan extends \city\PublicClass{
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
        $value = str_replace(array($this->url,'.html'),'',$source_url);
        $apiurl = 'http://mobileapi.centanet.com/';
        if($this->city_name == 'sz'){
            $apiurl = 'http://api.sz.centanet.com/';
        }
        if($this->city_name == 'sh'){
            $apiurl = 'http://apish.centanet.com/';
        }
        $result_temp=json_decode(getHtml("{$apiurl}{$this->city_id}/api/Post?PostId=".$value),1);
        $result_image_array=json_decode(getHtml("{$apiurl}{$this->city_id}/api/PostImg?PostId=".$value),1);
        $result_image=$result_image_array['Result'];
        $result=$result_temp['Result'];
        $result_agen=json_decode(getHtml("{$apiurl}{$this->city_id}/api/Staff?staffNo=".$result['StaffNo']."&postId=".$value),1);
        //标题
        $house_info['house_title']=$result['Title'];
        //标签
        $house_info['tag'] = array(); ;
        foreach($result['PostList']['KeyWords'] as $tagK=>$tagV){
            $house_info['tag'][]=$tagV;
        }
        $house_info['tag'] = array_unique($house_info['tag']);
        $house_info['tag']=implode('#',$house_info['tag']);
        //价格
        $house_info['house_price']=round($result['SellPrice']/10000,2);
        //室
        $house_info['house_room'] =$result['RoomCnt'];
        //厅
        $house_info['house_hall'] = $result['HallCnt'];
        //卫
        $house_info['house_toilet'] =$result['ToiletCnt'] ;
        //面积
        $house_info['house_totalarea']=$result['GArea'];;
        //朝向
        $house_info['house_toward']=$result['Direction'];
        //所在楼层
        $house_info['house_floor']=str_replace('层','',$result['FloorDisplay']);
        //总楼层
        $house_info['house_topfloor']=$result['FloorTotal'];
        //装修情况
        $house_info['house_fitment']=$result['Fitment'];
        //房源描述
        $house_info['house_desc']=trimall($result['PlainDescription']);
        //房屋类型
        $house_info['house_type']=$result['PostList']['PropertyType'];
        //建成年代
        preg_match('/(\d{4})\-/',$result['OpDate'],$year);
        $house_info['house_built_year']= $year[1];
        //城区
        $house_info['cityarea_id'] = $result['ReginName'];
        //商圈
        $house_info['cityarea2_id'] = $result['BlockName'];
        //小区名
        $house_info['borough_name'] = $result['CestName'];
        //房源人员名字
        $house_info['owner_name'] = $result_agen['Result']['CnName'];
        //房源人员电话
        $house_info['owner_phone'] = $result_agen['Result']['Mobile'];
        //房源经纪人服务商区
        $house_info['fuwu_shq'] = $result_agen['Result']['DepartmentName'];
        //经纪人公司
        $house_info['company_name']='中原';

        $house_info['house_pic_unit']=array();
        $house_info['house_pic_layout']=array();

        foreach($result_image as $imgK=>$imgV){
            if($imgV['RefType'] == "UNIT"){
                //房源图
                $house_info['house_pic_unit'][]=$imgV['HdPath'];
            }
            if($imgV['RefType'] == "LAYOUT"){
                //房型图
                $house_info['house_pic_layout'][]=$imgV['HdPath'];
            }
        }

        $house_info['house_pic_layout'] = array_unique($house_info['house_pic_layout']);
        $house_info['house_pic_layout']=implode('|',$house_info['house_pic_layout']);

        $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
        $house_info['house_pic_unit']=implode('|',$house_info['house_pic_unit']);

        //来源
        $house_info['source'] = 2;
        $house_info['source_owner'] = 0;
        $house_info['is_fill'] = 2;
        $house_info['content'] = $this->getUrlContent($source_url);
//        dumpp($house_info['content']);die;
        writeLog('Zhongyuan' . __FUNCTION__, ['url' => $house_info], true);
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





