<?php namespace shenzhen;
/**
 * @description 深圳58个人二手房抓取规则
 * @classname 深圳 =======《58个人》=======深圳
 */


Class Five8Personal extends \baserule\Five8Personal
{
    public $city_name = 'sz';
    public $dis = array('luohu', 'futian', 'nanshan', 'yantian', 'baoan', 'longgang', 'buji', 'pingshanxinqu', 'guangmingxinqu', 'szlhxq', 'dapengxq', 'shenzhenzhoubian'
    );
}