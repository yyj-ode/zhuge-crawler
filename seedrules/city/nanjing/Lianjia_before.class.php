<?php
namespace nanjing;
/**
 * @description 南京链家二手房
 * @classname 南京链家(k-ok)
 */

class Lianjia extends \city\PublicClass{
    public $PRE_URL = 'http://nj.lianjia.com/ershoufang/';
    private $current_url = '';

	/**
	 * 获取列表分页
	 */
	public function house_page(){
	    //区域
	    $dis = array(
	        'gulou' => '鼓楼',
	        'jianye' => '建邺',
	        'qinhuai' => '秦淮',
	        'xuanwu' => '玄武',
	        'yuhuatai' => '雨花台',
	        'qixia' => '栖霞',
	        'jiangning' => '江宁',
	        'pukou' => '浦口',
	        'liuhe' => '六合',
	        'lishui' => '溧水',
	        'gaochun' => '高淳',
	    );
	    //价格
	    $p = array(
	        'p1' => '80万以下',
	        'p2' => '80-100万',
	        'p3' => '100-120万',
	        'p4' => '120-150万',
	        'p5' => '150-200万',
	        'p6' => '200-250万',
	        'p7' => '250-300万',
	        'p8' => '300万以上',
	    );
        $urlarr = [];
	    foreach ($dis as $k1 => $v1){
	        foreach ($p as $k2 => $v2){
	            $this->current_url = $this->PRE_URL.$k1.'/'.$k2;
	            $url = \QL\QueryList::run('Request', [
	                'target' => $this->current_url,
	            ])->setQuery([
	                'link' => ['.total > span:nth-child(1)','text', '', function($total){
	                    $maxPage = ceil($total/30);
                        $link = [];
	                    for($Page = 1; $Page <= $maxPage; $Page++){
                            $link[] = $this->current_url.'pg'.$Page;
	                    }
	                    return $link;
	                }],
	                ])->getData(function($item){
	                    return $item['link'];
	                });
                $urlarr = array_merge($urlarr,$url[0]);
				return $urlarr;
	           }
	        }

	    return $urlarr;
	}

    /*
	 * 获取列表页
	 * */
    Public function house_list($url = ''){
        $house_info = array();
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['.listContent > li > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
                return $u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $house_info;
    }

	/*
	 * 获取详情
	*/
	public function house_detail($source_url){
		$house_info = \QL\QueryList::run('Request',[
			'target' => $source_url,
		])->setQuery([
			'house_title' => ['.main', 'text', '', function($title){
				return str_replace(array("\t", "\n", "\r", " "),"", $title);
			}],
			'house_price' => ['span.total', 'text', '', function($price){
				return $price;
			}],
			'cityarea_id' => ['.l-txt > a:nth-child(5)', 'text', '', function($cityarea_id){
			     return str_replace('二手房','',$cityarea_id);
			}],
			'cityarea2_id' => ['.l-txt > a:nth-child(7)', 'text', '', function($cityarea2_id){
			     return str_replace('二手房','',$cityarea2_id);
			}],

			'house_totalarea' => ['div.area > div:nth-child(1)', 'text', '', function($house_totalarea){
				return str_replace('平米', '', $house_totalarea);
			}],

			'house_room' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_room){
				 preg_match("/(\d+)室/", $house_room, $hr);
				 return $hr[1];
			}],

			'house_hall' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_hall){
				preg_match("/(\d+)厅/", $house_hall, $hh);
				return $hh[1];
			}],

			'house_kitchen' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_kitchen){
				preg_match("/(\d+)厨/", $house_kitchen, $hk);
				return $hk[1];
			}],
			'house_toilet' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(1)', 'text', '-span', function($house_toilet){
			preg_match("/(\d+)卫/", $house_toilet, $ht);
			    return $ht[1];
			}],
			

			'house_toward' => ['.houseInfo > div:nth-child(2) > div:nth-child(1)', 'text', ''],

			'house_floor' => ['.room > div:nth-child(2)', 'text', '', function($house_floor){
				preg_match('/(低楼层|中楼层|高楼层)/',$house_floor,$hf);
				return str_replace('楼层', '', $hf[1]);
			}],

			'house_topfloor' => ['.room > div:nth-child(2)', 'text', '', function($house_topfloor){
				preg_match('/共(\d+)层/',$house_topfloor,$htf);
				return $htf[1];
			}],

			'owner_name' => ['div.brokerInfo:nth-child(5) > div:nth-child(2) > div:nth-child(1) > a:nth-child(1)', 'text', ],

			'owner_phone' => ['div.brokerInfo:nth-child(5) > div:nth-child(2) > div:nth-child(3)', 'text', '', function ($op){
			    $op = trimall($op);
			    return str_replace('转',',',$op);
			}],

			'house_pic_unit' => ['.smallpic > li > img', 'src', '', function($house_pic_unit){
				return $house_pic_unit;
			}],

			'house_pic_title' => ['.smallpic > li', 'data-desc', '',function($house_pic_title){
				return $house_pic_title;
			}],

			'house_pic_layout' => [],

			'house_fitment' => ['.base > div:nth-child(2) > ul:nth-child(1) > li:nth-child(9)','text','-span'],

            'borough_name' => ['a.info', 'text', ''],

			'house_desc' => ['div.noData:nth-child(2)','text',''],

			'house_number' => ['.houseRecord > span:nth-child(2)', 'text', '-a -br', function($house_number){
				return trimall($house_number);
			}],

			'house_type' => [],

			'house_built_year' => ['div.area:nth-child(3) > div:nth-child(2)','text','',function($house_built_year){
			    preg_match('/(\d+)年建/',$house_built_year,$hby);
			    return $hby[1];
			}],

			'house_relet' => [],

			'house_style' => [],
		])->getData(function($data) use($source_url){
			$data['company_name'] = '链家';
			//下架检测
//			$data['off_type'] = $this->is_off($source_url);
			return $data;
		});
		foreach((array)$house_info as $key => $value){
			if(isset($house_info[$key]['house_pic_unit'])){
				$house_pic_unit[] = $house_info[$key]['house_pic_unit'];
			}
		}
		$house_info[0]['house_pic_unit'] = implode('|', $house_pic_unit);
		return $house_info[0];
	}
	
	/*
	 * 下架检测
	 * */
	public function is_off($url,$html=''){
	    if(!empty($url)){
	        if(empty($html)){
	            $html = $this->getUrlContent($url);
	        }
	        //抓取下架标识
	        $off_type = 1;
	        if($html){
	            $Tag = \QL\QueryList::Query($html,[
	                "isOff" => ['.main > span:nth-child(1)','text',''],
	                "404" => ['.sub-tle','text',''],
	            ])->getData(function($item){
	                return $item;
	            });
	            if(empty($Tag) ){
	                $off_type = 2;
	            }
	        }
	        return $off_type;
	    }
	    return -1;
	}
}