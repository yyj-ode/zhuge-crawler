<?php namespace baserule;
/**
 * @description 房天下成交
 * @classname 房天下成交
 */

Class FangDeal extends \city\PublicClass{
    // 分页列表地址
	protected $housePageUrl = false;

    /**
     * 获取列表分页
     */
    public function house_page(){
        $queryData = $this -> getDis($this -> housePageUrl);
        $dis = $queryData['dis'];
        if( !$this -> housePageUrl || !$dis){
            return false;
        }

        $URL = $this -> housePageUrl;

        $urlarr=array();
        foreach ($dis as $value){
            $page = 1;

            // 类型：1住宅，2别墅
            $type = array(1,2);
            foreach($type as $v){
                $URLPRE = $URL.$value."__".$v."_0_0_0_0";
                $html = gb2312_to_utf8(getHtml($URLPRE.'/'));

                preg_match('/txt\">共(\d+?)页/u',$html,$page);
                $maxPage = $page[1];
                for ($page=1; $page<=$maxPage; $page++){
                    $urlarr[]=$URLPRE.$page."_0_0/";
                }
            }
        }
        return $urlarr;
    }

    /*
     * 获取列表页
    * */
    Public function house_list($url = ''){
        $html = gbk_to_utf8(getHtml($url));

        preg_match_all('/href="(.+)"\starget="_blank"\sclass="plotTit">/u', $html, $hrefs);
        $hrefs = array_unique($hrefs[1]);
        $hrefs = array_merge($hrefs);
        foreach($hrefs as $k=>$h){
            if(preg_match("/esf/u",$h)){
                $h = str_replace('esf','chengjiao',$h);
            }else{
                $h = $h."chengjiao/";
            }

            $house_info[] = $h;
        }
        return $house_info;
    }

    /*
     * 获取详情
     *
     * $house_info = [];
     * $house_info['borough_name'] = ''; //小区名
     * $house_info['finish_time'] = ''; //成交时间
     * $house_info['finish_price'] = ''; //成交价格
     * $house_info['house_area'] = ''; //房屋面积
     * $house_info['house_room'] = ''; //室\\居
     * $house_info['house_hall'] = ''; //厅
     * $house_info['house_toward'] = ''; //朝向
     * $house_info['house_floor'] = ''; //楼层
     * $house_info['house_topfloor'] = ''; //总楼层
     * $house_info['broker_name'] = ''; //经纪人姓名
     * $house_info['company_name'] = ''; //成交经纪公司名
     * $house_info['building_number'] = ''; //楼号
     */
    public function house_detail($source_url){
        $html = gbk_to_utf8(getHtml($source_url));
        $datas = $this -> queryDetailLi($html);

        // 没有成交数据
        if($datas['page'] < 1){
            return [];
        }

        $result = [];
        for($i=1; $i <= $datas['page']; $i++){
            if( $i == 1 ){
                $pageData =  $this -> house_detail_page('', $datas);
            }else{
                $detail_url = $source_url."-p1{$i}-t11/";
                $pageData = $this -> house_detail_page($detail_url);
            }

            $result = array_merge($result, $pageData);
        }

        return $result;
    }

    /**
     * @param $html
     * @return array
     */
    private function queryDetailLi($html){
        $datas = [];

        // 小区名称
        preg_match('/<a.+?id="xqw_B01_12".+?>(.*?)<\/a>/is', $html, $match);
        $datas['borough_name'] = rtrim($match[1], '小区网');

        // 成交信息
        preg_match('/<div\sclass="dealSent">(.*?)<\/table>/is', $html, $match);
        $datas['chengjia'] = $match[1];

        // 分页
        preg_match('/<span\sclass="red">(\d+)<\/span>条房源/u', $html, $match);
        $datas['page'] = ceil($match[1] / 30);

        return $datas;
    }


    public function house_detail_page($source_url = '', $dataLi= ''){
        if(!$dataLi){
            $html = gbk_to_utf8(getHtml($source_url));
            $dataLi = $this -> queryDetailLi($html);
        }

        preg_match_all('/(<tr>.*?<\/tr>)/is', $dataLi['chengjia'], $match);
        $borough_name = $dataLi['borough_name'];
        $queryDatas = [];
        foreach($match[1] as $data){
            $queryDatas = array_merge($queryDatas, $this -> queryData($data, ['borough_name' =>$borough_name ]));
        }

        return $queryDatas;
    }

    /**
     * 使用querylist获取小区数据
     * @param $html
     * @param array $datas
     * @return mixed
     */
    private function queryData($html, $datas=[]){
        $queryData = [];
        \QL\QueryList::Query($html, [
            'finish_time' => ['tr > td:eq(0)', 'text'],
            'finish_price' => ['tr > td:eq(1)', 'text'],
            'house_room_halt' => ['tr > td:eq(3)', 'text'],
            'house_area' => ['tr > td:eq(4)', 'text'],
            'floor' => ['tr > td:eq(5)', 'text'],
            'house_toward' => ['tr > td:eq(6)', 'text'],
            'broker_name' => ['tr > td:eq(5)', 'text'],
        ])->getData(function($data)use($datas, &$queryData){
            preg_match_all('/(\d+(\.\d+)?)/u', $data['finish_price'], $match);
            $finish_price = 0;
            if(isset($data['finish_price'])){
                preg_match_all('/(\d+(\.\d+)?)/u', $data['finish_price'], $match);
                $finish_price = $match[1][0];
            }

            $house_area = 0;
            if(isset($data['house_area'])){
                preg_match_all('/(\d+(\.\d+)?)/u', $data['house_area'], $match);
                $house_area = $match[1][0];
            }

            $house_room = $house_halt = 0;
            if(isset($data['house_room_halt'])){
                preg_match_all('/(\d)室(\d)厅/u', $data['house_room_halt'], $match);
                $house_room = $match[1][0];
                $house_halt = $match[2][0];
            }

            $house_floor = $house_topfloor = 0;
            if(isset($data['floor'])){
                $float = explode('/', $data['floor']);
                $house_floor = rtrim($float[0], '层');
                $house_topfloor = rtrim($float[1], '层');
            }

            $house_toward = '';
            if(isset($data['house_toward'])){
                $house_toward = trim($data['house_toward'], '--');
            }

            $queryData[] = [
                'finish_time' => getValue($data, 'finish_time'),
                'house_toward' => $house_toward,
                'finish_price' => $finish_price,
                'house_area' => $house_area,
                'house_room' => $house_room,
                'house_halt' => $house_halt,
                'house_floor' => $house_floor,
                'house_topfloor' => $house_topfloor,
                'broker_name' => getValue($data, 'broker_name'),
                'borough_name' => getValue($datas, 'borough_name'),
                'building_number' => '',
                'company_name' => '房天下',
            ];
        });

        return $queryData;
    }

    /**
     * 获取城区
     */
    protected function getDis($url){
        if(!$url) return  false;

        $html = getHtml( $url );

        $datas = [];
        \QL\QueryList::Query($html, [
            'dis' => ['.qxName > a', 'href'],
            'page' => ['.fy_text', 'text'],
        ])->getData(function($data)use(&$datas){
            if( isset($data['dis']) ) {
                preg_match('/\/housing\/(\d*)__/', $data['dis'], $match);
                $datas['dis'][] = $match[1];
            }

            if(isset($data['page'])){
                list( $page, $totalPage ) = explode('/', $data['page']);
                $datas['totalPage'] = $totalPage;
            }
        });

        $datas['dis'] = array_filter($datas['dis'], function($num){
            return is_numeric($num);
        });

        return $datas;
    }
}