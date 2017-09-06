#!/bin/sh

currtime=`date +%s`;
cat ./serverList.txt | while read line
do
        redis=`/usr/local/redis/src/redis-cli -n 7 get $line`;
        echo $line;
        echo $redis;
        if [ ! -n "$redis" ];then
                continue;
        fi

        IFS="@";
        arr=($redis);
        if [ ! -n "${arr[2]}" ];then
                continue;
        fi

        difftime=$[ 10#$currtime - 10#${arr[0]} - 10#${arr[1]} ];
        if [ $difftime -ge 0 ];
        then
                eval ${arr[2]};
        fi

done