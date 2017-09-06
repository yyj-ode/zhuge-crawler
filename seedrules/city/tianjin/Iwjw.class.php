<?php namespace tianjin;
header("Content-type: text/html; charset=utf-8");
ini_set("memory_limit","4000M");
ini_set('max_execution_time', '0');

class Iwjw extends \city\PublicClass{
    //设置初始属性
    public function house_page(){
        $urlarr = [];
        
        $dis = [
                'https://www.iwjw.com/sale/tianjin/g2id119635/',
                'https://www.iwjw.com/sale/tianjin/g2id71163/',
                'https://www.iwjw.com/sale/tianjin/g2id71165/',
                'https://www.iwjw.com/sale/tianjin/g2id71166/',
                'https://www.iwjw.com/sale/tianjin/g2id71167/',
                'https://www.iwjw.com/sale/tianjin/g2id71168/',
                'https://www.iwjw.com/sale/tianjin/g2id71169/',
                'https://www.iwjw.com/sale/tianjin/g2id105407/',
                'https://www.iwjw.com/sale/tianjin/g2id105410/',
                'https://www.iwjw.com/sale/tianjin/g2id105402/',
                'https://www.iwjw.com/sale/tianjin/g2id105406/',
                'https://www.iwjw.com/sale/tianjin/g2id71172/',
                'https://www.iwjw.com/sale/tianjin/g2id71175/',
                'https://www.iwjw.com/sale/tianjin/g2id71181/',
                'https://www.iwjw.com/sale/tianjin/g2id71180/',
                'https://www.iwjw.com/sale/tianjin/g2id71186/',
                'https://www.iwjw.com/sale/tianjin/g2id71177/',
                'https://www.iwjw.com/sale/tianjin/g2id71182/',
                'https://www.iwjw.com/sale/tianjin/g2id71176/',
                'https://www.iwjw.com/sale/tianjin/g2id71179/',
                'https://www.iwjw.com/sale/tianjin/g2id71183/',
                'https://www.iwjw.com/sale/tianjin/g2id105409/',
                'https://www.iwjw.com/sale/tianjin/g2id71173/',
                'https://www.iwjw.com/sale/tianjin/g2id71174/',
                'https://www.iwjw.com/sale/tianjin/g2id71188/',
                'https://www.iwjw.com/sale/tianjin/g2id71192/',
                'https://www.iwjw.com/sale/tianjin/g2id71223/',
                'https://www.iwjw.com/sale/tianjin/g2id71217/',
                'https://www.iwjw.com/sale/tianjin/g2id71189/',
                'https://www.iwjw.com/sale/tianjin/g2id71191/',
                'https://www.iwjw.com/sale/tianjin/g2id71222/',
                'https://www.iwjw.com/sale/tianjin/g2id71216/',
                'https://www.iwjw.com/sale/tianjin/g2id71218/',
                'https://www.iwjw.com/sale/tianjin/g2id71207/',
                'https://www.iwjw.com/sale/tianjin/g2id71208/',
                'https://www.iwjw.com/sale/tianjin/g2id71211/',
                'https://www.iwjw.com/sale/tianjin/g2id71214/',
                'https://www.iwjw.com/sale/tianjin/g2id71201/',
                'https://www.iwjw.com/sale/tianjin/g2id71212/',
                'https://www.iwjw.com/sale/tianjin/g2id71206/',
                'https://www.iwjw.com/sale/tianjin/g2id71210/',
                'https://www.iwjw.com/sale/tianjin/g2id71215/',
                'https://www.iwjw.com/sale/tianjin/g2id71198/',
                'https://www.iwjw.com/sale/tianjin/g2id71195/',
                'https://www.iwjw.com/sale/tianjin/g2id71200/',
                'https://www.iwjw.com/sale/tianjin/g2id71197/',
                'https://www.iwjw.com/sale/tianjin/g2id71194/',
                'https://www.iwjw.com/sale/tianjin/g2id71196/',
                'https://www.iwjw.com/sale/tianjin/g2id71202/',
                'https://www.iwjw.com/sale/tianjin/g2id119656/',
                'https://www.iwjw.com/sale/tianjin/g2id119657/',
                'https://www.iwjw.com/sale/tianjin/g2id71204/',
                'https://www.iwjw.com/sale/tianjin/g2id71239/',
                'https://www.iwjw.com/sale/tianjin/g2id71371/',
                'https://www.iwjw.com/sale/tianjin/g2id71228/',
                'https://www.iwjw.com/sale/tianjin/g2id71230/',
                'https://www.iwjw.com/sale/tianjin/g2id71231/',
                'https://www.iwjw.com/sale/tianjin/g2id71233/',
                'https://www.iwjw.com/sale/tianjin/g2id71235/',
                'https://www.iwjw.com/sale/tianjin/g2id71238/',
                'https://www.iwjw.com/sale/tianjin/g2id71240/',
                'https://www.iwjw.com/sale/tianjin/g2id71453/',
                'https://www.iwjw.com/sale/tianjin/g2id71236/',
                'https://www.iwjw.com/sale/tianjin/g2id71237/',
                'https://www.iwjw.com/sale/tianjin/g2id71377/',
                'https://www.iwjw.com/sale/tianjin/g2id71381/',
                'https://www.iwjw.com/sale/tianjin/g2id71241/',
                'https://www.iwjw.com/sale/tianjin/g2id71257/',
                'https://www.iwjw.com/sale/tianjin/g2id71254/',
                'https://www.iwjw.com/sale/tianjin/g2id71252/',
                'https://www.iwjw.com/sale/tianjin/g2id71256/',
                'https://www.iwjw.com/sale/tianjin/g2id71258/',
                'https://www.iwjw.com/sale/tianjin/g2id71242/',
                'https://www.iwjw.com/sale/tianjin/g2id71243/',
                'https://www.iwjw.com/sale/tianjin/g2id71379/',
                'https://www.iwjw.com/sale/tianjin/g2id71245/',
                'https://www.iwjw.com/sale/tianjin/g2id71246/',
                'https://www.iwjw.com/sale/tianjin/g2id71378/',
                'https://www.iwjw.com/sale/tianjin/g2id71253/',
                'https://www.iwjw.com/sale/tianjin/g2id71251/',
                'https://www.iwjw.com/sale/tianjin/g2id71247/',
                'https://www.iwjw.com/sale/tianjin/g2id71380/',
                'https://www.iwjw.com/sale/tianjin/g2id71382/',
                'https://www.iwjw.com/sale/tianjin/g2id71384/',
                'https://www.iwjw.com/sale/tianjin/g2id71383/',
                'https://www.iwjw.com/sale/tianjin/g2id105433/',
                'https://www.iwjw.com/sale/tianjin/g2id105450/',
                'https://www.iwjw.com/sale/tianjin/g2id105422/',
                'https://www.iwjw.com/sale/tianjin/g2id105431/',
                'https://www.iwjw.com/sale/tianjin/g2id105440/',
                'https://www.iwjw.com/sale/tianjin/g2id105437/',
                'https://www.iwjw.com/sale/tianjin/g2id105458/',
                'https://www.iwjw.com/sale/tianjin/g2id71387/',
                'https://www.iwjw.com/sale/tianjin/g2id105444/',
                'https://www.iwjw.com/sale/tianjin/g2id105423/',
                'https://www.iwjw.com/sale/tianjin/g2id105445/',
                'https://www.iwjw.com/sale/tianjin/g2id105419/',
                'https://www.iwjw.com/sale/tianjin/g2id105436/',
                'https://www.iwjw.com/sale/tianjin/g2id71388/',
                'https://www.iwjw.com/sale/tianjin/g2id71389/',
                'https://www.iwjw.com/sale/tianjin/g2id71178/',
                'https://www.iwjw.com/sale/tianjin/g2id71184/',
                'https://www.iwjw.com/sale/tianjin/g2id71185/',
                'https://www.iwjw.com/sale/tianjin/g2id71190/',
                'https://www.iwjw.com/sale/tianjin/g2id71193/',
                'https://www.iwjw.com/sale/tianjin/g2id71225/',
                'https://www.iwjw.com/sale/tianjin/g2id71219/',
                'https://www.iwjw.com/sale/tianjin/g2id105418/',
                'https://www.iwjw.com/sale/tianjin/g2id71220/',
                'https://www.iwjw.com/sale/tianjin/g2id71221/',
                'https://www.iwjw.com/sale/tianjin/g2id71226/',
                'https://www.iwjw.com/sale/tianjin/g2id71213/',
                'https://www.iwjw.com/sale/tianjin/g2id71209/',
                'https://www.iwjw.com/sale/tianjin/g2id71199/',
                'https://www.iwjw.com/sale/tianjin/g2id99831/',
                'https://www.iwjw.com/sale/tianjin/g2id71203/',
                'https://www.iwjw.com/sale/tianjin/g2id71205/',
                'https://www.iwjw.com/sale/tianjin/g2id71229/',
                'https://www.iwjw.com/sale/tianjin/g2id71369/',
                'https://www.iwjw.com/sale/tianjin/g2id71370/',
                'https://www.iwjw.com/sale/tianjin/g2id71368/',
                'https://www.iwjw.com/sale/tianjin/g2id71375/',
                'https://www.iwjw.com/sale/tianjin/g2id71376/',
                'https://www.iwjw.com/sale/tianjin/g2id71372/',
                'https://www.iwjw.com/sale/tianjin/g2id71374/',
                'https://www.iwjw.com/sale/tianjin/g2id71366/',
                'https://www.iwjw.com/sale/tianjin/g2id71255/',
                'https://www.iwjw.com/sale/tianjin/g2id105448/',
                'https://www.iwjw.com/sale/tianjin/g2id105432/',
                'https://www.iwjw.com/sale/tianjin/g2id105439/',
                'https://www.iwjw.com/sale/tianjin/g2id105453/',
                'https://www.iwjw.com/sale/tianjin/g2id105446/',
                'https://www.iwjw.com/sale/tianjin/g2id105455/',
                'https://www.iwjw.com/sale/tianjin/g2id105442/',
                'https://www.iwjw.com/sale/tianjin/g2id105451/',
                'https://www.iwjw.com/sale/tianjin/g2id105452/',
                'https://www.iwjw.com/sale/tianjin/g2id105454/',
                'https://www.iwjw.com/sale/tianjin/g2id105449/',
                'https://www.iwjw.com/sale/tianjin/g2id105456/',
                'https://www.iwjw.com/sale/tianjin/g2id105438/',
                'https://www.iwjw.com/sale/tianjin/g2id105441/',
                'https://www.iwjw.com/sale/tianjin/g2id105443/',
                'https://www.iwjw.com/sale/tianjin/g2id105424/',
                'https://www.iwjw.com/sale/tianjin/g2id105421/',
                'https://www.iwjw.com/sale/tianjin/g2id71385/'

            ];
            foreach ($dis as $k => $v) {
                for ($i=1; $i < 4; $i++) { 
                    for ($j=1; $j < 101; $j++) { 
                        $urlarr[] = rtrim($v,'/')."fl{$i}p{$j}";
                    }
                }
            }
        return $urlarr;
    }

    /*
     * 获取列表页
    */
    public function house_list($url)
    {
        sleep(mt_rand(2, 5));
        $house_info = [];
        $html = getHtml($url);
        $rules = array(
              'href' => array('.ol-border li a[class="house hPic"]','href'),
            );
        $data = \QL\QueryList::Query($html,$rules)->data;
        foreach ($data as $k => $v) {
            $house_info[] = 'https://www.iwjw.com'.$v['href'];
        }
        return $house_info;
    }

    /*
     * 获取详情
    */
    public function house_detail($source_url){
        sleep(mt_rand(2, 5));
        $html = getHtml($source_url);
        // $html = file_get_contents($source_url);
        // $html = getSnoopy($source_url);
        
        $rules = array('title' => array('.detail-title-h1','title'));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['title'];
        $house_info['borough_name'] = $data;
        $rules = array('text' => array('.detail-title-h1 + h2','text'));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        preg_match('/(\S+)\s+\-\s+(\S+)/', $data,$info);
        $house_info['cityarea_id'] = $info[1];
        $house_info['cityarea2_id'] = $info[2];
        $rules = array('text' => array('.g-fence span i','text'));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_price'] = $data;
        $rules = array('text' => array('.g-fence span:eq(1) i','text'));
        $data = \QL\QueryList::Query($html,$rules)->data;
        $house_info['house_room'] = $data[0]['text'];
        $house_info['house_hall'] = $data[1]['text'];
        $house_info['house_toilet'] = $data[2]['text'];
        $rules = array('text' => array('.g-fence span:eq(2) i','text'));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_totalarea'] = $data;
        $rules = array('text' => array('.list-infos div:eq(0) .left','text','-i',function($total){
                preg_match('/\d+\.\d+/', $total,$data);
                return $data[0];
        }));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_unitprice'] = $data;
        $rules = array('text' => array('.list-infos div:eq(2) .left','text','-i',function($total){
                preg_match('/(\S+)\s+\/\s+(\S+)/', $total,$data);
                return $data;
        }));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_floor'] = $data[1];
        $house_info['house_topfloor'] = str_replace('层', '', $data[2]);
        if (empty($house_info['house_topfloor'])) {
            $house_info['house_topfloor'] = '暂无信息';
        }
        if (empty($house_info['house_floor'])) {
            $rules = array('text' => array('.list-infos div:eq(2) .left','text','-i'));
            $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['house_floor'] = $data;
        }
        $rules = array('text' => array('.list-infos div:eq(2) .right','text','-i',function($total){
                preg_match('/\d+/', $total,$data);
                return $data[0];
        }));
        $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        $house_info['house_built_year'] = $data;
        preg_match('/朝向：<\/i>([^<]+)/', $html,$data);
        $house_info['house_toward'] = $data[1];
        preg_match('/装修：<\/i>([^<]+)/', $html,$data);
        $house_info['house_fitment'] = $data[1];
        if (empty($house_info['house_unitprice'])) {
            preg_match('/单价：<\/i>(\d+)/', $html,$data);
            $house_info['house_unitprice'] = $data[1];
        }
        // $rules = array('text' => array('.list-infos div:eq(3) .left','text','-i'));
        // $data = \QL\QueryList::Query($html,$rules)->data[0]['text'];
        // $house_info['house_toward'] = $data;
        // if (preg_match('/\d+/', $house_info['house_toward'])) {
        //     $house_info['house_toward'] = '暂无信息';
        // }


        $rules = array('src' => array('li.img-li > img:nth-child(1)', 'data-src', '', function($data)use(&$house_info){
                if(substr($data, 0, 4) == 'http'){
                    $house_info['house_pic_unit'][] = $data;
                }else{
                    $house_info['house_pic_unit'][] = 'http:'.$data;
                }
            }));
        \QL\QueryList::Query($html,$rules)->data;


        $house_info['house_title'] = $house_info['borough_name'].''.$house_info['house_room'].'室'.$house_info['house_hall'].'厅'.$house_info['house_toilet'].'卫 '.$house_info['house_totalarea'].' m²';
        $house_info['source_url'] = $source_url;
        $house_info['source_name'] = '爱屋吉屋';
        $house_info['created'] = time();
        $house_info['updated'] = time();
        $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
        //匹配video_url
        preg_match('/hd:\s+\"([^\"]+)/', $html,$video);
        $house_info['video_url'] = $video[1];
        return $house_info;
    }
    /**
     * 获取最新的房源种子
     * @param type $num 条数
     * @author vincent
     * @return type
     */
    public function callNewData($num = 100){
        //爱屋吉屋天津最新发布url
        //https://www.iwjw.com/sale/tianjin/o1/
        $num = 100;
        $url = 'https://www.iwjw.com/sale/tianjin/o1p{$page}/';
        $data = [];
        for($i = 1; $i <= $num; $i++){
            $data[] = str_replace('{$page}', $i, $url);
        }
        return $data;
    }
    //下架判断
    public function is_off($url,$html=''){
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getSnoopy($url);
            }
            //抓取下架标识
            $off_type = 1;
            preg_match('/已下架/', $html,$Tag);
            if(empty($Tag)){
                $off_type = 2;
            }
            return $off_type;
        }
        return -1;
    }
}