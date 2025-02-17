<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "ftp", "s3", "rackspace"
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('uploads'),
        ],

        'exports' => [
            'driver' => 'local',
            'root' => storage_path('exports'),
        ],

        'pbx_recordings' => [
            'driver' => 'local',
            'root' => storage_path('pbx_recordings'),
        ],

        'pbx_logs' => [
            'driver' => 'local',
            'root' => storage_path('pbx_logs'),
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => public_path().'/uploads',
        ],

        'attachments' => [
            'driver' => 'local',
            'root' => public_path('attachments/'),
        ],
        'pricing_exports' => [
            'driver' => 'local',
            'root' => public_path().'/uploads',
        ],

        'downloads' => [
            'driver' => 'local',
            'root' => public_path().'/downloads',
        ],

        'templates' => [
            'driver' => 'local',
            'root' => storage_path('templates'),
        ],

        'api' => [
            'driver' => 'local',
            'root' => storage_path('api'),
        ],

        'event_logs' => [
            'driver' => 'local',
            'root' => storage_path('event_logs'),
        ],

        'debit_orders' => [
            'driver' => 'local',
            'root' => storage_path('debit_orders'),
        ],

        'reports' => [
            'driver' => 'local',
            'root' => storage_path('reports'),
        ],

        'sageone' => [
            'driver' => 'local',
            'root' => storage_path('sageone'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
        ],

        'porting_input' => [
            'driver' => 'local',
            'root' => storage_path('porting_input'),
            'visibility' => 'public',
            'permPublic' => 0755,
            'directoryPerm' => 0755,
        ],

        'porting_data_gnp_source' => [
            'driver' => 'ftp',
            'host' => 'ftps.porting.co.za',
            'username' => 'coincorpgnp',
            'password' => 'Coincorp786',
            'port' => 10021,
            'root' => '',
            'passive' => true,
            'ssl' => true,
            'timeout' => 30,
        ],

        'porting_data_mnp_source' => [
            'driver' => 'ftp',
            'host' => 'ftps.porting.co.za',
            'username' => 'coincorpmnp',
            'password' => 'Coincorp786',
            'port' => 10021,
            'ssl' => true,
        ],

        // chmod 755 porting_data/
        // chmod 777 porting_data/* -Rf
        // chown root:erpcloud-live porting_data/ -Rf
        'porting_data_mnp_local' => [
            'driver' => 'local',
            'root' => storage_path('porting_data/mnp'),
            'visibility' => 'public',
            'permPublic' => 0755,
            'directoryPerm' => 0755,
        ],

        'porting_data_gnp_local' => [
            'driver' => 'local',
            'root' => storage_path('porting_data/gnp'),
            'visibility' => 'public',
            'permPublic' => 0755,
            'directoryPerm' => 0755,
        ],

        // 'porting_ftp_test' => [
        //     'driver' => 'ftp',
        //     'host' => 'ftps.porting.co.za',
        //     'username' => 'GNPtimestamp',
        //     'password' => 't1m3st9mp@gnp',
        //     'port' => 10021,
        //     'ssl' => true,
        // ],

        's3' => [
            'driver' => 's3',
            'key' => 'your-key',
            'secret' => 'your-secret',
            'region' => 'your-region',
            'bucket' => 'your-bucket',
        ],

        'saml' => [
            'driver' => 'local',
            'root' => storage_path().'/saml',
        ],
    ],
];
