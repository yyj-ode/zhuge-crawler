#!/bin/bash

phpcmd=php
codePath=/alidata/www/zhuge-crawler
chmod 777 -R ./*

case "$1" in
   #爬取器
    Grab)
    	cd ${codePath}/server
		$phpcmd server.php
		echo "######################### 服务端启动成功"
		sleep 3
		cd ${codePath}/client
		$phpcmd client.php
		echo "######################### 客户端启动成功"
    ;;
	#抓取器
    Crawling)
    	cd ${codePath}/server
		$phpcmd detail_server.php
		echo "######################### 服务端启动成功"
		sleep 3
		cd ${codePath}/client
		$phpcmd detail_client.php
		echo "######################### 客户端启动成功"
    ;;
 	#ETL清洗
    Etl)
	    cd ${codePath}/server
		$phpcmd etl_server.php
		echo "######################### 服务端启动成功"
		sleep 3
		cd ${codePath}/client
		$phpcmd etl_client.php
		echo "######################### 客户端启动成功"
	;;

    *)
        echo '参数可选为:{Grab|Crawling|Etl}';
        exit 1;
esac






