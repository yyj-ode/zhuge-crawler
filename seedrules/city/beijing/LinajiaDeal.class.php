<?php namespace beijing;
/**
 * @description 北京链家成交
 * @classname 北京链家成交
 */

Class LianjiaDeal extends \city\PublicClass{
	
    /**
     * 获取列表分页
     */
    public function house_page(){
        
    }

    /*
     * 获取列表页
    * */
    Public function house_list($url = ''){

    }

    /*
     * 获取详情
    */
    public function house_detail($source_url){
        $house_info = [];
        $house_info['borough_name'] = ''; //小区名
        $house_info['finish_time'] = ''; //成交时间
        $house_info['finish_price'] = ''; //成交价格
        $house_info['house_area'] = ''; //房屋面积
        $house_info['house_room'] = ''; //室\\居
        $house_info['house_hall'] = ''; //厅
        $house_info['house_toward'] = ''; //朝向
        $house_info['house_floor'] = ''; //楼层
        $house_info['house_topfloor'] = ''; //总楼层
        $house_info['broker_name'] = ''; //经纪人姓名
        $house_info['company_name'] = ''; //成交经纪公司名
        $house_info['building_number'] = ''; //楼号
    }
}