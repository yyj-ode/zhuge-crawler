#!/bin/sh

while [ true ]; do
/bin/sleep 20
echo `nohup curl "http://www.crawler.com/server/grab_device.php?num=20" &`
done
