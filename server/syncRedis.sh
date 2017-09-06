#!/bin/sh

while [ true ]; do
/bin/sleep 10
echo `nohup php syncRedis.php &`
done
