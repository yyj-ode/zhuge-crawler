<?php namespace tianjin;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/29
 * Time: 1:46
 */
    Class FangPersonal extends \baserule\Fang{

        /**
         * 获取页数
         * @param string $URL
         * @param $cli
         */
        public $PRE_URL = 'http://esf.tj.fang.com/house-';
        private $current_url = '';

        public function house_page()
        {
            $dis = array(
                'a037' => '和平',
                'a041' => '南开',
                'a043' => '河西',
                'a044' => '河北',
                'a042' => '河东',
                'a046' => '红桥',
                'a038' => '西青',
                'a039' => '北辰',
                'a049' => '东丽',
                'a045' => '津南',
                'a047' => '塘沽',
                'a055' => '开发区',
                'a040' => '大港',
                'a052' => '武清',
                'a0615' => '宝坻',
                'a054' => '静海',
                'a051' => '蓟州',
                'a0614' => '汉沽',
                'a053' => '宁河',
            );
            //价格
            $p = array(
                'd280' => '80万以下',
                'c280-d2100' => '80-100万',
                'c2100-d2150' => '100-150万',
                'c2150-d2200' => '150-200万',
                'c2200-d2300' => '200-300万',
                'c2300-d2500' => '300-500万',
                'c2500' => '500万以上',
            );
            $urlarr = [];
            foreach ($dis as $k1 => $v1) {
                foreach ($p as $k2 => $v2) {
                    $this->current_url = $this->PRE_URL . $k1 . '/' .'a21'.'-'.$k2;
                    $html = getSnoopy($this->current_url);
                    preg_match('/txt\">共(\d+?)页/u',$html,$page);
                    $maxPage = $page[1];
                    for ($page=1; $page<=$maxPage; $page++){
                        $urlarr[]=$this->current_url . '-i3' . $page;
                    }

                }
            }
            return $urlarr;
        }
        public function callNewData(){;
            $data = [];
            for($i = 1; $i <= 100; $i++){
                $data[] = 'http://esf.tj.fang.com' . "/house/a21-h316-i3{$i}/";
            }
            return $data;
        }

    }