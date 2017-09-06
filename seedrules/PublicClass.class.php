<?php
/**
                        _ooOoo_
                       o8888888o
                       88" . "88
                       (| -_- |)
                       O\  =  /O
                    ____/`---'\____
                  .'  \\|     |//  `.
                 /  \\|||  :  |||//  \
                /  _||||| -:- |||||-  \
                |   | \\\  -  /// |   |
                | \_|  ''\---/''  |   |
                \  .-\__  `-`  ___/-. /
              ___`. .'  /--.--\  `. . __
           ."" '<  `.___\_<|>_/___.'  >'"".
          | | :  `- \`.;`\ _ /`;.`/ - ` : | |
          \  \ `-.   \_ __\ /__ _/   .-` /  /
     ======`-.____`-.___\_____/___.-`____.-'======
                        `=---='
     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
              佛祖保佑       永无BUG
**/
namespace city;
 abstract class PublicClass
{
     private $path;
     public function __construct($path = ''){
         $this->path = $path;
     }
     /**
      * 获取页数
      * @param string $URL
      * @param $cli
      */
     abstract public function house_page();

	/*
	 * 获取列表页
	 */
	abstract public function house_list($url);
		
    /*
     * 获取详情
     */
     abstract public function house_detail($source_url);

     /**
      * @return string
      */
     protected function getSource(){
         return (!empty($this->path) ? $this->path.'/' : '').get_class($this);
     }

     /**
      * @param $cli
      * @param $urlarr
      */
     protected function send($cli,$urlarr){
         while(true){
             if(checkThreadNum('Grab') < 32) {
                 echo date('Y-m-d H:i:s').'URL: '.$urlarr['source_url'].'--SOURCE: '.$urlarr['source']. "\r\n";
                 $cli->send(json_encode($urlarr) . "\r\n");
                 break;
             }
         }
     }


     /**
      * 获取定义内容
      * @param string $url
      * @param array $rules
      * @return bool
      */
     protected function queryList($url = '', $rules = []){
        if(!empty($url) && !empty($rules) && is_array($rules)){
            $data = \QL\QueryList::Query($url,$rules)->data;
            return $data;
        }
        return false;
     }

     /**
      * 获取远程地址内容
      * @param string $url
      * @param string $data
      * ==================================
      * get
      * post
      * referrer
      * user_agent
      * handle  json
      * ==================================
      * @return bool
      */
     protected function getUrlContent($url = '', $data = ''){
        if(!empty($url)){
            $attribute = ['target' => $url];
            if(!empty($data)){
                if(isset($data['get'])){
                    $attribute['method'] = 'GET';
                    $attribute['params'] = $data['get'];
                }elseif(isset($data['post'])){
                    $attribute['method'] = 'POST';
                    $attribute['params'] = $data['post'];
                }
                $attribute['referrer'] = isset($data['referrer']) ? $data['referrer'] : '';
                $attribute['user_agent'] = isset($data['user_agent']) ? $data['user_agent'] : '';
            }
            $result = \QL\QueryList::run(
                'Request', $attribute
            )->getHtml(0);
            if(isset($data['handle']) && $data['handle'] == 'array'){
                if(!is_array($result)){
                    $result = analyJson($result);
                }
            }
            return $result;
        }
        return false;
     }
     
}