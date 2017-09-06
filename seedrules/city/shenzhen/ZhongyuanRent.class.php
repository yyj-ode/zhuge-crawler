<?php namespace shenzhen;
/**
 * @description 深圳中原整租房抓取规则
 * @classname 深圳中原 (May-OK)
 */

Class ZhongyuanRent extends \city\PublicClass{

    Public function house_page(){
        $minPage=empty($minPage)?1:$minPage;
        $maxPage=empty($maxPage)?994:$maxPage;
        $urlarr = [];
        for ($page=$minPage; $page<$maxPage; $page++){
            $urlarr[] = "http://api.sz.centanet.com/0755/api/Post?pagecount=".$page."0&startindex=0&posttype=r";
        }
        return $urlarr;
    }
    
    public function house_list($url){
//        $url = 'http://api.sz.centanet.com/0755/api/Post?pagecount=1300&startindex=0&posttype=r';
        $snoopy=$this->getUrlContent($url);
        $list_decode=json_decode($snoopy,1);
        $house_info = [];
        foreach($list_decode['Result'] as $v){
            $house_info[] = "http://sz.centanet.com/zufang/".$v['Id'].".html";;
        }
        return $house_info;
    }
    
    public function house_detail($source_url){
        preg_match('/zufang\/([\x{0000}-\x{ffff}]*?)\./u',$source_url,$value);
        $value = $value[1];
        $result_temp=json_decode($this->getUrlContent("http://api.sz.centanet.com/0755/api/Post?PostId=".$value."&isDetail=true"),1);
        $result_image_array=json_decode($this->getUrlContent("http://api.sz.centanet.com/0755/api/PostImg?postId=".$value),1);
        $result_image=$result_image_array['Result'];
        $result=$result_temp['Result'];
        $result_agen=json_decode($this->getUrlContent("http://api.sz.centanet.com/0755/api/Staff?staffNo=".$result['StaffNo']."&postId=".$value),1);

        //从web补充建筑年代数据、小区、城区
        $result_html=$this->getUrlContent($source_url);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url);
        preg_match("/<div\sclass=\"roombase\-info([\x{0000}-\x{ffff}]*?)<div\sclass=\"roombase\-people\">/u",$result_html,$nav_con1);
        $info = strip_tags($nav_con1[0]);
        //dump($info);
        preg_match("/装修：([\x{0000}-\x{ffff}]*?)小区名称/u",$info,$house_fitmet);
        preg_match("/(\d{4})年/u",$info,$year);
        $house_built_year= $year[1];
        //标题
        $house_info['house_title']=trimall(HTMLSpecialChars($result['Title']));
        //价格
        $house_info['house_price']=$result['RentalPrice'];
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
        $house_info['house_floor']=str_replace('层',"",$result['FloorDisplay']);
        //总楼层
        $house_info['house_topfloor']=$result['FloorTotal'];
        //装修情况
        $house_info['house_fitment']=$result['Fitment'];
        //房源描述

        $house_info['house_desc']=trimall(HTMLSpecialChars($result['PlainDescription']));
        //房屋类型
        $house_info['house_type']=$result['PostList']['PropertyType'];
        //建成年代
        $house_info['house_built_year']=trimall($house_built_year);
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
        //		$house_info['fuwu_shq'] = $result_agen['Result']['DepartmentName'];
        //经纪人公司
        $house_info['is_contrast']=2;
        $house_info['company_name']='中原';

        $house_info['house_pic_unit']=array();
        $house_info['house_pic_layout']=array();
        //dump($result_image);die;
        foreach($result_image as $imgV){
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
        $house_info['source']=2;
        $house_info['is_fill'] = 2;
        $house_info['created']=time();
        $house_info['updated']=time();
        $house_info['house_relet']='';
        $house_info['house_style']='';
        return $house_info;
    }

    //下架判断
    public function is_off($url){
        //暂时没有下架标识
        return 2;
        $newurl = get_jump_url($url);
        if($newurl == $url){//没有跳转
            $html = $this->getUrlContent($url);
            //暂未找到下架页面
            if(preg_match("/remove_over\s*state_bg/", $html)){
                return 1;
            }elseif(preg_match("/class=\"errortag\"/", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}