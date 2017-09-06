<?php namespace haerbin;

class Jiaoyang extends \city\PublicClass{
    private $maxPage = 3739;
    private $maxContent = 30;
    public function house_page()
    {
        for($i = 1; $i <= $this->maxPage; $i++){
            $resultData[] = 'http://55555558.com/Handler/GetErShouFangList.ashx?TagType=ershoufang%26Qy=%26Sj=%26Mj=%26Fx=%26PageIndex='.$i.'%26PageSize='.$this->maxContent;
        }
        return $resultData;
    }
    public function house_list($url)
    {
        $url = urldecode($url);
        usleep(5000);
        $headers[] = "Accept:*/*";
        $headers[] = "Accept-Language:zh-CN,zh;q=0.8";
        $headers[] = "Cache-Control:no-cache";
        $headers[] = "Connection:keep-alive";
        $headers[] = 'Content-Length:0';
        $headers[] = "Cookie:CNZZDATA953157=cnzz_eid%3D1311664265-1475508680-%26ntime%3D1475508680";
        $headers[] = "Host:55555558.com";
        $headers[] = "Origin:http://55555558.com";
        $headers[] = "Pragma:no-cache";
        $headers[] = "Referer:http://55555558.com/ershoufang";
        $headers[] = "User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36";
        $headers[] = "X-Requested-With:XMLHttpRequest";
        $html = $this->curlHttpPost($url, $postdata,$headers);
        $rules = array('href' => array('.House_list h2 a','href'));
        $data = \QL\QueryList::Query($html,$rules)->data;
        foreach ($data as $key => $value) {
            $urlarr[] = 'http://55555558.com/'.$value['href'];
        }
        return $urlarr;
    }
    public function house_detail($source_url)
    {
        usleep(5000);
        $rules = array('text' => array('.info_ a p','text'));
        $house_info['house_title'] = \QL\QueryList::Query($source_url,$rules)->data[0]['text'];
        //info_basic
        $rules = array('text' => array('#info_basic','text'));
        $basic_info = \QL\QueryList::Query($source_url,$rules)->data[0]['text'];

        preg_match('/价：(\d+)/',$basic_info,$base);
        $house_info['house_price'] = $base[1];
        preg_match('/\[(\d+)/',$basic_info,$base);
        $house_info['house_unitprice'] = $base[1];
        preg_match('/型：(\d+)\D+(\d+)\D+(\d+)\D+/',$basic_info,$base);
        $house_info['house_room'] = $base[1];
        $house_info['house_hall'] = $base[2];
        $house_info['house_toilet'] = $base[3];
        preg_match('/积：(\d+)/',$basic_info,$base);
        $house_info['house_totalarea'] = $base[1];
        preg_match('/向：(\S+)/',$basic_info,$base);
        $house_info['house_toward'] = $base[1];
        preg_match('/层：(\d+)/',$basic_info,$base);
        $house_info['house_floor'] = $base[1];
        preg_match('/层：[^\(]+\D+(\d+)/',$basic_info,$base);
        $house_info['house_topfloor'] = $base[1];
        preg_match('/修：(\S+)/',$basic_info,$base);
        $house_info['house_fitment'] = $base[1];
        preg_match('/类型：(\S+)/',$basic_info,$base);
        $house_info['house_type'] = $base[1];
        preg_match('/建筑年代：(\d+)/',$basic_info,$base);
        $house_info['house_built_year'] = $base[1];
        preg_match('/\d{11}/',$basic_info,$base);
        $house_info['owner_phone'] = $base[0];
        $rules = array('text' => array('.emp_name h6','text'));
        $name = \QL\QueryList::Query($source_url,$rules)->data[0]['text'];
        $house_info['owner_name'] = $name;
        $rules = array('src' => array('.piclist .ps_ .ps a img','src'));
        $img1 = \QL\QueryList::Query($source_url,$rules)->data;
        $img = [];
        foreach ($img1 as $key => $value) {
            $img[] = $value['src'];
        }
        $imgstr = implode('#',$img);
        $house_info['house_pic_unit'] = $imgstr;
        if ($imgstr == 'http://alimg.55555558.com/Upfiles/houseimage/error.jpg@800w_80Q_1x.jpg|watermark=1&object=d2F0ZXIucG5n&p=5&t=100') {
            $house_info['house_pic_unit'] = '';
        }
        $rules = array('text' => array('.mind_content','text','',function($total){
            preg_match('/为：(\w+)/',$total,$text);
            return $text[1];
        }));
        $house_info['house_number'] = \QL\QueryList::Query($source_url,$rules)->data[0]['text'];
        preg_match('/【(\S+)】/', $house_info['house_title'],$data);
        $house_info['borough_name'] = $data[1];


        // $house_info['cityarea_id'] = $arr_list_conteont['cityarea_id'];
        // //没有商圈,城区代替商圈
        // $house_info['cityarea2_id'] = $arr_list_conteont['cityarea_id'];
        // $house_info['vr_url'] = $this->vrUrl.$arr_list_conteont['house_id'];
        // $house_info['lat'] = $arr_list_conteont['lat'];
        // $house_info['lng'] = $arr_list_conteont['lng'];



        $house_info['created'] = time();
        $house_info['updated'] = time();
        $house_info['source_name'] = '骄阳地产';
        $house_info['source_url'] = $source_url;
        return $house_info;
    }
    public function callNewData(){
        //http://55555558.com/Handler/GetErShouFangList.ashx?TagType=zuixinfang&Qy=&Sj=&Mj=&Fx=&PageIndex=3&PageSize=30
    }
    function curlHttpPost($url,$data=[], $header=[], $jsonDecode=false){
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //curl_setopt($ch,CURLOPT_TIMEOUT,3);  //定义超时3秒钟
        // curl_setopt($ch, CURLOPT_REFERER, "http://xxx");
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //所需传的数组用http_bulid_query()函数处理一下，就ok了

        //执行并获取url地址的内容
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
}