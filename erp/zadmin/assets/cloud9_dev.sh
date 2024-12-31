#!/bin/bash
get_c9_pid(){
    echo $(echo $(ps -ax | grep dev321 | awk '{print $1}') | awk '{print $2}')
}

start() {
   
    	   node /home/_admin/c9/server.js -l 0.0.0.0 -p 89 -a admin:dev321 -w /home/teleclou >/dev/null 2>&1 &
            echo "c9 started"

}

stop() {
    local c9_pid=$(get_c9_pid)
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


