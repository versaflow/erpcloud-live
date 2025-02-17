<?php

function schedule_check_partition_sizes(){
    $cmd = 'df -h';
    $result = Erp::ssh('pbx.cloudtools.co.za', 'root', 'Ahmed777', $cmd); 
}