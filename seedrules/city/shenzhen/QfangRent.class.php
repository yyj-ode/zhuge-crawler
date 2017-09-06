<?php namespace shenzhen;
/**
 * @description 深圳Q房 整租房抓取规则
 * @classname 深圳Q房 (May-OK)
 */
Class QfangRent extends \city\PublicClass{
    
    public function house_page(){
        $minPage=empty($minPage)?1:$minPage;
        $maxPage=empty($maxPage)?3025:$maxPage;
        $urlarr = [];
        for($page=$minPage; $page<=$maxPage; $page++){
            $urlarr[]="http://shenzhen.qfang.com/appapi/v3_4/room/list?bizType=RENT&dataSource=SHENZHEN&pageSize=20&currentPage=".$page;
        }
        return $urlarr;
    }

    /*
     * 获取列表页
     */
    public function house_list($url){
        $json=json_decode($this->getUrlContent($url),1);
        $result=$json['result'];
        $list=$result['list'];
        $house_info=array();
        foreach($list as $element){
            $id=$element['id'];
            //tag
            $tags = str_replace('|','#',$element['labelDesc']);
            $details = $this->getUrlContent("http://shenzhen.qfang.com/rent/".$id);
            //付款类型 从pc抓
            preg_match("/租赁类型\：[\x{0000}-\x{ffff}]*?<\/b>/u",$details,$pay);
            $payarr = explode('整租',$pay[0]);
            $pay_type = trimall($payarr[1]);
            //房屋公共配置
            preg_match("/<div\sclass\=\"house\_advantage\_item\">[\x{0000}-\x{ffff}]*?<\/div>/u",$details,$config);
            $configarr = explode("</span>",$config[0]);
            $configstr = trimall(strip_tags(implode("#",$configarr)));
            $house_info[] = $id.'|'."http://shenzhen.qfang.com/rent/".$id.'|'.$tags.'|'.$pay_type.'|'.$configstr.'|'."http://shenzhen.qfang.com/appapi/v3_4/room/detail?id=".$id."&qchatPersonId=83638900403346&dataSource=SHENZHEN&bizType=RENT&pageSize=20&which=0";
        }
        return $house_info;
    }

    /* 
     * 获取详情页
     * 
     *  */
    public function house_detail($source_url){
//        $source_url = '12520835|http://shenzhen.qfang.com/rent/12520835||||http://shenzhen.qfang.com/appapi/v3_4/room/detail?id=12520835&qchatPersonId=83638900403346&dataSource=SHENZHEN&bizType=RENT&pageSize=20&which=0';
        $house_info = [];
        $split = explode('|',$source_url);
        $house_info['house_number'] = $split[0];
        $house_info['source_url']= $split[1];
        $house_info['tag']= $split[2];
        $house_info['pay_type'] = $split[3];
        $house_info['house_configpub'] = $split[4];
        $house_info['app_url'] = $split[5];
        $source_url = $split[5];
        $json_2=json_decode($this->getUrlContent($source_url),1);
        $result_2=$json_2['result'];
        //下架检测
        $house_info['off_type'] = 2;
        if($json_2['status'] == 'E0502' || $json_2['message'] == '房源已下架或已删除'){
            $house_info['off_type'] = 1;
            return $house_info;
        }
        $house_info['house_title']=$result_2['title'];
        $house_info['house_price']=$result_2['price'];
        $pc_html=$this->getUrlContent($house_info['source_url']);
        $pattern = "/<span[\s]*class=\"fl\">([\d]*)年<\/span>/";
        preg_match_all($pattern, $pc_html,$out);
        $house_info['house_built_year']=$out[1][0];
        $house_info['house_totalarea']=$result_2['area'];
        $house_info['house_room']=$result_2['bedRoom'];
        $house_info['house_hall']=$result_2['livingRoom'];
        $house_info['house_toilet']=$result_2['bathRoom'];
        $house_info['house_fitment']=$result_2['decoration'];
        $house_info['house_desc']=trimall($result_2['description']);
        $house_info['house_toward']=$result_2['direction'];
        $house_info['house_floor']=$result_2['floor'];
        $house_info['house_topfloor']=$result_2['totalFloor'];
        $house_info['source']=5;
        //source_owner 区分业主来源  1,房主儿网 2，爱直租
        $house_info['source_owner'] = '';
        $broker=$result_2['broker'];
        $house_info['owner_name']=$broker['name'];
        $house_info['owner_phone']=$broker['phone'];
        $house_info['cityarea2_id']=$result_2['garden']['region']['name'];
        $house_info['borough_name']=$result_2['garden']['name'];
        $json_3=json_decode($this->getUrlContent("http://shenzhen.qfang.com/appapi/v3_4/garden/detail?qchatPersonId=83638900403346&dataSource=SHENZHEN&gardenId=".$result_2['garden']['id']),1);
        $house_info['cityarea_id']=$json_3['result']['region']['parent']['name'];
        $house_info['borough_id'] = '';
        $house_info['house_kitchen'] = '';
        $house_info['house_type'] = $result_2['roomType'];
        $house_info['house_relet'] = 2;//默认为非转租
        $house_info['house_style'] = '';//可有可无主次卧
        $house_info['sex'] = '';
        $house_info['into_house'] = '';
        $house_info['pay_method'] = '';
        $house_info['comment'] = '';
        $house_info['deposit'] = '';
        $house_info['house_configroom'] = '';
        $house_info['is_ture'] = '';
        $house_info['is_fill'] = 2;
        $house_info['created'] = time();
        $house_info['updated'] = time();
        $house_info['wap_url'] = '';
        $house_info['pub_time'] = '';
        $house_info['is_contrast'] = 2;
        $house_info['house_pic_layout']=str_replace('{size}','600x450',$result_2['layoutIndexPicture']);
        $pics=$result_2['roomPictures'];
        if(!empty($pics)){
            foreach($pics as $index=>$img){
                $img_url=str_replace('{size}','600x450',$img['url']);

                if($img_url!= $house_info['house_pic_out'])
                {
                    $house_info['house_pic_in_array'][]=$img_url;
                }
            }
        }
        $house_info['house_pic_in_array'] = array_unique($house_info['house_pic_in_array']);
        $house_info['house_pic_unit']=implode('|', $house_info['house_pic_in_array']);
        unset($house_info['house_pic_in_array']);
        return $house_info;
    }

    //下架判断
    public function is_off($url){
        $newurl = get_jump_url($url);
        if($newurl == $url){
            $html = $this->getUrlContent($url);
            //暂未找到下架页面
            if(preg_match("/remove_over\sstate_bg/", $html)){
                return 1;
            }elseif(preg_match("/(传送到首页|系统正在优化|erro500)/u", $html)){
                return 1;
            }else{
                return 2;
            }
        }else{
            return 1;
        }
    }
}