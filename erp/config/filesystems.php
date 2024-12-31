<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
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
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'porting_input' => [
            'driver' => 'local',
            'root' => storage_path('porting_input'),
            'visibility' => 'public',
            'permPublic' => 0755,
            'directoryPerm' => 0755,
        ],

        'porting_data_mnp' => [
            'driver' => 'local',
            'root' => storage_path('porting_data/mnp'),
            'visibility' => 'public',
            'permPublic' => 0755,
            'directoryPerm' => 0755,
        ],

        'porting_data_gnp' => [
            'driver' => 'local',
            'root' => storage_path('porting_data/gnp'),
            'visibility' => 'public',
            'permPublic' => 0755,
            'directoryPerm' => 0755,
        ],

        'porting_ftp_gnp' => [
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

        'porting_ftp_mnp' => [
            'driver' => 'ftp',
            'host' => 'ftps.porting.co.za',
            'username' => 'coincorpmnp',
            'password' => 'Coincorp786',
            'port' => 10021,
            'ssl' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'saml' => [
            'driver' => 'local',
            'root' => storage_path().'/saml',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
