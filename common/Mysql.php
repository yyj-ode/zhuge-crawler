<?php
/**
 * mysql嘞
 */
#$header("Content-type: text/html; charset=utf-8");
Class washData{
    // 数据库配置
    private $dbconf = [
        'host' => '101.200.81.152',
        'user' => 'zhugef_online',
        'post' => '3307',
        'pass' => 'emd3ZW56*$FuYmFanVuZmVuZw%db',
        'dbname' => 'spider',
        'code' => 'utf8'
    ];
    // 数据库对象
    private $db;
    private $startTime;
    
    public function __construct($config = null){
        if(!empty($config)){
                $this->dbconf = $config;
        }
        $this->db = mysql_connect($this->dbconf['host'].':'.$this->dbconf['post'], $this->dbconf['user'], $this->dbconf['pass']) or die('数据库服务器连接错误:' . mysql_error());
        mysql_select_db($this->dbconf['dbname']);
        mysql_query("set names '".$this->dbconf['code']."'");
        $this->startTime = time();
//        return $this->getSellGov();
    }
    
    public function getData($sql = ''){
        if(empty($sql)){ 
            $selldata = $this->getSellGov();
//            $rentdata = $this->getRentGov();
//            $hezudata = $this->getHezuGov();
            $data = array_merge((array)$selldata, (array)$rentdata);
            $data = array_merge((array)$data, (array)$hezudata);
            return [$data, true];
        }else{
            if(is_string($sql)){
                $data = $this->getSelect($sql);
            }else{
                foreach((array)$sql as $key => $value){
                    $data = array_merge((array)$data, (array)$this->getSelect($value));
                }
            }
            return [$data, false];
        }
    }

    public function getSellGov(){
//            $sql = "SELECT source_url,source,source_name FROM house_sell_gov WHERE  house_pic_unit = '' or source = 1";
            $sql = "SELECT source_url,source,source_name FROM house_sell_gov";
            return $this->getSelect($sql);
    }

    public function getHezuGov(){
            $sql = "SELECT source_url,source,source_name FROM house_hezu_gov WHERE house_pic_unit = ''";
            return $this->getSelect($sql);
    }

    public function getRentGov(){
            $sql = "SELECT source_url,source,source_name FROM house_rent_gov WHERE house_pic_unit = ''";
            return $this->getSelect($sql);
    }

    public function getQuery($sql = ''){
            return $this->getSelect($sql);
    }

    public function getSelect($sql = ''){
            if(!empty($sql)){
                    $result = mysql_query($sql);
                    $data = [];
                    while($row = mysql_fetch_assoc($result)){
                            $data[] = $row;
                    }
                    return $data;
            }
            return false;
    }
    
    public function delBadData($ids = '', $tablename = ''){
        if(!empty($ids) && !empty($tablename)){
            $ids = implode(',', $ids);
            $sql = 'delete from '.$tablename.' where id in ('.$ids.')';
            mysql_query($sql);
        }
    }
	

}




//$washData = new  washData();
//$washData -> gdApi();
//
//
//$source = [
//'链家地产' => '1',
//'中原地产' => '2',
//'21世纪（酷房网）' => '3',
//'我爱我家' => '4',
//'Q房网' => '5',
//'爱屋吉屋' => '6',
//'麦田' => '7',
//'丽兹行' => '8',
//'搜房网' => '9',
//
////所有个人房源的source都是10,用source_owner字段区分渠道来源
//'搜房整租' => '10',
//'搜房合租' => '10',
//'58同城' => '10',
//'房主儿' => '10',
//
//'自如友家' => '11',
//'家家顺' => '12',
//'丁丁租房' => '13',
//'房多多' => '14',
//'寓见公寓' => '15',
//'快有家' => '16',
//'中诚致地产' => '17',
//'汉宇地产' => '18',
//'美联物业' => '19',
//'德祐地产' => '20',
//'蘑菇公寓' => '21',
//'万福地产' => '22',
//'青客公寓' => '23',
//'安个家' => '24',
//'安居客' => '25',
//'百姓网' => '26',
//'赶集网' => '27',
//'优办' => '32',
//'点点租' => '33',
//'好租' => '34',
//'远行地产' => '35',
//'美丽屋' => '39',
//'悟空找房' => '40',
//'蛋壳公寓' => '41',
//'乐租网' => '42',
//];
//
//$source_owner = [
//'房主儿' => '1',
//'搜房整租' =>'3',
//'搜房合租' =>'3',
//'58同城' => '5'
//'Hi租房' => '6'
//]
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    