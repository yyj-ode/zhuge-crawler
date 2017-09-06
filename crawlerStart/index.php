<?php
include_once '../common/common.php';
//$a = getCrawlerSource();
//foreach((array)$a as $key => $value){
//    echo '\''.$value.'\' => \'\','.'<br>';
//}
//die;

/**
 * Created by PhpStorm.
 * User: tony
 * Date: 16/3/11
 * Time: 下午5:11
 */
class index{
    private $reids;
    private $crawlerSeed = 'crawler-seed-list';
    private $servertype = ['Grab-server', 'Crawling-server', 'Etl-server'];

    public function __construct() {
        $this->reids = getRedisInit();
    }

    public function index($city = ''){
        //获取服务器
        $serverips = $this->getServerIps();
        $citys = getCitys();
        $i = 0;
        foreach((array)$citys as $key => $cityname){
            if(!empty($city)){
                if($city != $cityname){
                    break;
                }
            }
            $path = '../seedrules/city/'.$cityname.'/';
            $files = getFiles($path);
            foreach((array)$files as $k => $v){
                if($this->isExistsStr($v, '.class.php')){
                    if (str_replace('.class.php', '', $v) != 'PublicClass') {
                        $path = '../seedrules/city/' . $cityname . '/' . $v;
                        $content = file_get_contents($path);
                        $source[$i] = $this->getApiName($content);
                        $sourcename = str_replace('.class.php', '', $v);
                        $source[$i]['source'] = $cityname.'-'.$sourcename;
                        $nums = $this->getSourceNum($cityname.'/'.$sourcename);
                        $source[$i]['num'] = $nums['num'];
                        $source[$i]['seed'] = $nums['seed'];
                        $source[$i]['illega'] = $nums['illeganum'];
                        $source[$i]['etlnum'] = $nums['etlnum'];
                        $source[$i]['count'] = $nums['count'];
                    }
                }
                $i++;
            }
        }
//        $housenums = $this->house_count();
        $source = $this->seetingSourceList($source);
        include_once './view/index.php';
    }

    /**
     * 获取服务器
     */
    private function getServerIps(){
        foreach((array)$this->servertype as $key => $value){
            $data[$value] = $this->reids->hkeys($value);
        }
        return $data;
    }

    private function getApiName($code) {
        $data = explode('/**', $code);
        $temp = explode('*/', $data[1]);
        return $this->analysisDesc($temp[0]);
    }

    private function analysisDesc($str){
        $arr = explode("\n", $str);
        $d = array();
        foreach ((array) $arr as $key => $value) {
            if ($this->isExistsStr($value, '@description')) {
                $d['description'] = trim(str_replace('* @description', '', $value));
            }
            if ($this->isExistsStr($value, '@classname')) {
                $d['classname'] = trim(str_replace('* @classname', '', $value));
            }
        }
        return $d;
    }

    public function getSourceNum($source = ''){
        if(!empty($source)){
            $source = str_replace('-', '/', $source);
            $num = $this->reids->get($source);
            $illeganum = $this->reids->get($source.'illega');
            $seednum = $this->reids->get($source.'seed');
            $etlnum = $this->reids->get($source.'etlnum');
            $count = $this->reids->get($source.'count');
            if($num == ''){$num = 0;}
            if($illeganum == ''){$illeganum = 0;}
            if($seednum == ''){$seednum = 0;}
            if($etlnum == ''){$etlnum = 0;}
            if($count == ''){$count = 0;}
            return ['num' => $num, 'illeganum' => $illeganum, 'seed' => $seednum, 'etlnum' => $etlnum, 'count' => $count];
        }
        return ['num' => 0, 'illeganum' => 0, 'seed' => 0];
    }

    private function isExistsStr($str, $search){
        $temp = str_replace($search, '', $str);
        return $temp != $str;
    }

    private function getAllSource(){
        $citys = getCitys();
        $i = 0;
        $source = [];
        foreach((array)$citys as $key => $cityname){
            $path = '../seedrules/city/'.$cityname.'/';
            $files = getFiles($path);
            foreach((array)$files as $k => $v){
                if($this->isExistsStr($v, '.class.php')){
                    if (str_replace('.class.php', '', $v) != 'PublicClass') {
                        $sourcename = str_replace('.class.php', '', $v);
                        $source[] = $cityname.'-'.$sourcename;
                    }
                }
                $i++;
            }
        }
        return $source;
    }

    public function getSourceInfo(){
        $allsource = $this->getAllSource();
        $data = ['status' => true];
        foreach((array)$allsource as $key => $value){
            $data[$value] = $this->getSourceNum($value);
        }
        return $data;
    }

    public function settingSourceOrder($source = false){
        if($source){
            $psource = str_replace('-', '/', $source);
            $rsource = '"'.str_replace('-', '\\/', $source).'"';
            $this->reids->lRem($this->crawlerSeed, $rsource);
            $this->reids->push($this->crawlerSeed, $psource);
        }
        return false;
    }

    public function seetingSourceList($source = false){
        if($source){
            $dsource = $this->getListSource();
            $data = [];
            foreach((array)$dsource as $k => $v){
                foreach ((array)$source as $key => $value) {
                    if ($value['source'] == $v) {
                        $data[] = $source[$key];
                        unset($source[$key]);
                    }
                }
            }
            return array_merge($data, $source);
        }
        return false;
    }

    public function getListSource(){
        $data = [];
        $length = $this->reids->Llen($this->crawlerSeed);
        for($i = 0; $i < $length; $i++) {
            $dsource[] = str_replace(['/', '\\', '"'], ['-', '', ''], $this->reids->LINDEX($this->crawlerSeed, $i));

        }
        for($i = count($dsource)-1; $i > 0; $i--){
            $data[] = $dsource[$i];
        }
        return $data;
    }

    //统计官网数据
    public function house_count(){
        $num = [];
        $url = 'http://www.ziroom.com/z/nl/z1.html';
        $html = get_curl_post_data($url, []);
        preg_match('/id=\"page\">([\x{0000}-\x{ffff}]*?)<\/div>/u',$html,$div);
        preg_match('/共(\d+)页/',$div[1],$total);
        $total = trimall(strip_tags($total[1]));
        $num['beijing-ZiroomRent'] = $total * 10;

        $url = 'http://www.ziroom.com/z/nl/z2.html';
        $html = get_curl_post_data($url, []);
        preg_match('/id=\"page\">([\x{0000}-\x{ffff}]*?)<\/div>/u',$html,$div);
        preg_match('/共(\d+)页/',$div[1],$total);
        $total = trimall(strip_tags($total[1]));
        $num['beijing-ZiroomHezu'] = $total * 20;

        $List = array(
            'beijing-Wiwj'=>array(
                'url'=>'http://bj.5i5j.com/exchange/',
                'query'=>'.font-houseNum'
            ),
            'beijing-WiwjHezu'=>array(
                'url'=>'http://bj.5i5j.com/rent/w2',
                'query'=>'.font-houseNum'
            ),
            'beijing-Wiwjrent'=>array(
                'url'=>'http://bj.5i5j.com/rent/w1',
                'query'=>'.font-houseNum'
            ),
            'beijing-Dingdinghezu'=>array(
                'url'=>'http://bj.zufangzi.com/area/0-20000000000/',
                'query'=>'span.pull-right > span:nth-child(1)'
            ),
            'beijing-DingdingRent'=>array(
                'url'=>'http://bj.zufangzi.com/area/0-10000000000/',
                'query'=>'span.pull-right > span:nth-child(1)'
            ),
            'beijing-Fang'=>array(
            ),
            'beijing-FangHezu'=>array(
            ),
            'beijing-FangRent'=>array(
            ),
            'beijing-FangzhuHezu'=>array(
                'url'=>'http://bj.fangzhur.com/hezu/',
                'query'=>'.result > span:nth-child(1)'
            ),
            'beijing-FangzhuRent'=>array(
                'url'=>'http://bj.fangzhur.com/rent/',
                'query'=>'.result > span:nth-child(1)'
            ),
            'beijing-Five8PersonalRent'=>array(
            ),
            'beijing-Iwjw'=>array(
                'url'=>'http://www.iwjw.com/sale/beijing/',
                'query'=>'#Order > dt:nth-child(1) > span:nth-child(1)'
            ),
            'beijing-Kufang'=>array(
                'url'=>'http://beijing.koofang.com/sale/',
                'query'=>'.tongji > span:nth-child(2)'
            ),
            'beijing-KufangHezu'=>array(
                'url'=>'http://beijing.koofang.com/rent/c1/t8/',
                'query'=>'.tongji > span:nth-child(2)'
            ),
            'beijing-Landzestate'=>array(
                'url'=>'http://www.landzestate.com/bj/xiaoshou',
                'query'=>'span.fwb'
            ),
            'beijing-LandzestateRent'=>array(
                'url'=>'http://www.landzestate.com/bj/rent',
                'query'=>'span.fwb'
            ),
            'beijing-Lianjia'=>array(
                'url'=>'http://bj.lianjia.com/ershoufang/',
                'query'=>'.secondcon > ul:nth-child(1) > li:nth-child(3) > span:nth-child(2) > strong:nth-child(1) > a:nth-child(1)'
            ),
            'beijing-LianjiaRent'=>array(
                'url'=>'http://bj.lianjia.com/zufang/',
                'query'=>'.list-head > h2:nth-child(1) > span:nth-child(1)'
            ),
            'beijing-Mai'=>array(
                'url'=>'http://maitian.cn/esfall',
                'query'=>'.screening > p:nth-child(3) > span:nth-child(1)'
            ),
            'beijing-MaiRent'=>array(
                'url'=>'http://www.maitian.cn/zfall',
                'query'=>'.screening > p:nth-child(3) > span:nth-child(1)'
            ),
            'beijing-Qfang'=>array(
                'url'=>'http://beijing.qfang.com/sale',
                'query'=>'.dib'
            ),
            'beijing-QfangHezu'=>array(
                'url'=>'http://beijing.qfang.com/rent/h2',
                'query'=>'.dib'
            ),
            'beijing-QfangRent'=>array(
                'url'=>'http://beijing.qfang.com/rent/h1',
                'query'=>'.dib'
            ),
            'beijing-Zhongyuan'=>array(
                'url'=>'http://bj.centanet.com/ershoufang/',
                'query'=>'.pagerTxt > span:nth-child(1) > span:nth-child(1) > em:nth-child(1)'
            ),
            'beijing-ZhongyuanRent'=>array(
                'url'=>'http://bj.centanet.com/zufang/',
                'query'=>'.pagerTxt > span:nth-child(1) > span:nth-child(1) > em:nth-child(1)'
            ),
        );
        foreach ($List as $key=>$value){
            if(empty($value)){
                $num[$key] = '无';
                continue;
            }
            $num[$key] = $this->queryList($value['url'], [
                'total' => [$value['query'],'text'],
            ])[0]['total'];
        }
        return $num;
        // 	    return 0;
    }


    private function queryList($url = '', $rules = []){
        if(!empty($url) && !empty($rules) && is_array($rules)){
            $data = \QL\QueryList::Query($url,$rules)->data;
            return $data;
        }
        return false;
    }
}
$index = new index();
$type = $_GET['type'];
unset($_GET['type']);
$cityname = '';
foreach((array)$_GET as $key => $value){
    $cityname = $key;
}
if(!empty($type) && $type == 'getAllSource'){
    die(json_encode($index->getSourceInfo()));
}elseif($type == 'ding' || $type == 'newding'){
    if($type == 'newding'){ //重新抓取
        $redis = getRedisInit();
        $source = str_replace('-', '/', $_GET['source']);
        $daykey = md5($source).'_page_url';
        
        $redis->delete($daykey);
        $redis->delete($source.'seed');
        $redis->delete($source.'_count');
        $redis->delete($source);
                
        $redis->set($source.$_SERVER['config']['sourecall'], 'exists');
    }
    $index->settingSourceOrder($_GET['source']);
    header("location: ./");
}
$index->index($cityname);