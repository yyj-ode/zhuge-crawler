<?php namespace guangzhou;
use Qiniu\json_decode;
/**
 * @description 广州爱屋吉屋二手房抓取规则
 * @classname 广州爱屋吉屋(k-ok)
 */


Class Iwjwoold extends \city\PublicClass  {

	public $URL = 'http://www.iwjw.com/sale/guangzhou/';
	public $PRE_URL = 'http://m.iwjw.com/getHouseList.action?ht=2&provinceId=40000&pageSize=100&page=1';
	//	private $current_url = '';
	private $tag = [
			'满二',
			'满五唯一',
			'学区房',
			'电梯房',
			'地铁房',
			'学区',
			];
	/*
	 * 抓取
	*/
	public function house_page()
	{
		$list =array(
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=40001&page=1",//11034
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=40002&page=1",//5132
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=40003&page=1",//7754
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=40004&page=1",//10836
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=40005&page=1",//7850
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=40006&page=1",//13546
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=61143&page=1",//6402
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=62757&page=1",//2035
				"http://m.iwjw.com/getHouseList.action?g=1&ht=2&provinceId=40000&pageSize=100&id=69715&page=1");//172
		//		return $this->callNewData();
//  		$result = json_decode($this->getUrlContent($this->PRE_URL),true);
		$urls = [];
		foreach ($list as $key=>$value){
			$result = json_decode(getHtml($value),true);
			$total = $result['totalNum'];
			$maxPage = floor($total/100);
			for($Page = 1; $Page <= $maxPage+1; $Page++){
				$urls[] = str_replace('page=1', "page=".$Page, $value);
			}
		}
		return $urls;
	}
	
	
	Public function house_page(){
		/*
		 * @author jsx
		* 爱屋及屋用搜索条件的方式获取页面列表6 4 8
		*/
		$dis=array(1=>'南山区',2=>'福田区',3=>'罗湖区',4=>'盐田区',5=>'宝安区',6=>'龙岗区',7=>'龙华区',8=>'光明新区',9=>'坪山新区',10=>'大鹏新区');
		$urlarr = [];
		foreach($dis as $d=>$index){
			for($a = 1; $a <= 6; $a ++) {
				for($r = 1; $r <= 4; $r ++) {
					for ($pr = 1; $pr <= 8; $pr++) {
						//搜索条件，从第一页拿到最大标签页
						$condition = "http://www.iwjw.com/sale/shenzhen/ip" . $pr . "ia" . $a . "rn" . $r . "/?kw=".urlencode($index);
						$html = $this->getUrlContent($condition);
						//判断是否有搜索结果
						preg_match("/<div\sclass=\"no\-content\">/u", $html, $content);
						//结果为空直接跳出本循环
						if ($content) {
							continue;
						}
						// 拿到检索的最大页面数目$pagecnt
						preg_match_all("/>(\d+?)<\/a>/", $html, $pagecnt);
						// $max表示当前条件下最大的页面数最大的页面数
						if (!empty ($pagecnt[1])) {
							$max = $pagecnt [1] [count($pagecnt [1]) - 1];
						} else {
							$max = 1;
						}
						unset ($html);
						for ($i = 1; $i <= $max; $i++) {
							$urlarr [] = "http://www.iwjw.com/sale/shenzhen/ip" . $pr . "ia" . $a . "rn" . $r . "p" . $i . "/?kw=" . urlencode ($index);
						}
					}
				}
			}
		}
		return $urlarr;
	}
	
	
	/*
	 * 获取列表页
	*/
	public function house_list($url = ''){
		$house_info = array();
		if (!isExistsStr($url,"m.iwjw.com")) {
			$house_info = \QL\QueryList::run('Request', [
					'target' => $url,
					])->setQuery([
							//获取单个房源url
							'link' => ['.ol-border > li> div:nth-child(2) > h4:nth-child(1) > b:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
								return $u;
							}],
							])->getData(function($item){
								return "http://www.iwjw.com".$item['link'];
							});
		} else {
			$url_lj = "http://www.iwjw.com/";
			$result = json_decode($this->getUrlContent($url),1);
			foreach ($result['houseList'] as $arr) {
				$house_info[] = $url_lj . $arr['houseDetailUrl'];
			}
		}
		return $house_info;
	}
	
	/*
	 * 获取详情
	*/
	public function house_detail($source_url = ''){
		$house_info = \QL\QueryList::run('Request',['target' => $source_url,])->setQuery(
				[
						'house_title' => ['.detail-title-h1', 'text', ''],
						'house_price' => ['p.g-fence:nth-child(1) > span:nth-child(1) > i:nth-child(1)', 'text', '', function($price){
							return $price;
						}],
						'cityarea_id' => ['a.mod-detail-nav-a:nth-child(3)', 'text', '', function($cityarea_id){
							return str_replace('二手房','',$cityarea_id);
						}],
	
						'cityarea2_id' => ['a.mod-detail-nav-a:nth-child(4)', 'text', '', function($cityarea2_id){
							return str_replace('二手房','',$cityarea2_id);
						}],
	
						'house_totalarea' => ['span.thin:nth-child(3) > i:nth-child(1)', 'text', '', function($house_totalarea){
							return str_replace('平米', '', $house_totalarea);
						}],
	
						'house_room' => ['span.thin:nth-child(2) > i:nth-child(1)', 'text', '', function($house_room){
							// 					preg_match("/(\d+)室/", $house_room, $hr);
							return $house_room;
						}],
	
						'house_hall' => ['span.thin:nth-child(2) > i:nth-child(2)', 'text', '', function($house_hall){
							return $house_hall;
						}],
						'house_toilet' => ['span.thin:nth-child(2) > i:nth-child(3)', 'text', '', function($house_toilet){
							return $house_toilet;
						}],
	
						'house_toward' => ['div.item-infos:nth-child(4) > p:nth-child(1)', 'text', '-i',function($house_toward){
							return $house_toward;
						}],
						'house_floor' => ['div.item-infos:nth-child(3) > p:nth-child(1)', 'text', '-i', function($house_floor){
							$temp_topfloor1 = explode("/",$house_floor);
							return str_replace('层', '', $temp_topfloor1[0]);
						}],
	
						'house_topfloor' => ['div.item-infos:nth-child(3) > p:nth-child(1)', 'text', '-i', function($house_topfloor){
							$temp_topfloor2 = explode("/",$house_topfloor);
							return trimall(str_replace('层', '', $temp_topfloor2[1]));
						}],
	
						'house_pic_unit' => ['li.img-li > img:nth-child(1)', 'data-src', '', function($house_pic_unit){
							return $house_pic_unit;
						}],
	
						//本渠道没有房源户型图
						'house_pic_layout' => [],
	
						'house_fitment' => ['div.item-infos:nth-child(5) > p:nth-child(1)','text','-i'],
						'borough_name' => ['a.mod-detail-nav-a:nth-child(5)', 'text', '', function($borough_name){
							return str_replace('二手房','',$borough_name);
						}],
						//				'house_desc' => ['div.noData:nth-child(2)','text',''],
	
						'house_built_year' => ['div.infos-mods:nth-child(2) > p:nth-child(3) > span:nth-child(1)','text','-i',function($house_built_year){
							return str_replace('年', '', $house_built_year);
						}],
	
						'house_style' => [],
						'tag' => ['div.list-infos > div.item-state.item-infos', 'text', '',function($tag){
							$tags = [];
							$tag = trimall($tag);
							foreach((array)$this->tag as $val){
								if(isExistsStr($tag, $val)){
									$tags[] = $val;
								}
							}
							return implode('#', $tags);
						}],
						])->getData(function($data) use($source_url)
						{
							$data['company_name'] = '爱屋吉屋';
							$data['source'] = '6';
							return $data;
						}
						);
						foreach((array)$house_info as $key => $value){
							if(isset($house_info[$key]['house_pic_unit'])){
								if(!strstr($house_info[$key]['house_pic_unit'],'http:')){
									$house_pic_unit[] = "http:".$house_info[$key]['house_pic_unit'];
								}else{
									$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
								}
							}
						}
						if(strstr($house_pic_unit[0],"layout")){
							$house_info[0]['house_pic_layout'] = $house_pic_unit[0];
							unset($house_pic_unit[0]);
						}
						$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
						
						$html = file_get_contents($source_url);
						preg_match("/朝向：([\x{0000}-\x{ffff}]*?)<\/p>/u", $html, $toward);
						preg_match("/装修：([\x{0000}-\x{ffff}]*?)<\/p>/u", $html, $fit);
						if(strstr($toward[1],"—")){
							$toward[1]="";
						}else{
							$toward[1] = str_replace("</i>","",$toward[1]);
						}
						if(strstr($fit[1],"—")){
							$fit[1]="";
						}else{
							$fit[1]=str_replace("</i>","",$fit[1]);
						}
						$house_info[0]['house_toward'] = $toward[1];
						$house_info[0]['house_fitment'] = $fit[1];
						$house_info[0]['content']=$this->getUrlContent($source_url);
						
						return $house_info[0];
	
	}
	
	//统计官网数据
	public function house_count(){
		$PRE_URL = 'http://www.iwjw.com/sale/guangzhou/';
		$totalNum = $this->queryList($PRE_URL, [
				'total' => ['#Order > dt:nth-child(1) > span:nth-child(1)','text'],
				]);
		return $totalNum;
	}
	//下架判断
	public function is_off($url,$html=''){
		if(!empty($url)){
			if(empty($html)){
				$html = $this->getUrlContent($url);
			}
			//抓取下架标识
			$off_type = 1;
			$newurl = get_jump_url($url);
			$oldurl = str_replace('shtml','html',$url);
			if($newurl == $oldurl){
				$Tag = \QL\QueryList::Query($html,[
						"isOff" => ['a.sellBtnView1:nth-child(3)','text','',function($text) {
							if (preg_match('/已售出/',$text)){
								return $off_type = 1;
							}
						}],
						"404" => ['.img-404','class',''],
						])->getData(function($item){
							return $item;
						});
						if(empty($Tag)){
							$off_type = 2;
							return $off_type;
						}
			}
			return $off_type;
		}
		return -1;
	}
	
	/* 获取最新的房源种子
	 * @author robert
	* @return type
	*/
	public function callNewData(){
		$resultData = [];
		for($i = 1; $i <= 100; $i++){
			$resultData[] = "http://www.iwjw.com/sale/guangzhou/o1p{$i}/";
		}
		return $resultData;
	}
	
}