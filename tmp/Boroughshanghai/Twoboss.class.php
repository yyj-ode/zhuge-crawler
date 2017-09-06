<?php
namespace Boroughshanghai;
/**
  *@description 兔博士小区成交信息抓取
  * @classname 上海兔博士
  *@author python
  *@version 1
  *@since 2016-04-08
*/

class Twoboss extends \city\PublicClass{

	private $city_id = '605'; //上海
    
    Public function house_page(){
        $source_url = "http://ta.qa.2boss.cn/house/getDistrictName";
        $Parameters ["city_id"] = $this->city_id;
		$result = $this->handleJson($this->getUrlContent($source_url, ['post' => $Parameters]));
         $house_info = array();
        foreach((array)$result as $k=>$res){  
             $Parameters["district_id"] = $res['udc_code'];
             $urls = "http://ta.qa.2boss.cn/house/getPlateName";
             $houselist = $this->handleJson($this->getUrlContent($urls, ['post' => $Parameters]));
             foreach((array)$houselist as $key => $value){
                 $house_info[] = $value['udc_code'].'-'.$Parameters["district_id"];
             }
        }
        return $house_info;
    }
	/*
	 * 获取列表页
	 */
	public function house_list($udc_code){
		$codes = explode('-', $udc_code);
		$urls = "http://ta.qa.2boss.cn/house/getSalesListByArea";
		$Par['plate_id'] = $codes[0];
		$Par['city_id'] = $this->city_id;
		$Par['selectid'] = 3;
		$Par['district_id'] = $codes[1];
		$datas = $this->handleJson($this->getUrlContent($urls,['post' => $Par]));
		$info = $datas[0];
		$house_url = "http://ta.qa.2boss.cn/house/getHouseSales";
		$house_id = $info['house_id'];
		$Pa['times'] = '1';
		$Pa['city_id'] = '604';
		$Pa['lines'] = '50';
		$Pa['if_garage'] = '0';
		$Pa['house_id'] = $house_id;
		$Pa['area'] = "10,9999";
		$Pa['sorttype'] = '2';
		$da = $this->handleJson($this->getUrlContent($house_url,['post' => $Pa]));
		foreach((array)$da as $key => $value){
			$house_list[$key] = $value;
			$house_list[$key]['house_id'] = $house_id;
			$house_list[$key]['borough_name'] = $info['house_name'];
			$house_list[$key]['finish_time'] = strtotime($value['date']);

			$house_list[$key]['finish_price'] = $value['sprice']*$value['area'];
			$house_list[$key]['house_area'] = $value['area'];

			$roomtype = explode('室', $value['roomtype']);
			$house_list[$key]['house_room'] = $roomtype[0];
			$house_list[$key]['house_hall'] = mb_substr($roomtype[1], 0, 1, 'utf-8');
			$house_list[$key]['house_toward'] = '';
			$house_list[$key]['house_floor'] = $value['floor'];
			$house_list[$key]['house_topfloor'] = '';
			$house_list[$key]['broker_name'] = '';
			$house_list[$key]['building_number'] = $value['building'];
			$house_list[$key]['company_name'] = $value['agent_name'];
			$url = 'http://share.wap.2boss.cn/bkservice/index.html?city_id='.$this->city_id.'&house_id='.$house_id;
			$house_list[$key] = serialize($house_list[$key]).getSourceUrlTag().$url;
		}
		return $house_list;





		$udc_code = $url;
		$Parameters ["district_id"] = $udc_code;
		$urls = "http://ta.qa.2boss.cn/house/getPlateName";
		$data = $this->getUrlContent($urls, ['post' => $Parameters]);
		$result = json_decode($data,1);
		foreach ($result as $key=>$value){
		    //前district_id 后plate_id
		    $Par['plate_id'] = $value['udc_code'];
		    $Par['city_id'] = 604;
		    $Par['selectid'] = 3;
		    $Par['district_id'] = $udc_code;
		    $urls = "http://ta.qa.2boss.cn/house/getSalesListByArea";
		    $datas = $this->getUrlContent($urls,['post' => $Par,'handle' => 'array']);
		    $info = $datas['lbsInfo'][0];
		    $house_url = "http://ta.qa.2boss.cn/house/getHouseSales";
		    $house_id = $info['house_id'];
		    $Pa['times'] = '1';
		    $Pa['city_id'] = '604';
		    $Pa['lines'] = '50';
		    $Pa['if_garage'] = '0';
		    $Pa['house_id'] = $house_id;
		    $Pa['area'] = "30,999";
		    $Pa['sorttype'] = '2';
		    $da = $this->handleJson($this->getUrlContent($house_url,['post' => $Pa]));
		    
		    foreach ($da as $key=>$value){
		       $house_list[] = $value;
		      
		    }
		}
// 		    return serialize($house_list);
	}
	
	private function handleJson($jsondata = false){
	    if($jsondata){
	        preg_match('/\[{.*}\]/', $jsondata, $out_arr);
	        return objarray_to_array(json_decode($out_arr[0], TRUE));
// 	        return json_decode($out_arr,true);
	    }
	    return false;
	}
	/*
	 * 获取详情页
	 *
	 *  */
	public function house_detail($url = '', $status = true){
		$sourcetag = getSourceUrlTag();
		$data = explode($sourcetag, $url);
		$data = $data[0];
	    $content = unserialize($data);
	    return $content;
	    $house_info['borough_name'] = $info['house_name'];
	    $house_info['finish_time'] = $info['aa'];
	    $house_info['finish_price'] = $info;
	    $house_info['house_area'] = '';
	    $house_info['house_room'] = '';
	    $house_info['house_hall'] = '';
	    $house_info['house_toward'] = '';
	    $house_info['house_floor'] = '';
	    $house_info['house_topfloor'] = '';
	    $house_info['broker_name'] = '';
	    $house_info['source_url'] = '';
	    $house_info['source'] = '';
	    
	    
	    
	    
	    
	}
	

}
