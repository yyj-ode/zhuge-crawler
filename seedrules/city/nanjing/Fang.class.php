<?php
namespace nanjing;
/**
 * @description 南京房天下二手房
 * @classname 南京房天下(k-ok)
 */

class Fang  extends \baserule\Fang
{
    public $host = 'http://esf.nanjing.fang.com';
    public function house_page()
    {
        $queryData = $this -> getHtmlQueryData($this ->host.'/house/a21/');
        $urlarr=array();
        $error = $totalNum = [];
        foreach($queryData['dis'] as $dv){
            $disUrl = $this ->host .'/'. $dv .'/a21';
            // var_dump($disUrl);die;
            $queryData = $this -> getHtmlQueryData($disUrl.'/');
            // 内容为空
            if(!$queryData){
                $error['empty'][] = $disUrl;
                continue;
            }

            // 商圈
            if($queryData['totalnum'] > 3000 ){
                foreach($queryData['plate'] as $plate){
                    $plateQueryUrl =  $this ->host .'/'. $dv. '-' . $plate .'/a21';
                    $plateQuery = $this -> getHtmlQueryData($plateQueryUrl.'/');

                    // 内容为空
                    if(!$plateQuery){
                        $error['empty'][] = $plateQueryUrl;
                        continue;
                    }

                    // 面积
                    if($plateQuery['totalnum'] > 3000){
                        foreach($queryData['mianji'] as $mianji){
                            $mianjiUrl =  $this ->host .'/'. $dv. '-' . $plate ."/a21-{$mianji}";
                            $plateQuery = $this -> getHtmlQueryData($mianjiUrl.'/');

                            // 内容为空
                            if(!$plateQuery){
                                $error['empty'][] = $plateQueryUrl;
                                continue;
                            }

                            $page = ceil($plateQuery['totalnum'] / 30);
                            for($i=1; $i<=$page; $i++){
                                $urlarr[] = $mianjiUrl."-i3{$i}/";
                                // var_dump($urlarr);die;
                            }
                        }
                    }else{
                        $totalNum[] = $plateQuery['totalnum'];
                        $page = ceil($plateQuery['totalnum'] / 30);

                        for($i=1; $i<=$page; $i++){
                            $urlarr[] = $plateQueryUrl."-i3{$i}/";
                        }
                    }

                }
            }
            else{ // 城区
                $totalNum[] = $queryData['totalnum'];
                $page = ceil($queryData['totalnum'] / 30);
                for($i=1; $i<=$page; $i++){
                    $urlarr[] = $disUrl."-i3{$i}/";
                }
            }
        }
        // var_dump($urlarr);die;
        writeLog( 'Fang'.__FUNCTION__, ['error_url'=>$error, 'totalNum' => $totalNum], $this -> log);
        return $urlarr;
    }

}
