<?php namespace shanghai;
/**
 * @description 上海房天下 整租房抓取规则
 * @classname 上海房天下
 */

Class FangRent extends \city\PublicClass
{
    Public function house_page(){

        $dis = array(
            18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,35,586,996,1046);
        //每个城区列表页的最大页码
        $pagelist = array(
            100,100,100,100,100,100,100,100,100,100,100,100,100,100,89,35,100,6,24);
        //0-
        $urlarr = [];
        foreach($dis as $key=>$index){
            $URLPRE = "http://zu.sh.fang.com/house-a0".$index."/a21-n31";
            for($page=1; $page<=$pagelist[$key]; $page++){
                $urlarr [] = ($page == 1) ? $URLPRE.'/' : $URLPRE.'-i3'.$page.'/';
            }
        }
        return $urlarr;
    }
	/*
	 * 获取列表页
	*/
	public function house_list($url){
		$html = gb2312_to_utf8(getSnoopy($url));
    	preg_match("/<dl\s*class=\"list[\x{0000}-\x{ffff}]+?<\/ul>/u", $html, $houses);
        $house_info = array();
        $house_floor = array();
        $house_topfloor = array();
    	preg_match_all("/chuzu\/[_\-\w\.]*?htm\">/",$houses[0], $source);
        preg_match_all("/info\srel([\x{0000}-\x{ffff}]+?)<\/dd>/u", $html, $floors);
        foreach($floors[1] as $f){
            preg_match("/(\d+)\/(\d+)层/u", $f, $split_floor);
            $house_floor [] = $split_floor[1];
            $house_topfloor [] = $split_floor[2];
        }
        foreach($source[0] as $k=>$source_url){
            $source_url = explode('">',$source_url);
            $house_info[$k] = "http://zu.sh.fang.com/".$source_url[0].'|'.$house_floor[$k].'|'.$house_topfloor[$k];
        }
        return $house_info;
	}
	
	/*
	 * 获取详情
	 */
    public function house_detail($source_url){
        //详情页拿信息
//        var_dump($source_url);
        $split = explode('|',$source_url);
//        var_dump($split);exit;
        $source_url = $split[0];
        $html = gb2312_to_utf8(getSnoopy($source_url));
//        var_dump($html);
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);
        //过滤经纪人房源
        if(preg_match('/comName:\s*\'-1\'/u',$html)){
            //详细信息
            //标题
            preg_match("/h1-tit\srel[\x{0000}-\x{ffff}]+?<\/h1>/u", $html, $title);
            $house_title = explode('>',trimall(strip_tags($title[0])));
            $house_info['house_title'] =$house_title[1] ;
            preg_match("/house-info\">([\x{0000}-\x{ffff}]+?)<\/div>/u", $html, $info);
            //图片列表
            preg_match("/<div\s*class=\"slider\"[\x{0000}-\x{ffff}]+?<\/div>/u", $html, $pics);
//		    $info = strip_tags($info[0]);
            $info = trimall($info[0]);
//		    var_dump($info);
            preg_match("/[\x{0000}-\x{ffff}]*?小区/u", $info, $p);
            preg_match("/小区[\x{0000}-\x{ffff}]*?交通/u", $info, $b);
            preg_match("/房屋[\x{0000}-\x{ffff}]*?配套/u", $info, $a);
            //var houseInfo对象元素抓取
            preg_match("/配套[\x{0000}-\x{ffff}]+/u", $info, $config);
            //面积
            preg_match("/buildingArea\:\s\'(\d+\.?\d*)\'/u", $html, $totalarea);
            $house_info['house_totalarea'] = $totalarea[1];
            //小区名称和编号
            preg_match('/projname\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $bor);
            $house_info['borough_name'] = $bor[1];
            preg_match('/houseid\:\s\'(\d+)\'/u', $html,$borough_id);
            $house_info['borough_id'] = $borough_id[1];
            //房屋类型
            preg_match('/purpose\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $house_type);
            $house_info['house_type'] = $house_type[1];
            //价格
            preg_match("/price\:\s\'(\d+)\'/u", $html, $price);
            $house_info['house_price'] = $price[1];
            //城区商圈
            preg_match('/district\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $cityarea);
            preg_match('/comarea\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $cityarea_2);
            $house_info['cityarea_id'] = $cityarea[1];
            $house_info['cityarea2_id'] = $cityarea_2[1];
            //owner名称电话
            preg_match('/agentName\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $owner);
            preg_match('/agentMobile\:\s\'([\x{0000}-\x{ffff}]*?)\'/u', $html, $phone);
            $house_info['owner_name'] = $owner[1];
            $house_info['owner_phone'] = str_replace('-', '', str_replace("转", ",", $phone[1]));
            //楼层及总楼层
            preg_match("/(\d+)\/(\d+)/", $info, $floor);
            $house_info['house_floor'] = $floor[1];
            $house_info['house_topfloor'] = $floor[2];
            if(empty($floor)){   //无法从详情页抓取楼层信息时，从列表页抓取
                $house_info['house_floor'] = $split[1];
                $house_info['house_topfloor'] = $split[2];
            }
            //朝向
            preg_match('/(东西|南北|东北|东南|西北|西南)/u',$info,$toward);
            if(empty($toward[1])){
                preg_match('/(东|北|南|西)/u',$info,$toward);
            }
            $house_info['house_toward'] = $toward[1];

            //室厅卫厨
            preg_match("/(\d+)室/", $info, $r);
            $house_info['house_room']=empty($r)?0:$r[1];
            preg_match("/(\d+)厅/", $info, $h);
            $house_info['house_hall']=empty($h)?0:$h[1];
            preg_match("/(\d+)卫/", $info, $t);
            $house_info['house_toilet']=empty($t)?0:$t[1];
            preg_match("/(\d+)厨/", $info, $kitchen);
            $house_info['house_kitchen']=empty($kitchen)?0:$kitchen[1];
            //装修
            preg_match('/(毛坯|简装修|精装修|豪华装修)/u',$info,$fitment);
            $house_info['house_fitment'] = $fitment[1];

            if(preg_match("/暂无资料/", $config[0])){
                $house_info['house_configroom'] = '';
            }else{
                $c = str_replace("配套设施:", "", $config[0]);
                $c = explode(',', $c);
                $house_info['house_configroom'] = implode('#', $c);
            }
            $house_info['house_configpub'] = '';
            //图片
            preg_match("/fy-img[\x{0000}-\x{ffff}]*?<\/div>/u",$html, $pics);
            preg_match_all("/src=\"(\S+?)\"/u", $pics[0], $pictures);
            $house_info['house_pic_unit'] = array();
            foreach($pictures[1] as $k=>$v){
                $house_info['house_pic_unit'][] = $v;
            }
            $house_info['house_pic_unit'] = array_unique($house_info['house_pic_unit']);
            $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
            $house_info['house_pic_layout'] = '';

            //标题中有性别的信息，暂时未做处理
            $house_info['sex'] = '';

            //<div class="Introduce floatr"
            preg_match("/<div\s*class=\"Introduce([\x{0000}-\x{ffff}]*?)<\/div>/u", $html, $desc);
            $desc = strip_tags($desc[0]);
            $desc = str_replace(array("\t", "\n", " ",'&nbsp;','&nbsp；'), "", $desc);
            $house_info['house_desc'] = str_replace('联系我时，请说是在房天下上看到的，谢谢！','',trimall($desc));

            $house_info['into_house'] = '';

            $pay_type = explode('[', $p[0]);
            $pay_type = explode(']', $pay_type[1]);
            $house_info['pay_type'] = trimall($pay_type[0]);
            $house_info['pay_method'] = '';

            $house_info['tag'] = '';
            $house_info['comment'] = '';
            $house_info['house_number'] = '';

            $house_info['deposit'] = '';
            $house_info['is_ture'] = '';

            $house_info['created'] = time();
            $house_info['updated'] = time();

            $house_info['house_relet'] = 2;
            $house_info['wap_url'] = '';
            $house_info['app_url'] = '';
            $house_info['is_contrast'] = 2;
            $house_info['is_fill'] = 2;
            $house_info['source_owner'] = 3;
            $house_info['chain_url'] = '';
        }else{
            unset($house_info);
        }
        return $house_info;
    }
	
	private $distinct = array(
	 	'18'=>'闵行', 
	    '19'=>'徐汇', 
	    '20'=>'长宁',
	    '21'=>'静安',
	    '22'=>'卢湾',
	    '23'=>'虹口',
	    '24'=>'黄浦',
	    '25'=>'浦东',
	    '26'=>'杨浦',
	    '27'=>'闸北', 
	    '28'=>'普陀', 
	    '29'=>'嘉定',
	    '30'=>'宝山',
		'31'=>'青浦',
	    '32'=>'奉贤',
	    '35'=>'金山',
	    '586'=>'松江', 
	    '996'=>'崇明',
	    '1046'=>'上海周边'
 	);	

	
	/*
	 * 经纪人手机号抓取更新   lzc
	 */
	public function borker(){
	    	
	    $house_rent_gov=M('house_rent_gov');
	    $house_rent_info=M('house_rent_info');
	    $count=$house_rent_gov->where("source=10 AND source_owner = 3 AND is_contrast != 2 AND (owner_phone = '' or owner_phone is null )")->count();
	    $times=$count/100;
	    for($i=0;$i<$times;$i++){
	        $num = ($count - ($i+1)*100) >= 0 ? 100 : ($count - $i*100);
	        $list=$house_rent_gov->where("source=10 AND source_owner = 3 AND is_contrast != 2 AND (owner_phone = '' or owner_phone is null )")->order(id)->limit($num)->getField('id,source_url');
	        foreach ($list as $k=>$v){
	            $url=$v;
	            $div=gb2312_to_utf8(getSnoopy($url));
	            preg_match("/<dt\s*id=\"esfbjxq_201\"([\x{0000}-\x{ffff}]*?)访问网上店铺/u",$div,$sj);
	            preg_match("/href=\"([\x{0000}-\x{ffff}]*?)\"\s*target/u",$sj[0],$sj1);
	            	
	            $div1=gb2312_to_utf8(getSnoopy($sj1[1]));
	            preg_match("/<title>([\x{0000}-\x{ffff}]*?)<\/title>/u",$div1,$sj2);
	            preg_match("/\d+/u",$sj2[1],$phone);
	            $th['owner_phone']=$phone[0];
	            if($th['owner_phone']){
	                $where['id']=$k;
	                $where1['gov_id']=$k;
	                $house_rent_gov->where($where)->save($th);
	                //  				echo $house_rent_gov->getLastSql();
	                $house_rent_info->where($where1)->save($th);
	                //  				echo $house_rent_info->getLastSql();
	                Log::write('gov_id = '.$k.'的房源 owner_phone 改为 '.$th['owner_phone'],'INFO');
	            }else{
	                //  				    file_put_contents("./fang.txt", $k.",", FILE_APPEND);
	                Log::write( 'id=['.$k.'] url='.$url.' 页面中没有电话号码','INFO');
	            }
	        }
	    }
	}
    public function is_off($url,$html){
        if(!empty($url)){
            if(empty($html)){
                $html = gb2312_to_utf8(getSnoopy($url));
            }
            $newurl = get_jump_url($url);
            if($newurl == $url){
                if(preg_match("/searchNoInfo/", $html)){
                    return 1;
                }elseif(preg_match("/sellAll/", $html)){
                    return 1;
                }elseif(preg_match("/ico-wrong/",$html)){
                    return 1;
                }else{
                    return 2;
                }
            }else{
                return 1;
            }
        }
    }
}