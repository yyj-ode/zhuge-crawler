<?php
/**
 *测试专用
 */
#$header("Content-type: text/html; charset=utf-8");
Class washData{
    // 数据库配置
    private $dbconf = [
        'host' => '101.200.81.152',
        'user' => 'zhuge',
        'post' => '3307',
        'pass' => 'zhuge1116',
        'dbname' => 'spider',
        'code' => 'utf8'
    ];

    // 数据库对象
    private $db;

    // 高德秘钥key
    private $gdApiKey = "6d19379a3278a23c7e5d7d470b875a93";

    private  $num=0;
    private  $totleNum = 0;
    private $totleSucc=0;
    private $startTime;

    public function __construct(){
        $this->db = mysql_connect($this -> dbconf['host'].':'.$this->dbconf['post'], $this -> dbconf['user'], $this -> dbconf['pass']) or die('数据库服务器连接错误:' . mysql_error());
        mysql_select_db($this -> dbconf['dbname']);
        mysql_query("set names '".$this -> dbconf['code']."'");
    }

    public function test(){
    	$sql = 'select * from house_sell_gov limit 10';
    	$result = mysql_query($sql);
    	$row = mysql_fetch_assoc($result);
    	foreach($row as $key => $value){
			if($key != 'id'){
				$fields .= '`'.$key.'`, ';
			}
		}
		echo $fields;


    	die;
	    $fields = '';
		
    }
}

$washData = new washData();
$washData->test();