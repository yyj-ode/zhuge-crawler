<?php namespace suzhou;
/**
 * Created by PhpStorm.
 * User: lihongdong
 * Date: 16/7/15
 * Time: 上午2:36
 */
class FangZiying  extends \city\PublicClass
{
    // http://esf.fang.com/chushou/14_714459.htm 下架
    protected $log = true; // 是否开启日志
    public  $host = 'http://esf.suzhou.fang.com';
    private $tag = [
        '满二',
        '满五唯一',
        '学区房',
        '电梯房',
        '地铁房',
        '地铁',
        '学区',
        '优质教育',
        '不限购'
    ];


    /*
     * 分城区抓取
     */
    public function house_page()
    {
        $queryData = $this -> getHtmlQueryData($this ->host.'/house/a21/');
//         var_dump($queryData);die;
        $urlarr=array();
        $error = $totalNum = [];
        foreach($queryData['dis'] as $dv){
            $disUrl = $this ->host .'/'. $dv .'/a21';
            // var_dump($disUrl);die;
            $queryData = $this -> getHtmlQueryData($disUrl.'/');
            // 内容为空
            if(!$queryData){
                $error['empty'][] = $disUrl;
                continue;
            }

            // 商圈
            if($queryData['totalnum'] > 3000 ){
                foreach($queryData['plate'] as $plate){
                    $plateQueryUrl =  $this ->host .'/'. $dv. '-' . $plate .'/a21';
                    $plateQuery = $this -> getHtmlQueryData($plateQueryUrl.'/');

                    // 内容为空
                    if(!$plateQuery){
                        $error['empty'][] = $plateQueryUrl;
                        continue;
                    }

                    // 面积
                    if($plateQuery['totalnum'] > 3000){
                        foreach($queryData['mianji'] as $mianji){
                            $mianjiUrl =  $this ->host .'/'. $dv. '-' . $plate ."/a21-{$mianji}";
                            $plateQuery = $this -> getHtmlQueryData($mianjiUrl.'/');

                            // 内容为空
                            if(!$plateQuery){
                                $error['empty'][] = $plateQueryUrl;
                                continue;
                            }

                            $page = ceil($plateQuery['totalnum'] / 30);
                            for($i=1; $i<=$page; $i++){
                                $urlarr[] = $mianjiUrl."-i3{$i}/";
                                // var_dump($urlarr);die;
                            }
                        }
                    }else{
                        $totalNum[] = $plateQuery['totalnum'];
                        $page = ceil($plateQuery['totalnum'] / 30);

                        for($i=1; $i<=$page; $i++){
                            $urlarr[] = $plateQueryUrl."-i3{$i}/";
                        }
                    }

                }
            }
            else{ // 城区
                $totalNum[] = $queryData['totalnum'];
                $page = ceil($queryData['totalnum'] / 30);
                for($i=1; $i<=$page; $i++){
                    $urlarr[] = $disUrl."-i3{$i}/";
                }
            }
        }
        // var_dump($urlarr);die;
        writeLog( 'Fang'.__FUNCTION__, ['error_url'=>$error, 'totalNum' => $totalNum], $this -> log);
        return $urlarr;
    }

    public function house_list($url)
    {
        $queryData = $this ->getHtmlQueryData($url);
        // 抓取空日志
        if(!$queryData['list']) writeLog( 'Fang_'.__FUNCTION__, ['url'=>$url, 'msg' => '内容为空'], $this -> log);
        return $queryData['list'];
    }

    public function house_detail($source_url)
    {
        $html = getHtml($source_url);
        if(!$html) writeLog( 'Fang_'.__FUNCTION__, ['url'=>$source_url, 'msg' => '内容为空'], $this -> log);

        $div = gbk_to_utf8($html);
        $house_info['content'] = $html;
        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$div);
        //标题
        preg_match("/\"title\">([\x{0000}-\x{ffff}]*?)<\/h1>/u", $div, $title);
        $house_info['house_title'] = trimall(strip_tags($title[1]));
        preg_match("/<div\s*class=\"inforTxt([\x{0000}-\x{ffff}]*?)看房/u", $div, $detail);
        $info = strip_tags($detail[1]);
        $info = str_replace(array("\t", "\n", "\r", " "),"", $info);
        $info = SBC_DBC($info);
        //价格
        preg_match("/(\d+\.?\d*)万/", $info, $price);
        preg_match("/第?(\d+|中|高|低)层/u", $info, $floor);
        preg_match("/共(\d+)层/", $info, $topfloor);
        preg_match("/面积:(\d+\.?\d*)/", $info, $totalarea);
        preg_match("/:([\d\-]+?)年/", $info, $year);
        $house_info['house_price'] = $price[1];
        $house_info['house_totalarea'] = $totalarea[1];
        $tempyear = explode("-",$year[0]);
        $house_info['house_built_year']=empty($year)?0:str_replace(array(":", "年"), "", $tempyear[0]);
        $house_info['house_floor'] = empty($floor)?0:$floor[1];
        $house_info['house_topfloor'] = empty($topfloor)?0:$topfloor[1];
        //室厅卫厨
        preg_match("/(\d+)室/", $info, $r);
        $house_info['house_room']=empty($r)?0:$r[1];
        preg_match("/(\d+)厅/", $info, $h);
        $house_info['house_hall']=empty($h)?0:$h[1];
        preg_match("/(\d+)卫/", $info, $t);
        $house_info['house_toilet']=empty($t)?0:$t[1];
        preg_match("/(\d+)厨/", $info, $kitchen);
        $house_info['house_kitchen']=empty($kitchen)?0:$kitchen[1];
        preg_match("/朝向:([\x{0000}-\x{ffff}]+)/u", $info, $toward1);
        if(!empty($toward1)){
            preg_match("/(东北|东南|西北|西南|南北)/u", $toward1[1], $toward2);
            if(empty($toward2)){
                preg_match("/(东|南|北|西)/u", $toward1[1], $toward2);
            }
            $house_info['house_toward'] = $toward2[0];
        }else{
            $house_info['house_toward'] = 0;
        }
        preg_match("/(简装|中装|精装|毛坯)/u", $info, $fitment);
        $house_info['house_fitment'] = $fitment[1];
        

        // 小区
        preg_match('/<a\sid="dsesfxq_B02_04".*?>(.*?)<\/a>/is', $div, $match);
        $house_info['borough_name'] = $match[1];

        // 城区
        preg_match('/<a\sid="dsesfxq_B02_05".*?>(.*?)<\/a>/is', $div, $match);
        $house_info['cityarea_id'] = $match[1];

        // 商圈
        preg_match('/<a\sid="dsesfxq_B02_06".*?>(.*?)<\/a>/is', $div, $match);
        $house_info['cityarea2_id'] = trimall($match[1]);

        preg_match("/\"fy-img\"([\x{0000}-\x{ffff}]*?)<\/div>/u", $div, $pictures);
        preg_match_all("/src=\"([\x{0000}-\x{ffff}]*?)\">/u",$pictures[1],$pics);

        $house_info['house_pic_layout'] = $pics[1][0];
        unset($pics[1][0]);
        $pics = array_merge($pics[1]);
        $house_info['house_pic_unit']= implode("|", $pics);

        // service_phone
        preg_match('/<span\sclass=\"tel\">.*?<\/span>/is',$div,$match);
        $servicePhoneStr = $this -> queryData($match[0], 'span');
        $servicePhoneStr = trimall(strip_tags($servicePhoneStr));
        $servicePhone = str_replace('转',',',str_replace('-','',$servicePhoneStr));
        $house_info['service_phone'] = $servicePhone;

        // owner_name
        preg_match("/<dl\sclass=\"ppInfor\">.*?<\/dl>/si", $div, $match);
        $house_info['owner_name'] = $this -> queryData($match[0], 'a > span');
		if($house_info['owner_name']){
			$house_info['service_phone']="";
		}
        // owner_phone
        $ownerUrl = $this -> queryData($match[0], 'a', 'href');
        if($ownerUrl){
            $brokUrl  = $this -> host . '/Agent/Agentnew/AloneService.aspx?managername=' . ltrim($ownerUrl, '/a/');
            $brokHmtl = gethtml($brokUrl);
            $house_info['owner_phone'] = $this -> queryData($brokHmtl, '.phonenum');
        }

        preg_match("/<div\s*class=\"txt\s*floatl\">([\x{0000}-\x{ffff}]*?)<\/p>/u", $div, $desc);
        $desc = strip_tags($desc[0]);
        $desc = str_replace(array("\t", "\n", " "), "", $desc);
        $house_info['house_desc'] = trimall(HTMLSpecialChars($desc));

        preg_match("/<dt\s*id=\"esfbjxq_201\"([\x{0000}-\x{ffff}]*?)访问网上店铺/u",$div,$sj);
        preg_match("/href=\"([\x{0000}-\x{ffff}]*?)\"\s*target/u",$sj[0],$sj1);
        $div1=gb2312_to_utf8(gethtml($sj1[1]));
        preg_match("/<title>([\x{0000}-\x{ffff}]*?)<\/title>/u",$div1,$sj2);
        preg_match("/\d+/u",$sj2[1],$phone);
        $house_info['source'] = $this->getSource();
        $house_info['created'] = time();
        $house_info['updated'] = time();

        $house_info['tag'] = $this->getTags($div);

        return $house_info;
    }

    /**
     * 抓取房源对应标签
     */
    public function getTags($html){
        preg_match_all("/<span\s*class=['\"]\w+\snote['\"]>(.*?)<\/span>/u",$html,$match);
        return implode("#",$match[1]);
    }

    /**
     * 获取最新的房源种子
     * @author robert
     * @return type
     */
    public function callNewData(){;
        $data = [];
        for($i = 1; $i <= 100; $i++){
            $data[] = $this -> host . "/house/a21-i3{$i}/";
        }
        return $data;
    }

    /**
     * querylist匹配数据
     * @param $url 访问地址
     * @return array
     */
    protected function getHtmlQueryData($url){
        $queryData = [];
        $html = gb2312_to_utf8(getHtml($url)) ;
//        var_dump($html);die;

        // 总数量
        preg_match("/<ul\sclass=\"last\">(.*?)<\/ul>/is", $html, $match);
        if($match[1]){
            preg_match("/<b>(.*?)<\/b>/u", $match[1], $match);
            $queryData['totalnum'] = $match[1];
        }

        //  城区
        preg_match("/<div\sclass=\"qxName\".*?>(.*?)<\/div>/is", $html, $match);
        if($match[1]){
            preg_match_all('/\/(house-[a-z0-9]+)\//',$match[1], $match);
            $queryData['dis'] = $match[1];
        }

        // 商圈
        preg_match("/<p\sid=\"shangQuancontain\".*?>(.*?)<\/p>/is", $html, $match);
        if($match[1]){
            preg_match_all('/\/house-.*(b\d+)/',$match[1], $match);
            $queryData['plate'] = $match[1];
        }

        // 面积
        preg_match("/<li\sid=\"list_D02_13\".*?>(.*?)<\/p>/is", $html, $match);
        if($match[1]){
            preg_match_all("/href='(.*?)'/", $match[1], $mianji);
            if(is_array($mianji[1])){
                foreach($mianji[1] as $mj){
                    $mianjiArr = [];
                    preg_match('/(j2\d+)/', $mj, $mjMatch);
                    if(isset($mjMatch[1])) $mianjiArr[] = $mjMatch[1];

                    preg_match('/(k2\d+)/', $mj, $mjMatch);
                    if(isset($mjMatch[1])) $mianjiArr[] = $mjMatch[1];
                    if( $mianjiArr ){
                        $queryData['mianji'][] = implode('-', $mianjiArr);
                    }
                }
            }
        }

        // 列表
        preg_match_all("/<dt\sclass=\"img\srel\sfloatl\">(.*?)<\/dt>/is", $html, $match);
        foreach($match[1] as $listHtml){
            preg_match('/...\shref="(.*?)"/', $listHtml, $listUrl);
            if($listUrl[1]){
                $queryData['list'][] =  $this -> host .$listUrl[1];
            }
        }

        return $queryData;
    }

    /**
     * querylist 获取部分数据
     */
    private function queryData($html, $preg, $type='text'){
        if(!$html || !$preg) return '';
        $result = '';
        \QL\QueryList::Query($html,
            ['data' => [$preg, $type, '', function($item)use(&$result){
                $result = $item;
            }]]
        );

        return $result;
    }
}