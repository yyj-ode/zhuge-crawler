<?php namespace haerbin;

class Jiaoyang extends \city\PublicClass{
    public function house_page()
    {




        /* //获取最大页数
        $url = 'http://55555558.com/ershoufang';
        $rules = array('text' => array('ul.jPag-pages li:last','text'));
        $page = \QL\QueryList::Query($url,$rules)->data[0]['text'];
        var_dump($page);die; */

    	//循环列表页
        $link = [];
        for($Page = 1; $Page <= 3755; $Page++){
            $link[] = 'http://55555558.com/Handler/GetErShouFangList.ashx?TagType=ershoufang&Qy=&Sj=&Mj=&Fx=&PageIndex='.$Page.'&PageSize=30';
        }
        return $link;
    }
    public function house_list($url)
    {

        // foreach ( $post_data as $k => $v ) 
        // { 
        //     $o.= "$k=" . urlencode( $v ). "&" ;
        // }
        // $post_data = substr($o,0,-1);
        // // var_dump($post_data);die;
        
        // $url = "";
        // $data = [
        //         'PageIndex' => 3738,
        //         'PageSize' => 30
        //         ];
        // $reuslt =  $this->request_post($url, $data);
        // var_dump($result);die;
        // return $result;
    }
    public function house_detail($source_url)
    {
        $html = file_get_contents($source_url);

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
        $house_info['broker_name'] = $name;
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
            return $text;
        }));
        $house_info['house_number'] = \QL\QueryList::Query($source_url,$rules)->data[0]['text'][1];
        $house_info['status'] = 1;
        $house_info['source'] = '骄阳地产';
        $house_info['source_owner'] = '骄阳地产';
        $house_info['source_name'] = '骄阳地产';
        $house_info['source_url'] = $source_url;
        $house_info['is_checked'] = 3;

        return $house_info;

        // preg_match('/人：<\/dt>[^\/]+/',$html,$broker);
        // preg_match('/<dd>([^<]+)/',$broker[0],$info);
        // $house_info['owner_name'] = $info[1];

        // $rules = array('text' => array('.blues b','text'));
        // $house_info['house_price'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $rules = array('text' => array('.cr_left dd:frist','text','',function($total){
        //     preg_match('/\((\d+)/',$total,$str);
        //     return $str[1];
        // }));
        // $house_info['house_unitprice'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];

        // $rules = array('text' => array('.des p','text'));
        // $house_info['house_desc'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $rules = array('text' => array('.house-title','text'));
        // $house_info['house_title'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $rules = array('text' => array('.position a:eq(2)','text','',function($total){
        //     $str = str_replace('二手房','',$total);
        //     return $str;
        // }));
        // $house_info['cityarea_id'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $rules = array('text' => array('.position a:eq(3)','text','',function($total){
        //     $str = str_replace('二手房','',$total);
        //     return $str;
        // }));
        // $house_info['cityarea2_id'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        
        // $rules = array('text' => array('.cr_left dl:eq(5) dd','text','',function($total){
        //     preg_match('/(\S+)\/\D+(\d+)/',$total,$info);
        //     return $info;
        // }));
        // $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $house_info['house_floor'] = $info[1];
        // $house_info['house_topfloor'] = $info[2];
        // $house_info['house_toward'] = '';
        // $rules = array('text' => array('#content .cr_left dl:eq(1) dd','text','',function($total){
        //     preg_match('/(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+/',$total,$data);
        //     return $data;
        // }));
        // $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $house_info['house_room'] = $info[1];
        // $house_info['house_hall'] = $info[2];
        // $house_info['house_toilet'] = $info[3];
        // $house_info['house_kitchen'] = '';
        // $house_info['house_fitment'] = '';
        // $house_info['house_feature'] = '';
        // $house_info['house_built_year'] = '';
        // $house_info['house_totalarea'] = $info[4];
        // $rules = array('text' => array('.telephone','text','',function($data){
        //     $data = preg_replace('/\s+/','',$data);
        //     return $data;
        // }));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['owner_phone'] = $info;
        $house_info['service_phone'] = '';
        $house_info['house_support'] = '';

        $rules = array('text' => array('.l_fy p','text','',function($data){
            $data = preg_replace('/[^@]+：/','', $data);
            return $data;
        }));
        $house_info['created'] = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['updated'] = date('Y-m-d',time());
        $house_info['status'] = 1;
        $house_info['source'] = '第一时间';
        $house_info['source_owner'] = '第一时间';
        $house_info['source_name'] = '第一时间';
        $house_info['source_url'] = $source_url;
        $house_info['is_checked'] = 3;
        $house_info['click_num'] = 0;
        $house_info['refresh'] = 0;
        $rules = array('text' => array('#content .cr_left dl:eq(4) dd','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_type'] = $info;
        $rules = array('text' => array('#content .cr_left dl:eq(2) dd','text'));
        $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['borough_name'] = $info;
        $house_info['tag'] = '';
        return $house_info;
    }
    public function callNewData(){}
        /**
     * curl post请求
     * @param $url
     * @param array $data
     * @return bool|mixed
     */
    function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }
        
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        
        return $data;
    }
    function curlHttpPost($url,$data=array(), $jsonDecode=false){
        //对空格进行转义
        $url = str_replace(' ','+',$url);
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, "$url");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_TIMEOUT,3);  //定义超时3秒钟
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