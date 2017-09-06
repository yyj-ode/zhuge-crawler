#!/bin/bash

phpcmd=php
chmod 777 -R ./*

case "$1" in
   #爬取器
    Grab)
		$phpcmd ./server.php
		echo "######################### 服务端启动成功"
    ;;
	#抓取器
    Crawling)
		$phpcmd ./detail_server.php
		echo "######################### 服务端启动成功"
    ;;
 	#ETL清洗
    Etl)
		$phpcmd ./etl_server.php
		echo "######################### 服务端启动成功"
	;;

    *)
        echo '参数可选为:{Grab|Crawling|Etl}';
        exit 1;
esac






