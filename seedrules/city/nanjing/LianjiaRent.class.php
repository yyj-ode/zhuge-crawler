<?php
namespace nanjing;
/**
 * @description 南京链家整租
 * @classname 南京链家(k-ok)
 */

class LianjiaRent extends \city\PublicClass{
    public $PRE_URL = 'http://nj.lianjia.com/zufang/';
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
	        'rp1' => '1000元以下',
	        'rp2' => '1000-1500元',
	        'rp3' => '1500-2000元',
	        'rp4' => '2000-3000元',
	        'rp5' => '3000元以上',
	    );
        $urlarr = [];
	    foreach ($dis as $k1 => $v1){
	        foreach ($p as $k2 => $v2){
	            $this->current_url = $this->PRE_URL.$k1.'/'.$k2;
	            $url = \QL\QueryList::run('Request', [
	                'target' => $this->current_url,
	            ])->setQuery([
	                'link' => ['.list-head > h2:nth-child(1) > span:nth-child(1)','text', '', function($total){
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
	           }
	        }
	    return $urlarr;
	}
	

    /*
	 * 获取列表页
	 * */
    Public function house_list($url){
        $house_info = array();
        $house_info = \QL\QueryList::run('Request', [
            'target' => $url,
        ])->setQuery([
            //获取单个房源url
            'link' => ['#house-lst li div:nth-child(2) > h2:nth-child(1) > a:nth-child(1)', 'href', '', function($u){
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

			'house_totalarea' => ['p.lf:nth-child(1)', 'text', '', function($house_totalarea){
				return str_replace('平米', '', $house_totalarea);
			}],

			'house_room' => ['p.lf:nth-child(2)', 'text', '-i', function($house_room){
				 preg_match("/(\d+)室/", $house_room, $hr);
				 return $hr[1];
			}],

			'house_hall' => ['p.lf:nth-child(2)', 'text', '-i', function($house_hall){
				preg_match("/(\d+)厅/", $house_hall, $hh);
				return $hh[1];
			}],

			'house_kitchen' => ['p.lf:nth-child(2)', 'text', '-i', function($house_kitchen){
				preg_match("/(\d+)厨/", $house_kitchen, $hk);
				return $hk[1];
			}],
			'house_toilet' => ['p.lf:nth-child(2)', 'text', '-i', function($house_toilet){
			preg_match("/(\d+)卫/", $house_toilet, $ht);
			    return $ht[1];
			}],
			'house_toward' => ['p.lf:nth-child(4)', 'text', '-i'],

			'house_floor' => ['p.lf:nth-child(3)', 'text', '', function($house_floor){
				preg_match('/(低|中|高)[楼层]?/',$house_floor,$hf);
				return $hf[1];
			}],

			'house_topfloor' => ['p.lf:nth-child(3)', 'text', '', function($house_topfloor){
				preg_match('/(\d+)层/',$house_topfloor,$htf);
				return $htf[1];
			}],

			'owner_name' => ['.brokerName > a:nth-child(1)', 'text', ],

			'owner_phone' => ['div.phone:nth-child(3)', 'text', '', function ($op){
			    $op = trimall($op);
			    return str_replace('转',',',$op);
			}],

			'house_pic_unit' => ['.thumbnail > ul:nth-child(1) > li > img:nth-child(1)', 'src', '', function($house_pic_unit){
				return $house_pic_unit;
			}],

			'house_pic_layout' => [],

			'house_fitment' => [],

            'borough_name' => ['.zf-room > p:nth-child(7) > a:nth-child(2)', 'text', ''],

			'house_desc' => ['div.noData','text',''],

			'house_number' => ['.houseNum', 'text', '', function($house_number){
                preg_match('/(\w+)/',$house_number,$number);
				return trimall($number[1]);
			}],

			'house_type' => [],

			'house_built_year' => [],

			'house_relet' => [],

			'house_style' => [],
            'cityarea_id'=> ['.zf-room > p:nth-child(8) > a:nth-child(2)','text',''],
            'cityarea2_id'=>['.zf-room > p:nth-child(8) > a:nth-child(3)','text',''],
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
	    return 2;//TODO 未找到下架房源直接返回2
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