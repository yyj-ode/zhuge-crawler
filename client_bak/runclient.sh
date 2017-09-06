#!/bin/bash

phpcmd=php
chmod 777 -R ./*

case "$1" in
   #爬取器
    Grab)
		$phpcmd ./client.php
		echo "######################### 客户端启动成功"
    ;;
	#抓取器
    Crawling)
		$phpcmd ./detail_client.php
		echo "######################### 客户端启动成功"
    ;;
 	#ETL清洗
    Etl)
		$phpcmd ./etl_client.php
		echo "######################### 客户端启动成功"
	;;

    *)
        echo '参数可选为:{Grab|Crawling|Etl}';
        exit 1;
esac






