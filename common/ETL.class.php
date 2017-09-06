<?php
/**
 *ETLSellAction.class.php
 *二手房ETL处理父类，ETL不同单独写一个类，继承此类
 *@author kys
 *@version 1
 *@since 2015-7-18
 */
class ETLSellAction{
	
	//二手房数据（数组类型）
	protected $house_info=array();
	//数据是否补全（true：补全；flase：未补全）
	protected $is_fill=false;
	//LOG类型
	protected $log_notice="";
	//整体开始时间
	protected $time_all_start=0;
	//整体结束时间
	protected $time_all_end=0;
	//传递过来数据总量
	protected $num_all=0;
	//重复数据总量
	protected $num_all_repeat=0;
	//插入数据库中的数据总量
	protected $num_all_insert=0;
	//城区，商圈表
	protected $cityarea_item=array();
	//小区主表
	protected $borough=array();
	//小区别名表
	protected $borough_byname=array();
	//租房信息表
	//二手房
	protected $house_sell_gov=array();
	//小区均价
	protected $avg_price=array();
	/**
	 * 全局变量赋值
	 */
	protected function __set_params($params){
		$this->time_all_start=microtime();
		if(is_array($params) && isset($params)){
			$house_info=$params['house_info'];
			$is_fill=$params['is_fill'];
			$log_notice=$params['log_notice'];
			$this->num_all=count($house_info);
		}else{
			exit;
		}
		
		$this->house_info=array_merge($house_info,array());
		$this->is_fill=$is_fill;
		$this->log_notice= $log_notice;
		$this->cityarea_item = M('city_item');
		$this->borough = M('borough');
		$this->borough_byname = M('borough_byname');
		$this->house_sell_gov = M('house_sell_gov');
		$this->house_sell_del = M('house_sell_del');
		$this->broker_check = M('broker_check');
		$this->avg_price = M('avg_price');
	}
	/****
	 * 
	 * 单个祛重
	 */
	public function remove(){
		$arrct =array();
		for($i=0;$i<count($this->house_info);$i++){
			for($j=$i+1;$j<count($this->house_info);$j++){
				if($this->house_info[$i]['source_url']==$this->house_info[$j]['source_url']){
					$arrct[]=$i;
					break;	
				}
				if($this->house_info[$i]['house_title']==$this->house_info[$j]['house_title'] && $this->house_info[$i]['borough_name']==$this->house_info[$j]['borough_name'] && $this->house_info[$i]['house_totalarea']==$this->house_info[$j]['house_totalarea']){
					$arrct[]=$i;
					break;
				}
			}	
		}
		if(!($arrct=="" || empty($arrct))){
			foreach ($arrct as $value) {
				unset($this->house_info[$value]);
			}
			$this->house_info=array_merge($this->house_info,array());
		}
	}
	

	/**
	 * 运行程序
	 */
	public function rungoodhouse($params){
		//参数赋值
		$this->__set_params($params);
		//单次祛重
		$this->remove();
		//字段校验、处理
		$this->_hadleFields();
		$this->house_info=array_merge($this->house_info,array());
	
		//是否为下架房源
		$this->_hadleSourceUrl();
		$this->house_info=array_merge($this->house_info,array());
	
		//房源判重
		$this->_handleYoufangExists();
		$this->house_info=array_merge($this->house_info,array());
	
		//处理小区
		$this->_handleBorouogh();
		$this->house_info=array_merge($this->house_info,array());
	
	
		//统一化处理
		$this->_mergeOne();
		$this->house_info=array_merge($this->house_info,array());
		$this->_bathInsertAllYoufang();
	
	}
	/**
	 * 运行程序
	 * 
	 */
	public function run($params){
		//参数赋值
		$this->__set_params($params);

		//单次祛重
		$this->remove();
		//字段校验、处理
		$this->_hadleFields();
		$this->house_info=array_merge($this->house_info,array());		
        
		//是否为下架房源
		$this->_hadleSourceUrl();
		$this->house_info=array_merge($this->house_info,array());
		
		//房源判重
		$this->_handleSellExists();		
		$this->house_info=array_merge($this->house_info,array());
		
		//处理小区
		$this->_handleBorouogh();	
		$this->house_info=array_merge($this->house_info,array());		
		
		
		//统一化处理
		$this->_mergeOne();
		$this->house_info=array_merge($this->house_info,array());
		
		//库房、Q房、麦田去重
		$this->depremove();
		$this->house_info = array_merge($this->house_info);
		//dump($this->house_info);die;
		$this->_bathInsertAllSell();
		
	}
	
	/*
	个人出售房源ETL
	*/
	public function personal_run($params){
		//参数赋值
		$this->__set_params($params);
		//单次祛重
		$this->remove();
		
		//字段校验、处理
		$this->_hadleFieldsPersonal();
		$this->house_info=array_merge($this->house_info,array());
		//是否为下架房源
		$this->_hadleSourceUrl();
		$this->house_info=array_merge($this->house_info,array());
		//经纪人过滤
		$this->_hadleBroker();
		$this->house_info=array_merge($this->house_info,array());
		//过滤标题关键字
		$this->_handleTitleKeyword();		
		$this->house_info=array_merge($this->house_info,array());
		//过滤房源描述关键字
		$this->_handleDescKeyword();
		$this->house_info=array_merge($this->house_info,array());
		//过滤房源标题是否含有经纪公司关键字
		$this->_handleTitleCompanyKeyword();
		$this->house_info=array_merge($this->house_info,array());
		//过滤房源描述是否含有经纪公司关键字
		$this->_handleDescCompanyKeyword();
		$this->house_info=array_merge($this->house_info,array());
		
		//处理小区
		$this->_handleBorouogh();	
		$this->house_info=array_merge($this->house_info,array());
		
		//根据电话号码去重
		$this->_handlePhone();		
		$this->house_info=array_merge($this->house_info,array());
		
		//统一化处理
		$this->_mergeOnePersonal();
		$this->house_info=array_merge($this->house_info,array());
		//入库gov
		$this->_aloneInsertGov();
		//计算偏差
		$this->countPiancha();
		$this->house_info=array_merge($this->house_info,array());
		//入库fangzhu
		$this->_aloneInsertGov();
	}
	/*
	经纪人过滤	
	*/
	protected function _hadleBroker(){
		$info_temp = array ();
		foreach($this->house_info as $key=>$value){
			$is_broker = $this->broker_check->where('phone = '.$value['owner_phone'])->select();
			if($is_broker){
				Log::write ( "【" .$value['source_url']. "】是中介房源", 'DEBUG' );
			}else{
				$info_temp [] = $value;
			}
		}
		$this->house_info=$info_temp;
	}
	/*
	检测标题关键字
	*/
	protected function _handleTitleKeyword(){
		$title_keyword = C('ARRAY_TITLE_KW');
		foreach($this->house_info as $key=>$value){
			foreach($title_keyword as $keyword){
				$result = strpos($value['house_title'],$keyword);
				$result = strval($result);
				if($result != false || $result === "0"){
					unset($this->house_info[$key]);
					continue;
				}
			}
		}
	}
	/*
	检测房源描述关键字
	*/
	protected function _handleDescKeyword(){
		$desc_keyword = C('ARRAY_DESC_KW');
		foreach($this->house_info as $key=>$value){
			foreach($desc_keyword as $keyword){
				$result = strpos($value['house_desc'],$keyword);
				$result = strval($result);
				if($result != false || $result === "0"){
					unset($this->house_info[$key]);
					continue;
				}
			}
		}
	}
	
	/*
	 检测房源标题是否含有经纪公司关键字
	 */
	protected function _handleTitleCompanyKeyword(){
	    $company_keyword = C('ARRAY_COMPANY_KW');
	    foreach($this->house_info as $key=>$value){
	        foreach($company_keyword as $keyword){
	            $result = strpos($value['house_title'],$keyword);
	            $result = strval($result);
	            if($result != false || $result === "0"){
	                unset($this->house_info[$key]);
	                continue;
	            }
	        }
	    }
	}
	
	/*
	 检测房源描述是否含有经纪公司关键字
	 */
	protected function _handleDescCompanyKeyword(){
	    $company_keyword = C('ARRAY_COMPANY_KW');
	    foreach($this->house_info as $key=>$value){
	        foreach($company_keyword as $keyword){
	            $result = strpos($value['house_desc'],$keyword);
	            $result = strval($result);
				if($result != false || $result === "0"){
	                unset($this->house_info[$key]);
	                continue;
	            }
	        }
	    }
	}
	
	/*
	业主电话号码去重
	*/
	protected function _handlePhone(){
		$info_temp = array ();
		foreach($this->house_info as $key=>$value){
			$exist = $this->house_sell_gov->where(array('borough_id'=>$value['borough_id'], 'house_floor'=>$value['house_floor'], 'house_topfloor'=>$value['house_topfloor'], 'house_room'=>$value['house_room'], 'owner_phone'=>$value['owner_phone']))->select();
			if($exist){
				Log::write ( "【" .$value['owner_phone']. "】已存在该业主房源", 'DEBUG' );
			}else{
				$info_temp [] = $value;
			}
		}
		$this->house_info=$info_temp;
	}
	//
	public function depremove(){
		//source= 3，5, 7的进行判断
		//标题一样的就去掉
		
		$house_sell_gov = M('house_sell_gov');
		foreach($this->house_info as $k=>$v){
			if($v['source'] !== 1){
				//如果存在相同的标题，则
// 				$house_title = $house_sell_gov->where(array('house_title'=>$v['house_title'],'borough_name'=>$v['borough_name'],'house_totalarea'=>$v['house_totalarea'],'source'=>$v['source']))->limit(1)->find();
// 				if($house_title != null){
// 					unset($this->house_info[$k]);
// 					continue;
// 				}
				
				//看是否是同一个小区  总楼层 本楼层 面积 室 厅 卫 价格  break
				$exist = $house_sell_gov->where(array('borough_id'=>$v['borough_id'],'source'=>$v['source'], 'house_floor'=>$v['house_floor'], 'house_topfloor'=>$v['house_topfloor'], 'house_room'=>$v['house_room'], 'house_hall'=>$v['house_hall'],'house_toward'=>$v['house_toward']))->limit(1)->find();
				if($exist != null){
					unset($this->house_info[$k]);
					continue;
				}
			}
			if($v['source'] == 5){
			    $res = preg_match('/Q房网/u',$v['company']);
			    if(!$res){
			        unset($this->house_info[$k]);
			        continue;
			    }
			}
		}
// 		print_r($this->house_info);die;
	}
	
	
	public function test(){
		$value['borough_id']=12;
		$value['borough_name']='远洋山水';
		$value['source'] = 10;
		$value['house_desc']="";
				//房源描述如果为空的话
			$this->borough = M('borough');
			if(empty($value['house_desc']) || $value['house_desc']==""){
				 $borough_info = $this->borough->table(array('borough'=>'a','borough_info'=>'b'))->field('a.*,b.*')->where("a.id=b.id and a.id=".$value['borough_id'])->select();
				 if($borough_info){
                            $borough_shop = $borough_info[0]['borough_shop'];
                            $borough_hospital = $borough_info[0]['borough_hospital'];
                            $middle_school = $borough_info[0]['middle_school'];
                            $elementary_school = $borough_info[0]['elementary_school'];
                            $nursery_school = $borough_info[0]['nursery_school'];
                            $borough_bus = $borough_info[0]['borough_bus'];
                            $borough_dining = $borough_info[0]['borough_dining'];
                        }
                        if($value['source'] == 10){
                        	switch (intval($value['source_owner'])) {
                        		case 1:
                        			$fromname='房主儿';
                        			break;
                        		case 2:
                        			$fromname='爱直租';
                        			break;
                        		case 3:
                        			$fromname='搜房';
                        			break;
                        		default:
                        			$fromname='';
                        			break;
                        	}
                    		$house_desc = "我是业主，我的房子在".$value['borough_name']."，附近的有超市商场 ".$borough_shop."，附近的有医院 ".$borough_hospital."，附近的有学校 ".$middle_school.$elementary_school.$nursery_school."，附近的交通也挺便利的 ".$borough_bus."，饭店也不错哦， ".$borough_dining."，希望把房子交给有缘人。";
                        }else{
                        	$sourcename = C("ARRAY_GOV_SOURCE");
                          	$house_desc = "我是".$sourcename[$value['source']]."的一名经纪人，竭诚为您服务，这套房子在".$value['borough_name']."，附近的有超市商场 ,".$borough_shop." 附近有医院 ,".$borough_hospital." 附近的有学校,".$middle_school.$elementary_school.$nursery_school."，附近的交通也挺便利的，".$borough_bus."，饭店也不错哦, ".$borough_dining."，我保证信息的真实，并期待您和我联系";
                        }
			}
			
	}
	protected  function _mergeOne(){
		
	  foreach($this->house_info as $key=>$value){
		
		if($value['source']==10 && $value['source_owner']==1){
		// if($value['source']==10){
			//朝向
			if($value['house_toward']){
				$toward = C('ARRAY_TOWARD_CONFIG_FANGZHUER');
				$value['house_toward'] = $toward[$value['house_toward']];			
			}
			//装修情况
			if($value['house_fitment']){
				$fitment = C('ARRAY_FITMENT_CONFIG_FANGZHUER');
                $value['house_fitment'] = $fitment[$value['house_fitment']];
			}
			//房屋类型
			if($value['house_type']){
				$htype = C('ARRAY_HOUSETP_CONFIG_FANGZHUER');
                $value['house_type'] = $htype[$value['house_type']];
			}
		}
		//电话为空的话 直接返回官网电话
		if($value['owner_phone']=="" || empty($value['owner_phone'])){
			if($value['source']!=10){
				$govtel = C('ARRAY_GOV_TEL');
				$this->house_info[$key]['owner_phone'] = $govtel[$value['source']];
			}else{
//				$govtel = C('ARRAY_GOV_OWNER_TEL');
//				$this->house_info[$key]['owner_phone'] = $govtel[$value['source_owner']];
				$this->house_info[$key]['owner_phone'] = "4000981985";
			}

		}
			//厅转换
			if($value['house_hall']){
				foreach (C('ARRAY_HALL_CONFIG') as $inkey=>$val){
            	  if(preg_match("/.*$val.*/",trimall($value['house_hall']))){
                    	$this->house_info[$key]['house_hall'] = $inkey;
                    	break;
                	}
            	}                
			}
			//装修情况
			if($value['house_fitment']){
			   foreach (C('ARRAY_FITMENT_CONFIG') as $inkey=>$val){
            	  if(preg_match("/.*$val.*/",trimall($value['house_fitment']))){
                    	$this->house_info[$key]['house_fitment'] = $inkey;
                    	break;
                	}
            	}
			}
			//朝向
			if($value['house_toward']){
				foreach (C('ARRAY_TOWARD_CONFIG') as $inkey=>$val){
            	  if(preg_match("/.*$val.*/",trimall($value['house_toward']))){
                    	$this->house_info[$key]['house_toward'] = $inkey;
                    	break;
                	}
            	}
			}
            //房源类型
            if($value['house_type']){
            	foreach (C('ARRAY_HOUSETP_CONFIG') as $inkey=>$val){
            	  if(preg_match("/.*$val.*/",trimall($value['house_type']))){
                    	$this->house_info[$key]['house_type'] = $inkey;
                    	break;
                	}
            	}
            }
            //房屋楼层 高 中 低,判断是不是数字
            if($value['house_floor']){
               foreach (C('ARRAY_HFLOOR_CONFIG') as $inkey=>$val){
            	  if(preg_match("/.*$val.*/",trimall($value['house_floor']))){
                    	$this->house_info[$key]['house_floor'] = $inkey;
                    	break;
                	}
            	}                
            }	    
			//房源描述如果为空的话
			if(empty($value['house_desc']) || $value['house_desc']==""){
				 $borough_info = $this->borough->table(array('borough'=>'a','borough_info'=>'b'))->field('a.*,b.*')->where("a.id=b.id and a.id=".$value['borough_id'])->select();
                        if($borough_info){
                            $borough_shop = $borough_info['borough_shop'];
                            $borough_hospital = $borough_info['borough_hospital'];
                            $middle_school = $borough_info['middle_school'];
                            $elementary_school = $borough_info['elementary_school'];
                            $nursery_school = $borough_info['nursery_school'];
                            $borough_bus = $borough_info['borough_bus'];
                            $borough_dining = $borough_info['borough_dining'];
                        }
                        if($value['source'] == 10){
                    		$house_desc = "我是业主，我的房子在".$value['borough_name']."，附近的有超市商场 ".$borough_shop."，附近的有医院 ".$borough_hospital."，附近的有学校 ".$middle_school.$elementary_school.$nursery_school."，附近的交通也挺便利的 ".$borough_bus."，饭店也不错哦， ".$borough_dining."，希望把房子交给有缘人。";
                        }else{
                        	$sourcename = C("ARRAY_GOV_SOURCE");
                          	$house_desc = "我是".$sourcename[$value['source']]."的一名经纪人，竭诚为您服务，这套房子在".$value['borough_name']."，附近的有超市商场 ,".$borough_shop." 附近有医院 ,".$borough_hospital." 附近的有学校,".$middle_school.$elementary_school.$nursery_school."，附近的交通也挺便利的，".$borough_bus."，饭店也不错哦, ".$borough_dining."，我保证信息的真实，并期待您和我联系";
                        }
                    $this->house_info[$key]['house_desc'] = $house_desc;			
			}	
			if($value['owner_phone']){
				$mem = M('member');
				$code= M('code');
				$phone = $value['owner_phone'];
				$flag = preg_match("/1(3[0-9]|4[5|7]|5[0-9]|7[6|7|8]|8[0-9])[0-9]{8}/", $phone) || preg_match("/170[0-9][0-9]{7}/", $phone);
				if($flag){
					$memph = $mem->where('username='.$value['owner_phone'])->find();
					if(!$memph){
						$member = array();
						if($value['source'] == 10){
							$member['role_type'] = 1;
						}else{
							$member['role_type'] = 2;
						}
						$member['username'] = $value['owner_phone'];
						$member['real_name'] = $value['owner_name'];
						$member['city_id'] = 1;
						$member['add_time'] = time();
						//插入成功会返回id
							
						$id = $mem->add($member);
						if($id >= 1){
							$data['user_id'] = $id;
							$data['code'] = 0;
							$data['status'] = 1;
							$data['createtime'] = date('Y-m-d H:i:s');
							$code_id = $code->add($data);
							if( $code_id == false){
								LOG::write(M()->getLastSql(), INFO);
							}
						}else{
							LOG::write(M()->getLastSql(), INFO);
						}
					}

				}
			}

			
			
			 $this->house_info[$key]['created'] = time();
			 $this->house_info[$key]['updated'] = time();
		}
	}
	
	/*个人房源*/
	protected  function _mergeOnePersonal(){
	    
	    foreach($this->house_info as $key=>$value){
	
	        //厅转换
	        if($value['house_hall']){
	            foreach (C('ARRAY_HALL_CONFIG') as $inkey=>$val){
	                if(preg_match("/.*$val.*/",trimall($value['house_hall']))){
	                    $this->house_info[$key]['house_hall'] = $inkey;
	                    break;
	                }
	            }
	        }
	        //装修情况
	        if($value['house_fitment']){
	            foreach (C('ARRAY_FITMENT_CONFIG') as $inkey=>$val){
	                if(preg_match("/.*$val.*/",trimall($value['house_fitment']))){
	                    $this->house_info[$key]['house_fitment'] = $inkey;
	                    break;
	                }
	            }
	        }
	        
	        //朝向
	        if($value['house_toward']){
	            foreach (C('ARRAY_TOWARD_CONFIG') as $inkey=>$val){
	                if(preg_match("/.*$val.*/",trimall($value['house_toward']))){
	                    $this->house_info[$key]['house_toward'] = $inkey;
	                    break;
	                }else{
	                    $this->house_info[$key]['house_toward'] = '';
	                }
	            }
	        }
	        //房屋楼层 高 中 低,判断是不是数字
	        if($value['house_floor']){
	            if(is_numeric($value['house_floor'])){
	                $this->house_info[$key]['house_floor'] = $value['house_floor'];
	            }else{
	                foreach (C('ARRAY_HFLOOR_CONFIG') as $inkey=>$val){
	                    if(preg_match("/.*$val.*/",trimall($value['house_floor']))){
	                        $this->house_info[$key]['house_floor'] = $inkey;
	                        break;
	                    }else{
	                        $this->house_info[$key]['house_floor'] = '';
	                    }
	                } 
	            }
	        }
	        
	        //房源描述如果为空的话
	        if(empty($value['house_desc']) || $value['house_desc']==""){
	            $borough_info = $this->borough->table(array('borough'=>'a','borough_info'=>'b'))->field('a.*,b.*')->where("a.id=b.id and a.id=".$value['borough_id'])->select();
	            if($borough_info){
	                $borough_shop = $borough_info['borough_shop'];
	                $borough_hospital = $borough_info['borough_hospital'];
	                $middle_school = $borough_info['middle_school'];
	                $elementary_school = $borough_info['elementary_school'];
	                $nursery_school = $borough_info['nursery_school'];
	                $borough_bus = $borough_info['borough_bus'];
	                $borough_dining = $borough_info['borough_dining'];
	            }
	            if($value['source'] == 10){
	                $house_desc = "我是业主，我的房子在".$value['borough_name']."，附近的有超市商场 ".$borough_shop."，附近的有医院 ".$borough_hospital."，附近的有学校 ".$middle_school.$elementary_school.$nursery_school."，附近的交通也挺便利的 ".$borough_bus."，饭店也不错哦， ".$borough_dining."，希望把房子交给有缘人。";
	            }else{
	                $sourcename = C("ARRAY_GOV_SOURCE");
	                $house_desc = "我是".$sourcename[$value['source']]."的一名经纪人，竭诚为您服务，这套房子在".$value['borough_name']."，附近的有超市商场 ,".$borough_shop." 附近有医院 ,".$borough_hospital." 附近的有学校,".$middle_school.$elementary_school.$nursery_school."，附近的交通也挺便利的，".$borough_bus."，饭店也不错哦, ".$borough_dining."，我保证信息的真实，并期待您和我联系";
	            }
	            $this->house_info[$key]['house_desc'] = $house_desc;
	        }
	        if($value['owner_phone']){
	            $mem = M('member');
	            $code= M('code');
	            $phone = $value['owner_phone'];
	            $flag = preg_match("/1(3[0-9]|4[5|7]|5[0-9]|7[6|7|8]|8[0-9])[0-9]{8}/", $phone) || preg_match("/170[0-9][0-9]{7}/", $phone);
	            if($flag){
	                $memph = $mem->where('username='.$value['owner_phone'])->find();
	                if(!$memph){
	                    $member = array();
	                    if($value['source'] == 10){
	                        $member['role_type'] = 1;
	                    }else{
	                        $member['role_type'] = 2;
	                    }
	                    $member['username'] = $value['owner_phone'];
	                    $member['real_name'] = $value['owner_name'];
	                    $member['city_id'] = 1;
	                    $member['add_time'] = time();
	                    //插入成功会返回id
	                    	
	                    $id = $mem->add($member);
	                    if($id >= 1){
	                        $data['user_id'] = $id;
	                        $data['code'] = 0;
	                        $data['status'] = 1;
	                        $data['createtime'] = date('Y-m-d H:i:s');
	                        $code_id = $code->add($data);
	                        if( $code_id == false){
	                            LOG::write(M()->getLastSql(), INFO);
	                        }
	                    }else{
	                        LOG::write(M()->getLastSql(), INFO);
	                    }
	                }
	
	            }
	        }
	        $this->house_info[$key]['created'] = time();
	        $this->house_info[$key]['updated'] = time();
	    }
	}
	/*  
	 * 计算偏差
	 * */
	public function countPiancha(){
	    
	    foreach($this->house_info as $key=>$value){
            if($value['house_totalarea'] !=0 && !empty($value['house_totalarea']) && $value['house_totalarea'] > 0){
                $avg_price = $this->avg_price->where("borough_name = '".$value['borough_name']."'")->field("avg_price")->find();
                $this->house_info[$key]['piancha'] = ($value['house_price']/$value['house_totalarea']-$avg_price['avg_price'])/$avg_price['avg_price']*100;
            }else{
                $this->house_info[$key]['piancha'] = 0;
            }
	    }
	}
	
	
	/**
	 * 返回传递过来数量总量
	 */
	public function getAllNum(){
		return $this->num_all;
	}
	/**
	 * 返回重复数量总量
	 */
	public function getAllNumReapt(){
		return $sthis->num_all_repeat;
	}
	
	/**
	 * 处理字段异常情况
	 * //处理房源类型
		$info[$i]['house_type']=handleHouseType($info[$i]['house_type']);
		//处理装修情况
		$info[$i]['house_fitment']=handleHouseFitment($info[$i]['house_fitment']);
		//处理朝向
		$info[$i]['house_toward']=handleHouseToward($info[$i]['house_toward']);
        
		//是否补全 Q房不需要补数据所以默认为 已经补全  1：已经补全    2：未补全
		$info[$i]['is_fill'] = $is_fill==true?1:2;
	 */
	protected function _hadleFields(){
		foreach($this->house_info as $key=>$value){
			//小区名不能为空，为空直接扔掉
			if(empty($value['borough_name']) || trimall($value['borough_name'])==""){
				unset($this->house_info[$key]);
				continue;
			}
			//房屋面积为空直接扔掉
			if(trimall($value['house_totalarea'])<0){
				unset($this->house_info[$key]);
				continue;
			}
			//卧室个数小于0扔掉
			if(trimall($value['house_room'])<0){
				unset($this->house_info[$key]);
				continue;
			}
			//总楼层小于0扔掉
			if(trimall($value['house_topfloor'])<0){
				unset($this->house_info[$key]);
				continue;
			}

		    //出租价格小于等于0扔掉
			if(trimall($value['house_price'])<=0){
				unset($this->house_info[$key]);
				continue;
			}			
			//厕所如果为空的话 默认值为1
			if(empty($value['house_toilet']) || $value['house_toilet']==""){
				$this->house_info[$key]['house_toilet']=1;
			}
			//对官网电话做统一处理
			if(strstr($value['owner_phone'],"-")){
				$this->house_info[$key]['owner_phone'] = str_replace("-","",$value['owner_phone']);
			}
			if(strstr($value['owner_phone'],"转")){
				$this->house_info[$key]['owner_phone'] = str_replace("转", ",",$value['owner_phone']);
			}
			//查询source==10的房源，如果手机号码在broker_check表中，则unset掉
			if( $value['source']==10 ){
				$phone = M('broker_check')->where(array('phone'=>$value['owner_phone']))->find();
				if($phone){
					unset($this->house_info[$key]);
				}	
			}
		}
	}
	/*
	业主房源，小区，城区，商圈 不能都为空
	*/
	protected function _hadleFieldsPersonal(){
		foreach($this->house_info as $key=>$value){
			//小区名不能为空，为空直接扔掉
			if((empty($value['borough_name']) || trimall($value['borough_name'])=="")&&(empty($value['cityarea_id']) || trimall($value['cityarea_id'])=="") && (empty($value['cityarea2_id']) || trimall($value['cityarea2_id'])=="") ){
				unset($this->house_info[$key]);
				continue;
			}
		}
	}
	/*
	 * 经济人处理
	 */
 	protected function	_handleJjr(){
 		foreach($this->house_info as $k=>$v){
 			$phone = $v['owner_phone'];
 			$flag = preg_match("/1(3[0-9]|4[5|7]|5[0-9]|7[6|7|8]|8[0-9])[0-9]{8}/", $phone) || preg_match("/170[0-9][0-9]{7}/", $phone);
 			if($flag){
 				$member = array();
 				if($v['source'] == 10){
 					$member['role_type'] = 1;
 				}else{
 					$member['role_type'] = 2;
 				}
 				$member['username'] = $v['owner_phone'];
 				$member['real_name'] = $v['owner_name'];
 				$member['city_id'] = 1;
 				$member['add_time'] = time();
 				//插入成功会返回id
 				$id = M('member')->add($member);
 				if($id >= 1){
 					$data['user_id'] = $id;
 					$data['code'] = 0;
 					$data['status'] = 1;
 					$data['createtime'] = date('Y-m-d H:i:s');
 					$code_id = M('code')->add($data);
 					if( $code_id == false){
 						LOG::write(M()->getLastSql(), INFO);
 					}
 				}else{
 					LOG::write(M()->getLastSql(), INFO);
 				}
 				
 				
 			}
 		}
 	}
	
	
	/*
	 * 是否为下架房源
	 *   */
	protected function _hadleSourceUrl(){
		$info_temp = array ();
		foreach ( $this->house_info as $key => $value ) {
			//统计传递过来数据的总量
			$this->num_all_repeat+=1;
			// 如果抓到重复的url不入库，不一定，如果价格有变化需要更新数据库
			$start = microtime ();
			
			$url = $this->house_sell_del->where ( "source_url = '" . $value ['source_url'] . "'" )->find ();
				

			if ($url) { // 如果数据库中有这个值，判断is_fill的值
				$end = microtime ();
				Log::$format = '[ Y-m-d H:i:s ]';
				Log::write ( "【" . "source_url 在  house_sell_delete 验证重复" . "】【" . $value ['source_url'] . "】耗时" . ($end - $start), 'DEBUG' );
				//统计重复数据总量
				$this->num_all_repeat+=1;
				Log::$format = '[ Y-m-d H:i:s ]';
				Log::write ( "【" . "House_Sell_Del" . "】【" . $value ['source_url'] . "】房源存在", 'DEBUG' );
			} else {
				$info_temp [] = $value;
			}
		}
		$this->house_info=$info_temp;
	
	}
		
	/**
	 * 归一化
	 * 
	 */
	public function _normalization(){
		$this->_mergeOne();
	}
	/**
	 * 房源信息判重
	 * 个性化判重，继承此父类，重写该方法
	 */
	protected function _handleSellExists(){
		$info_temp = array ();
		foreach ( $this->house_info as $key => $value ) {
			//统计传递过来数据的总量
			$this->num_all_repeat+=1;
			// 如果抓到重复的url不入库，不一定，如果价格有变化需要更新数据库
			$start = microtime ();
			
			$url = $this->house_sell_gov->where ( "source_url = '" . $value ['source_url'] . "'" )->find ();
				

			if ($url) { // 如果数据库中有这个值，判断is_fill的值
				$end = microtime ();
				Log::$format = '[ Y-m-d H:i:s ]';
				Log::write ( "【" . "URL验证重复" . "】【" . $value ['source_url'] . "】耗时" . ($end - $start), 'DEBUG' );
				//统计重复数据总量
				$this->num_all_repeat+=1;
				Log::$format = '[ Y-m-d H:i:s ]';
				Log::write ( "【" . "House_sell_Exist" . "】【" . $value ['source_url'] . "】房源存在", 'DEBUG' );
			} else {
				$info_temp [] = $value;
			}
		}
		$this->house_info=$info_temp;
	}
	/*
	 * 优房特卖去重
	 */
	protected function _handleYoufangExists(){
		$info_temp = array ();
		foreach ( $this->house_info as $key => $value ) {
			//统计传递过来数据的总量
			$this->num_all_repeat+=1;
			// 如果抓到重复的url不入库，不一定，如果价格有变化需要更新数据库
			$start = microtime ();
			
			$url = M('house_sell_fangzhu')->where ( "source_url = '" . $value ['source_url'] . "'" )->find ();
				

			if ($url) { // 如果数据库中有这个值，判断is_fill的值
				$end = microtime ();
				Log::$format = '[ Y-m-d H:i:s ]';
				Log::write ( "【" . "URL验证重复" . "】【" . $value ['source_url'] . "】耗时" . ($end - $start), 'DEBUG' );
				//统计重复数据总量
				$this->num_all_repeat+=1;
				Log::$format = '[ Y-m-d H:i:s ]';
				Log::write ( "【" . "House_sell_Exist" . "】【" . $value ['source_url'] . "】房源存在", 'DEBUG' );
			} else {
				$info_temp [] = $value;
			}
		}
		$this->house_info=$info_temp;
	}
	
	
	/**
	 * 批量插入二手房数据库
	 * $is_youfang 判断是不是优房特卖房源
	 */
	protected function _bathInsertAllSell($is_youfang=false){
	    //dump($this->house_info);die;
		$this->house_sell_gov->addAll($this->house_info);
		if($is_youfang){
			M('house_sell_fangzhu')->addAll($this->house_info);
		}
		$this->time_all_end=microtime();
		Log::$format = '[ Y-m-d H:i:s ]';
		Log::write($this->house_sell_gov->getLastSql(),'DEBUG');
		Log::write("【"."TimeSum"."】【".($this->num_all-$this->num_all_repeat)."】条数据采集完毕".",耗时【".($this->time_all_end-$this->time_all_start)."】",'DEBUG');
		
	}
	
	/* 
	 * 一条一条写入house_sell_gov（因为source_url为唯一索引）
	 *  */
	protected function _aloneInsertGov(){
	    foreach ($this->house_info as $key=>$value){
	        $this->house_sell_gov->add($value);
	    }
	}
	
	
	/*
	 * 一条一条写入house_sell_fangzhu（因为source_url为唯一索引）
	 *  */
	protected function _aloneInsertFangzhu(){
	    foreach ($this->house_info as $key=>$value){
	        $this->house_sell_fangzhu->add($value);
	    }
	}
	
	
	
	protected function _bathInsertAllYoufang(){
		M('house_sell_fangzhu')->addAll($this->house_info);
		$this->time_all_end=microtime();
		Log::$format = '[ Y-m-d H:i:s ]';
		Log::write(M()->getLastSql(),'DEBUG');
		Log::write("【"."TimeSum"."】【".($this->num_all-$this->num_all_repeat)."】条数据采集完毕".",耗时【".($this->time_all_end-$this->time_all_start)."】",'DEBUG');
	
	}
	
	/**
	 * 处理小区信息
	 */
	protected function _handleBorouogh(){
	    $arr_temp_info=array();
	    foreach($this->house_info as $house_info_key=>$info){
	        $borough_name = $info ['borough_name'];
	        $cityarea_id = $info ['cityarea_id'];
	        $cityarea2_id = $info ['cityarea2_id'];
	        // 城区
	        $info ['cityarea_id'] = $this->cityarea_item->where ( "name = '" . $cityarea_id . "'" )->getField ( 'id' );
	        // 商圈
	        $info ['cityarea2_id'] = $this->cityarea_item->where ( "name = '" . $cityarea2_id . "'" )->getField ( 'id' );
	        // 小区名
	        $borough_info = $this->borough->where ( "borough_name = '" . $borough_name . "'" )->find ();
	        if ($borough_info == '') {
	            //小区表里没有，去查小区别名表
	            $borough_info = $this->borough_byname->where ( "borough_byname = '" . $borough_name . "'" )->find ();
	            //小区别名表里有 就用小区别名表中的小区id,小区名
	            if ($borough_info ['borough_id'] && $borough_info ['borough_name']) {
	                $info ['borough_id'] = $borough_info ['borough_id'];
	                $info ['borough_name'] = $borough_info ['borough_name'];
	                	
	                if (empty ( $info ['cityarea_id'] ))
	                    $info ['cityarea_id'] = $borough_info ['cityarea_id'];
	                if (empty ( $info ['cityarea2_id'] ))
	                    $info ['cityarea2_id'] = $borough_info ['cityarea2_id'];
	            } else {
	                //小区别名表中没有，如果当前抓到的小区名不为空  把当前抓到的小区名创建为新小区
	                if (! empty ( $borough_name )) {
	                    $tmpinfo = array (
	                        'borough_name' => $borough_name,
	                        'cityarea_id' => $info ['cityarea_id'],
	                        'cityarea2_id' => $info ['cityarea2_id'],
	                        'created' => time (),
	                        'borough_letter' => getFirstPY ( $info ['borough_name'] ),
	                        'is_checked' => 0
	                    );
	                }elseif(! empty ($info ['cityarea2_id'])){
	                    //如果当前抓到的商圈不为空，查表中是否有以当前商圈命名的小区
	                    $cityarea2_borough = $this->borough->where ( "borough_name = '" . $cityarea2_id. "小区'" )->find();
	                    if($cityarea2_borough){
	                        $info ['borough_id'] = $cityarea2_borough ['id'];
	                        $info ['borough_name'] = $cityarea2_borough ['borough_name'];
	                    }else{
	                        //如果没有新建一个以当前抓到的商圈命名的小区
	                        $tmpinfo = array (
	                            'borough_name' => $cityarea2_id.'小区',
	                            'cityarea_id' => $info ['cityarea_id'],
	                            'cityarea2_id' => $info ['cityarea2_id'],
	                            'created' => time (),
	                            'borough_letter' => getFirstPY ( $info ['borough_name'] ),
	                            'is_checked' => 0
	                        );
	                    }
	                }else{
	                    //如果当前抓到的商圈不为空，查表中是否有以当前城区命名的小区
	                    $cityarea_borough = $this->borough->where ( "borough_name = '" . $cityarea_id. "小区'" )->find();
	                    if($cityarea_borough){
	                        $info ['borough_id'] = $cityarea_borough ['id'];
	                        $info ['borough_name'] = $cityarea_borough ['borough_name'];
	                    }else{
	                        //如果没有新建一个以当前抓到的城区命名的小区
	                        $tmpinfo = array (
	                            'borough_name' => $cityarea_id.'小区',
	                            'cityarea_id' => $info ['cityarea_id'],
	                            'cityarea2_id' => $info ['cityarea2_id'],
	                            'created' => time (),
	                            'borough_letter' => getFirstPY ( $info ['borough_name'] ),
	                            'is_checked' => 0
	                        );
	                    }
	                }
	                $resid = $this->borough->data ( $tmpinfo )->add ();
	                $info ['borough_id'] = $resid;
	                $info ['borough_name'] = $tmpinfo ['borough_name'];
	
	            }
	        } else {
	            //小区表中有小区信息，就和小区信息
	            $info ['borough_id'] = $borough_info ['id'];
	            $info ['borough_name'] = $borough_info ['borough_name'];
	
	            if (empty ( $info ['cityarea_id'] ))
	                $info ['cityarea_id'] = $borough_info ['cityarea_id'];
	            if (empty ( $info ['cityarea2_id'] ))
	                $info ['cityarea2_id'] = $borough_info ['cityarea2_id'];
	        }
	        $arr_temp_info[]=$info;
	
	    }
	
	    $this->house_info=$arr_temp_info;
	
	}
	
	/**
	 * 格式化输出房源数据信息
	 */
	protected function __toString(){
		var_dump($this->house_info);
	}
}