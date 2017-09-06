<?php
class Seed{

    public function callSource($source = '', $func = '', $url = '', $cli = ''){
        if(!empty($source) && !empty($func)){
            $filepath = '../seedrules/'.$source.'.class.php';
            if(file_exists($filepath)){
                include_once $filepath;
                $sourceclass = new $source();
                if(!empty($cli)){
                    return $sourceclass->$func($url, $cli);
                }else{
                    return $sourceclass->$func($url);
                }
            }
        }
    }
}