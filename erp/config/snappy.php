<?php

return array(


    'pdf' => array(
        'enabled' => true,
        'binary' => base_path().'/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
        'timeout' => 3600,
        'options' => array(),
        'env'     => array(),
        // 'temp-folder' => "/home/erpcloud-live/htdocs/html/tmp"
    ),
    'image' => array(
        'enabled' => true,
        'binary' => base_path().'/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
        'timeout' => false,
        'options' => array(),
        'env'     => array(),
        // 'temp-folder' => "/home/erpcloud-live/htdocs/html/tmp"
    ),


);
