#zhuge-house
  
诸葛数据采集系统（项目简介）
===================================
　　诸葛数据采集系统，房源数据。<br/>

# 系统简介


# 技术文档
1、神器无线 N16 12346789 
http://local.crawler.com/crawlerStart/index.php     
2、server 种子和内容 服务
server.php -》client client.php
detail_server.php ->client detail_client.php
grab_device.php 分布式部署 
syncRedis.php 神器往阿里云redis同步
3、指定渠道爬虫，common config.php。不同渠道并发不同，concurrentnums
4、重试三次，无时间间隔
5、统计报表，crawlerstart
6、etl，123.57.76.91，curl "http://127.0.0.1:8000/Etl/ETL/run?dbname=beijing&num=1000”
7、php 并发问题，进程可控性较差。任务调度。目标网站网速慢。
8、神器redis集群 代码层面逻辑 
9、redis连接池
10、神器 外网IP
11、神器 50M=》100M

## 安装

准备好环境：git , php , apache , mysql ...

`git clone git@git.oschina.net:zgzf/zhuge-house.git`
