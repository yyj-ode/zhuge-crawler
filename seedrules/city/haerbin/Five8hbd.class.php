<?php namespace haerbin;
/**
 * Created by PhpStorm.
 * User: hbd
 * Date: 2016/10/8
 * Time: 22:35
 */


class Five8hbd extends \city\PublicClass
{
    protected $log = false; // 是否开启日志
    public $city_name = 'hrb';
    public $dis = array(
        'nangang' => 59,
        'daoli' => 52,
        'daowai' => 50,
        'xiangfang' => 44,
        'hrbjiangbei' => 13,
        'hrbkaifaqu' => 8,
        'hebyilan' => 0,
        'hebfangz' => 0,
        'hebbinxian' => 0,
        'hebbayan' => 0,
        'hebmulan' => 0,
        'hebtonghe' => 0,
        'haerbin' => 8
    );
    /**
     * 获取种子分页
     * User: hbd
     * Date: 2016/10/8
     * Time: 22:35
     */
    public function house_page()
    {
        $urlarr = [];
        $urlstr = 'http://' . $this->city_name . '.58.com/{$cityarea}/ershoufang/0/';
        foreach ($this->dis as $k => $v) {
            //遍历城区
            $url = str_replace('{$cityarea}', $k, $urlstr);
            $html = file_get_contents($url);
            //找出商圈url
            $rules = array('href' => array('div.subarea a','href'));
            $data = \QL\QueryList::Query($url,$rules)->data;
            //遍历商圈循环页数
            foreach ($data as $key => $value) {
                $urlstr1 = 'http://' . $this->city_name . '.58.com' . $value['href'] . 'pn';
                for ($i=1; $i <= 70; $i++) { 
                    $urlarr[] = $urlstr1 . $i . '/?PGTID=0d30000c-0000-110b-940a-229a430108e3&ClickID=1';
                }
            }
        }
        return $urlarr;
    }

    /**
     * 获取详情页列表
     * User: zhangjg
     * Date: 2016/8/8
     * Time: 19:35
     */
    Public function house_list($url = '')
    {
        $rules = array(
            'text' => array('#infolist td.t > p.bthead > a','href'),
        );
        $data = \QL\QueryList::Query($url,$rules)->data;
        //循环列表页
        $link = [];
        foreach ($data as $key => $value) {
            //判断字符串长度是否大于40
            if (strlen($value['text'])>40) {
                $link[] = preg_replace('/\?[^\?]+/', '', $value['text']);
            }
        }
        return $link;
    }

    /**
     * 获取详情页信息
     * User: hbd
     * Date: 2016/10/9
     * Time: 1:41
     * $house_info['house_title']
     * $house_info['cityarea_id']
     * $house_info['cityarea2_id']
     * $house_info['borough_name']
     * $house_info['house_price']
     * $house_info['house_unitprice']
     * $house_info['house_room']
     * $house_info['house_hall']
     * $house_info['house_toilet']
     * $house_info['house_totalarea']
     * $house_info['house_floor']
     * $house_info['house_topfloor']
     * $house_info['house_toward']
     * $house_info['house_fitment']
     * $house_info['house_built_year']
     * $house_info['house_type']
     * $house_info['owner_name']
     * $house_info['owner_phone']
     * $house_info['house_desc']
     * $house_info['house_pic_unit']
     * $house_info['source_url']
     * $house_info['source_owner']
     * $house_info['house_number']
     */
    public function house_detail($source_url)
    {
        $html = file_get_contents($source_url);

        \QL\QueryList::Query($html, [
            'house_title' => ['.bigtitle', 'text', '', function ($data) use (&$house_info) {
                $data = preg_replace('/\s+/', '', $data);
                $house_info['house_title'] = $data;
            }],

            'baseinfo' => ['.suUl', 'text', '', function ($data) use (&$house_info) {
                preg_match('/\s+(\S+)-\s+(\S+)-\s+(\S+)/', $data,$info);
                $house_info['cityarea_id'] = $info[1];
                $house_info['cityarea2_id'] = $info[2];
                $house_info['borough_name'] = $info[3];

                preg_match('/(\d+)室/', $data,$info);
                $house_info['house_room'] = empty($info)?0:$info[1];
                preg_match('/(\d+)厅/', $data,$info);
                $house_info['house_hall'] = empty($info)?0:$info[1];
                preg_match('/(\d+)卫/', $data,$info);
                $house_info['house_toilet'] = empty($info)?0:$info[1];
                preg_match('/((\d+\.)?\d+)㎡/', $data,$info);
                $house_info['house_totalarea'] = empty($info)?0:$info[1];
            }],

            'house_price' => ['.suUl li:eq(0) .su_con span', 'text', '', function ($data) use (&$house_info) {
                $house_info['house_price'] = $data;
            }],

            'house_unitprice' => ['.suUl li:eq(0) .su_con', 'text', '-span', function ($data) use (&$house_info) {
                preg_match('/\d+/', $data,$info);
                $house_info['house_unitprice'] = $info[0];
            }],

            'owner_name' => ['.suUl .su_con > span > a[rel="nofollow"]', 'text', '', function ($data) use (&$house_info) {
                $house_info['owner_name'] = $data;
            }],

            'desinfo' => ['.des_table', 'text', '', function ($data) use (&$house_info) {
                preg_match('/住宅类别：\s+?(\S+)/', $data,$info);
                $house_info['house_type'] = rtrim($info[1],'暂无');
                preg_match('/装修程度：\s+?(\S+)/', $data,$info);
                $house_info['house_fitment'] = rtrim($info[1],'暂无');
                preg_match('/建造年代：\s+?(\d{4})/', $data,$info);
                $house_info['house_built_year'] = empty($info[1])?'':$info[1];
                preg_match('/朝向：\s+?(\S+)/', $data,$info);
                $house_info['house_toward'] = rtrim($info[1],'暂无');
            }],

            'floor' => ['.des_table>li:eq(2)>ul>li:eq(3)', 'text', '', function ($data) use (&$house_info) {
                preg_match('/([^\(]+)\D+(\d+)/', $data,$info);
                $house_info['house_floor'] = $info[1];
                if (!(preg_match('/层|地/', $house_info['house_floor']))) {
                    $house_info['house_floor'] = '';
                }
                $house_info['house_topfloor'] = $info[2];
            }],

            'house_pic_unit' => ['.descriptionImg img', 'src', '', function ($data) use (&$house_info) {
                $house_info['house_pic_unit'][] = $data;
            }],

            'house_desc' => ['.descriptionBox .description_con', 'text', '-.mb20', function ($data) use (&$house_info) {
                $info = preg_replace('/\s+/', '', $data);
                $house_info['house_desc'] = $info;
            }],

        ])->getData();
    
        //如果联系人为空则抓取第二套界面
        if (empty($house_info['owner_name'])) {
            $rules = array('text' => array('.jjreninfo_des_jjr span:eq(0)','text'));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['owner_name'] = $info;
        }

        //如果房源描述为空则抓取第二套方法
        if (empty($house_info['house_desc'])) {
            $rules = array('text' => array('.descriptionBox .description_con p:eq(1) span','text'));
            $info = \QL\QueryList::Query($html,$rules)->data[0]['text'];
            $house_info['house_desc'] = $info;
        }

        //电话号码是图片需要去手机端抓取
        preg_match('/(\d{4,})/', $source_url,$data);
        $phoneurl = 'http://m.58.com/hrb/ershoufang/'.$data[1].'x.shtml';
        $rules = array('text' => array('.contact li:eq(1)','text'));
        $info = \QL\QueryList::Query($phoneurl,$rules)->data[0]['text'];
        $house_info['owner_phone'] = $info;

        //固定字段
        $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
        $house_info['source_url'] = preg_replace('/\?[^\?]+/', '', $source_url);
        $house_info['source_owner'] = 5;

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
        for ($i = 0; $i <= 100; $i++) {
            $house_info[] = 'http://' . $this->city_name . '.58.com/ershoufang/0/pn' . $i . '/?PGTID=0d30000c-0000-110b-940a-229a430108e3&ClickID=1';
        }
        if (!$house_info)
            writeLog('Five8Personal' . __FUNCTION__, ['url' => $house_info], $this->log);
        return $house_info;

    }
}