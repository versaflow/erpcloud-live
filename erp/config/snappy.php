<?php

return [

    'pdf' => [
        'enabled' => true,
        'binary' => base_path().'/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
        'timeout' => 3600,
        'options' => [],
        'env' => [],
        // 'temp-folder' => "/home/erpcloud-live/htdocs/html/tmp"
    ],
    'image' => [
        'enabled' => true,
        'binary' => base_path().'/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
        'timeout' => false,
        'options' => [],
        'env' => [],
        // 'temp-folder' => "/home/erpcloud-live/htdocs/html/tmp"
    ],

];
