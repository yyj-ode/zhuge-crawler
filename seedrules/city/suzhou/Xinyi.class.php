<?php namespace suzhou;
/**
 * @description 苏州信义房屋 整个人房源抓取规则
 * @classname 苏州信义房屋
 */
class XinYi extends \city\PublicClass
{
    public $PRE_URL = 'http://www.sinyi.com.cn/Search.aspx?mz=0&cs=%E4%B8%8D%E9%99%90%2C%E4%B8%8D%E9%99%90%2C%E4%B8%8D%E9%99%90%2C%E4%B8%8D%E9%99%90%2C&sort=2';
    private $_log = false;
    public $maxPage = 199;
    /**
     * 获取页数
     * @param string $URL
     * @param $cli
     */
    public function house_page()
    {
        $page = [];
        $maxPage = $this->maxPage;
        for($i=1;$i<=$maxPage;$i++){
            $page[] = $i;
        }
        return $page;
    }

    public function curlHttpPost($page, $jsonDecode=false){
        $preURL = $this->PRE_URL;
//    设置post信息
        $data = [
            "__EVENTTARGET" => "AspNetPager1",
            "__EVENTARGUMENT" => $page,
        ];
//    设置头信息
        $headers[] = "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
        $headers[] = "Accept-Encoding:gzip, deflate";
        $headers[] = "Accept-Language:zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3";
        $headers[] = "Connection:keep-alive";
        $headers[] = "Cookie:pgv_pvi=320093184; CNZZDATA1000477127=2059062020-1476344333-%7C1476424974; pgv_si=s8210066432; pid=SAM10175510%2CSAM10175475%2CSAM10175518; CheckCode=204027; city=%E8%8B%8F%E5%B7%9E; code=002";
        $headers[] = "Host:www.sinyi.com.cn";
        $headers[] = "Referer:http://www.sinyi.com.cn/Search.aspx?mz=0&cs=%E4%B8%8D%E9%99%90%2C%E4%B8%8D%E9%99%90%2C%E4%B8%8D%E9%99%90%2C%E4%B8%8D%E9%99%90%2C&sort=2";
        $headers[] = "Upgrade-Insecure-Requests:1";
        $headers[] = "User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64; rv:49.0) Gecko/20100101 Firefox/49.0";
//    开始url对话
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$preURL);
        curl_setopt($ch,CURLOPT_TIMEOUT,8);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data));
        $output = curl_exec($ch);
        $errorCode = curl_errno($ch);
        //释放curl句柄
        curl_close($ch);
        if(0 !== $errorCode) {
            return false;
        }
        if($jsonDecode){
            $output = json_decode( $output, true );
        }
        return $output;
    }

    public function house_list($page)
    {
        $html = $this->curlHttpPost($page);
        $list = \QL\QueryList::Query($html,[
            'link' => ['a[class="inline red bold"]','href','',function($data){
            return $data;
            }],
        ])->getData(function($data){
            return $data;
        });
        $url=[];
        foreach($list as $v){
            $url[] = 'http://www.sinyi.com.cn/'.$v['link'];
        }
        return $url;
    }

    public function house_detail($source_url)
    {
        $i = 0;
        do{
            $i++;
            $html = getHtml($source_url);
            $house_info = \QL\QueryList::Query($html,[
                'house_title' => ['div[class="div320 line_h fle"]','html','',function($data){
                    $data = trimall($data);
                    $data = str_replace('<br>','#',$data);
                    $data = strip_tags($data);
                    $arr = explode('#',$data);
                    return $arr[0];
                }],
                'house_number' => [],
                'house_unitprice' => [],
                'house_price' => [],
                'cityarea_id' => [],
                'cityarea2_id' => [],
                'house_totalarea' => [],
                'house_room' => [],
                'house_hall' => [],
                'house_toilet' => [],
                'house_fitment' => [],
                'owner_name' => [],
                'owner_phone' => [],
                'source_url' => [],
                'source_name' => [],
                'house_pic_unit' => [],
                'house_desc' => [],
                'borough_name' => [],
            ])->getData(function($data){
                return $data;
            });

            preg_match("/物件编号：(.*)</",$html,$arr);
            if($arr){
                $house_info[0]['house_number'] = $arr[1];
            }
            preg_match("/单价：(.*)元/",$html,$arr);
            if($arr){
                $house_info[0]['house_unitprice'] = $arr[1];
            }
            preg_match("/价格：(.*)万/",$html,$arr);
            if($arr){
                $house_info[0]['house_price'] = $arr[1];
            }

            //网页纯文本信息
            $text = strip_tags($html);
            $text = trimall($text);
            preg_match('/区域(.*?)地址/',$text,$arr);
            if($arr){
                $house_info[0]['cityarea_id'] = $arr[1];
            }
            preg_match('/所属商圈(.*?)面积/',$text,$arr);
            if($arr){
                $house_info[0]['cityarea2_id'] = $arr[1];
            }
            preg_match('/面积(.*?)平米/',$text,$arr);
            if($arr){
                $house_info[0]['house_totalarea'] = $arr[1];
            }
            preg_match('/房型(\d+)房(\d+)厅(\d+)卫装修情况/',$text,$arr);
            if($arr){
                $house_info[0]['house_room'] = $arr[1];
                $house_info[0]['house_hall'] = $arr[2];
                $house_info[0]['house_toilet'] = $arr[3];
            }
            preg_match('/装修情况(.*)物业费/',$text,$arr);
            if($arr){
                $house_info[0]['house_fitment'] = $arr[1];
            }
            preg_match('/经纪人：(.*)联系电话/',$text,$arr);
            if($arr){
                $house_info[0]['owner_name'] = $arr[1];
            }
            preg_match('/联系电话：(\d+)/',$text,$arr);
            if($arr){
                $house_info[0]['owner_phone'] = $arr[1];
            }
            $house_info[0]['source_url'] = $source_url;
            $house_info[0]['source_name'] = '信义房屋';
            preg_match_all('/img\ssrc=\"(.*?)\"\swidth=\"500\"/',$html,$arr);
            $pics='';
            if($arr[1]){
                foreach($arr[1] as $pic){
                    $pics .= $pic.'|';
                }
                $house_info[0]['house_pic_unit'] = $pics;
            }
            preg_match('/(.*?)\d/',$house_info[0]['house_title'],$arr);
            if($arr){
                $house_info[0]['borough_name'] = $arr[1];
            }
            preg_match('/<!--房屋描述-->([\x{0000}-\x{ffff}]+?)<!--房源照片-->/u',$html,$arr);
            if($arr){
                $house_info[0]['house_desc'] = trimall(strip_tags($arr[1]));
            }
        }while(empty($house_info[0]['house_title']) && $i<3);
        return $house_info[0];
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @return type
     */
    public function callNewData(){
        $page = [];
        for($i=1;$i<=100;$i++){
            $page[] = $i;
        }
        return $page;
    }


}