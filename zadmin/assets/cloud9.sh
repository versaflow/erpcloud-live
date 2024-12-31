#!/bin/bash
get_c9_pid(){
    echo $(ps ax | grep server.js | grep -v grep | awk '{print $1}')
}
 
start() {
    local c9_pid=$(get_c9_pid)
    if [ -z "$c9_pid" ];
    then
	if [ $? -eq 0 ];
        then
    	    node /home/_admin/cloud9/server.js -l 0.0.0.0 -p 88 -a admin:nimda123 -w /home --collab >/dev/null 2>&1 &
            echo "c9 started"
	fi
    else
	echo "already running"
    fi
}

stop() {
    local c9_pid=$(get_c9_pid)
    echo "Stopping $c9_pid"
    if [ ! -z "$c9_pid" ]; 
    then    
        kill $c9_pid
        if [ $? -eq 0 ]; 
        then 
            echo "killed"
        else
            echo "cannot kill"   
       fi
    else
        echo "Nothing to stop"
    fi
}
status() {
    local c9_pid=$(get_c9_pid)
    if [ -z "$c9_pid" ];
    then
	echo "process is not running"
    else
	echo "process is running"
    fi 
}

case "$1" in 
    start)
           start
           ;;
    stop)
           stop
           ;;
    status)
           status
           ;;
    restart)
	   stop
	   start
	   ;; 
    *)
           echo "Usage: $0 {start|stop|status|restart}"
esac
exit 0 
