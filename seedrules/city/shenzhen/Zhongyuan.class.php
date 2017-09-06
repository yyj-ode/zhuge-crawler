<?php namespace shenzhen;
/**
 * @description 深圳中原二手房抓取规则
 * @classname 深圳 =======《深圳中原》=======深圳
 */

Class Zhongyuan extends \baserule\Zhongyuan
{
    public $city_name = 'sz';

    public function house_detail($source_url){
        $value = str_replace(array('http://sz.centanet.com/ershoufang/','.html'),'',$source_url);
        $apiurl = 'http://api.sz.centanet.com/v3/';
        $result_temp=json_decode(getSnoopy("{$apiurl}0755/json/reply/GetPostRequest?PostId=".$value."&PostType=B&WithoutDetail=true"),1);
        $result_image_array=json_decode(getSnoopy("{$apiurl}0755/json/reply/GetPostImagesRequest?PostId=".$value),1);
        $result_image=$result_image_array['Result'];
        $result=$result_temp['Result'];
        $result_agen=json_decode(getSnoopy("{$apiurl}0755/json/reply/GetStaffCommentRequeset?IsNeedComment=true&PageCount=1&PageIndex=1&PostId=".$value),1);
        $result_qaq = $result_agen['Result'][0];
        //标题
        $house_info['house_title']=$result['Title'];
        //标签
        $house_info['tag']=preg_replace("/,/","#",$result['KeyWords']);
//        var_dump($house_info['tag']);die;
        //价格
        $house_info['house_price']=round($result['SalePrice']/10000,2);
        //室
        $house_info['house_room'] =$result['RoomCount'];
        //厅
        $house_info['house_hall'] = $result['HallCount'];
        //卫
        $house_info['house_toilet'] =$result['ToiletCount'] ;
        //面积
        $house_info['house_totalarea']=$result['GArea'];
        //朝向
        $house_info['house_toward']=$result['Direction'];
        //所在楼层
        $data=$result['FloorDisplay'];
        preg_match("/(.*?)层\/共(\d+)层/",$data,$arr);
        $house_info['house_floor'] = $arr[1];
        //总楼层
        $house_info['house_topfloor']=$result['FloorTotal'];
        //装修情况
        $house_info['house_fitment']=$result['Fitment'];
        //房源描述
        $house_info['house_desc']=trimall($result_qaq['PostDirection']);
        //房屋类型
        $house_info['house_type']=$result['PropertyType'];
        //建成年代?
        $house_info['house_built_year']= date('Y',$result['OpDate']);
        //城区
        $house_info['cityarea_id'] = $result['RegionName'];
        //商圈
        $house_info['cityarea2_id'] = $result['GscopeName'];
        //小区名
        $house_info['borough_name'] = $result['EstateName'];
        //房源人员名字
        $house_info['owner_name'] = $result_qaq['StaffName'];
        //房源人员电话
        $house_info['owner_phone'] = $result_qaq['StaffMobile'];
        //房源经纪人服务商区
        $house_info['fuwu_shq'] = $result_qaq['StoreName'];
        //经纪人公司
        $house_info['company_name']='中原';

        $house_info['house_pic_unit']=array();

        foreach($result_image as $imgK=>$imgV){
            $house_info['house_pic_unit'][]=str_replace('.jpg','_2000x2000.jpg',$imgV['FullImagePath']);
        }
        $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
        $house_info['house_pic_unit']=implode('|',$house_info['house_pic_unit']);

        //来源
        $house_info['source'] = 2;
        $house_info['source_owner'] = 0;
        $house_info['is_fill'] = 2;
        $house_info['content'] = $this->getUrlContent($source_url);
        writeLog('Zhongyuan' . __FUNCTION__, ['url' => $house_info], true);
        return $house_info;
    }

}