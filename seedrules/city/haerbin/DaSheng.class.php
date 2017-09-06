<?php namespace haerbin;
/**
 * @description 北京58同城业主个人整租抓取规则
 * @classname 北京58同城业主个人
 */
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","8000M");
ini_set('max_execution_time', '0');

Class DaSheng extends \city\PublicClass
{
    protected $log = false; // 是否开启日志
    // public $city_id = array("tab66" => '道里区', "tab67" => '道外区', "tab70" => '南岗区', "tab69" => '开发区',"74" => '香坊区','72' => '松北区','71' => '平房区','68' => '呼兰区');
    
  

    /**
     * 获取种子分页
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_page()
    {
            $PRE_URL = "http://dsfang.com.cn/search/result/pro21getlist?callback=jQuery17205595169476021549_1474621271809&p=&qy=&jg1=&jg2=&mj1=&mj2=&hx=&lc1=&lc2=&cq=&ll1=&ll2=&orderby=&_=1474622078505"; 
            $str1 = getSnoopy($PRE_URL);
            $index = stripos($str1,'(')+1;
            $str2 = rtrim(substr($str1,$index),')');
            $result = json_decode($str2, true);
            $maxPage = $result['p_max']-1;
            // var_dump($maxPage);die;
            for ($Page = 1; $Page <= $maxPage ; $Page++) {
                $urls[] = "http://dsfang.com.cn/search/result/pro21getlist?callback=jQuery17205595169476021549_1474621271809&p=".$Page."&qy=&sq=0&jg1=&jg2=&mj1=&mj2=&hx=&lc1=&lc2=&cq=&ll1=&ll2=&orderby=&_=1474622078505";
                
            }
        // }
        #$result = json_decode(getSnoopy($PRE_URL), true);
        
        if (!$urls)
            writeLog('DaSheng' . __FUNCTION__, ['url' => $urls], $this->log);
        return $urls;
    }


    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    Public function house_list($url = '')
    {
         $str1 = getSnoopy($url);
         $index = stripos($str1,'(')+1;
         $str2 = rtrim(substr($str1,$index),')');
         $house_info =array();
         $result = json_decode($str2, true);
         $arr = $result['rs'];
         foreach($arr as $k => $v){
            $id = $v[1];
            $house_info[] = "http://dsfang.com.cn/index/esf/info?id=".$id;
            
         }

         return $house_info;
    }  

    /**
     * 获取详情页信息
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function house_detail($source_url)
    {
        sleep(2);
        $html = file_get_contents($source_url);
        //title
        preg_match('/<div\s*class=\"tl-left\">\s*(.*?)\s*<\/div>/', $html,$house_title);
        $house_info['house_title'] = $house_title[1];
        //成交价
        preg_match('/<td>(.*?)万元<\/td>/',$html,$house_price);
        $house_info['source_name'] = "大盛地产";
        $house_info['source_url'] = $source_url;
        //经纪人
        preg_match('/div\s*class=\"p-01\">\s*(.*?)\s*<br>\s*(.*?)\s*<\/div>/',$html,$broker);
        $house_info['owner_name'] = $broker[1];
        $house_info['owner_phone'] = $broker[2];
        //标题
        preg_match('/<dt>小区名称<\/dt>\s*<dd>(.*?)<\/dd>/',$html,$borough_name);
        // var_dump($borough_name);die;
        // $house_info['house_title'] = $json2['title'];
        $house_info['borough_name'] = $borough_name[1];
        preg_match('/<dt>所在区域<\/dt>\s*<dd>(.*?)&nbsp;&nbsp;(.*?)<\/dd>/',$html,$area);
        $house_info['cityarea2_id'] = $area[2];
        $house_info['cityarea_id'] = $area[1];
        $house_info['house_price'] = $house_price[1];
        //总面积
        preg_match('/<td>(.*?)㎡<\/td>/',$html,$house_totalarea);
        $house_info['house_totalarea'] = $house_totalarea[1];
        //装修
        preg_match('/<td>(.*?)装<\/td>/',$html,$house_fitment);
        if(!$house_fitment){
            $house_info['house_fitment'] = '毛坯'; 
        }else{
           $house_info['house_fitment'] = $house_fitment[1].'装'; 
        }
        
        //室
        preg_match('/<td>(.*?)室(.*?)厅(.*?)卫<\/td>/',$html,$house_room);
        
        $house_info['house_room'] = $house_room[1];
        //厅
        $house_info['house_hall'] = $house_room[2];
        //卫
        $house_info['house_toilet'] = $house_room[3];
        //朝向
        preg_match('/<td>([东南北西]*)<\/td>/',$html,$house_toward);
        $house_info['house_toward'] = $house_toward[1];
        //楼层
        preg_match('/<td>第(.*?)层\/\(共(.*?)层\)<\/td>/',$html,$house_floor);
        $house_info['house_floor'] = $house_floor[1];
        $house_info['house_topfloor'] = $house_floor[2];
        //建造年份
        preg_match('/<td>(.*?)年<\/td>/',$html,$house_built_year);
        $house_info['house_built_year'] = $house_built_year[1];
       
        preg_match('/<dt>房源编号<\/dt>\s*<dd>(.*?)<\/dd>/',$html,$house_number);
        // var_dump($house_number);die;
        $house_info['house_number'] = $house_number[1];
        //房源图片
        preg_match_all('/<span\s*class=\"item\">\s*.*\s*<img\s*src=\"(.*?)\"\s*style=\"height:400px;\">/',$html,$house_pic_unit);
            $house_info['house_pic_unit'] = implode('|', $house_pic_unit[1]);
        // }
        if (!$house_info)
            writeLog('DaSheng' . __FUNCTION__, ['url' => $source_url], $this->log);
        return $house_info;

    }

    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    public function callNewData()
    {
        
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data [] = "http://dsfang.com.cn/search/result/pro21getlist?callback=jQuery17205595169476021549_1474621271809&p=".$i."&qy=&sq=0&jg1=&jg2=&mj1=&mj2=&hx=&lc1=&lc2=&cq=&ll1=&ll2=&orderby=&_=1474622078505";
        }
        if (!$data)
            writeLog('DaSheng' . __FUNCTION__, ['url' => $url], $this->log);
        return $data;
    }
}
?>