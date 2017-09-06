<?php namespace haerbin;

class Jiaoyang extends \city\PublicClass{

    //http://101.200.1.99:1992/api/query/sell/search
    private $listUrl = "http://101.200.1.99:1992/api/query/sell/search";
    //http://www.55555558.com/Panorama/getTourXml.ashx?hid=feddd0dc-ebb9-4c6e-9378-9215d058e9ca
    private $contentUrl = "http://www.55555558.com/Panorama/getTourXml.ashx";
    //http://www.55555558.com/Panorama/pano.aspx?hid=efb6c7b0-dc29-48f2-a008-7b73372a111d
    private $vrUrl = "http://www.55555558.com/Panorama/pano.aspx?hid=";
    private $detailUrl="http://55555558.com/ershoufang/";
    private $maxContent = 100;
    private $maxPage = 375*3 ;
    public function house_page()
    {

        for($i = 1; $i <= $this->maxPage; $i++){
            $resultData[] = "pageSize=".$this->maxContent."&pageIndex=".$i;
        }
        return $resultData;
    }
    public function house_list($url)
    {
        sleep(1);
        $ch = curl_init();
        $params = $url;

        curl_setopt($ch, CURLOPT_URL, $this->listUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_HEADER, 0); // 不要http header 加快效率
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        curl_setopt($ch, CURLOPT_POST, 1);    // post 提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $output = curl_exec($ch);

        if(preg_match('/^\xEF\xBB\xBF/',$output))
        {
            $output = substr($output,3);
        }
        $output1 = json_decode($output,true);

        if(!empty($output1)){
            foreach($output1['resData']['content'] as $key1=>$value1){
                $source_url=$this->detailUrl.$value1['hrid'];
                $array_list_content = array();
                $array_list_content['borough_name']=$value1['propertyName'];
                $array_list_content['cityarea_id']=$value1['cityName'];
                $array_list_content['title']=$value1['comment'];
                $array_list_content['house_number']=$value1['hrno'];
                $array_list_content['house_id']=$value1['hrid'];
                $array_list_content['lat']=$value1['latitude'];
                $array_list_content['lng']=$value1['longitude'];
                $array_list_content['created']=strtotime($value1['recordTime']);

                $array_list_content_tag=array();
                foreach($value1['characteristic'] as $tag_key=>$tag_value){
                    $array_list_content_tag[]= $tag_value['value'];
                }
                $array_list_content['house_tag']=implode($array_list_content_tag,'|');

                setListFields($source_url,$array_list_content);
                $house_info[]=$source_url;
            }
                }
        curl_close($ch);
        return $house_info;
    }
    public function house_detail($source_url)
    {

        $arr_list_conteont=getListFields($source_url);
        $rules = array('text' => array('.info_ a p','text'));
        $house_info['house_title']=$arr_list_conteont['title'];
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
            return $text;
        }));
        $house_info['house_number'] = \QL\QueryList::Query($source_url,$rules)->data[0]['text'][1];

        $house_info['borough_name'] = $arr_list_conteont['borough_name'];
        $house_info['cityarea_id'] = $arr_list_conteont['cityarea_id'];
        //没有商圈,城区代替商圈
        $house_info['cityarea2_id'] = $arr_list_conteont['cityarea_id'];
        $house_info['vr_url'] = $this->vrUrl.$arr_list_conteont['house_id'];
        $house_info['lat'] = $arr_list_conteont['lat'];
        $house_info['lng'] = $arr_list_conteont['lng'];
        $house_info['house_number'] = $arr_list_conteont['house_number'];
        $house_info['created'] = $arr_list_conteont['created'];

        $house_info['source'] = '62';
        $house_info['source_owner'] = '0';
        $house_info['source_name'] = '骄阳地产';
        $house_info['source_url'] = $source_url;
        $house_info['is_checked'] = 3;

        return $house_info;


    }
    public function callNewData(){
        //http://55555558.com/Handler/GetErShouFangList.ashx?TagType=zuixinfang&Qy=&Sj=&Mj=&Fx=&PageIndex=3&PageSize=30
    }
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