<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_OBJ,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */
    'connections' => [

        // MAIN CONNECTION - only used to get tenant connection details

        'system' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'telecloud'),
            'username' => env('DB_USERNAME', 'telecloud'),
            'password' => env('DB_PASSWORD', 'B3roPJdl1DoxgHpKdeN3'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'shop' => [
            'driver' => 'sqlite',
            'database' => database_path('shop.sqlite'),
            'prefix' => '',
        ],

        'website' => [
            'driver' => 'sqlite',
            'database' => database_path('website.sqlite'),
            'prefix' => '',
        ],

        'default' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'telecloud'),
            'username' => env('DB_USERNAME', 'telecloud'),
            'password' => env('DB_PASSWORD', 'B3roPJdl1DoxgHpKdeN3'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'core' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'telecloud'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'xf2XpsZ4Q7Gn2pOM'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'telecloud' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'telecloud'),
            'username' => env('DB_USERNAME', 'telecloud'),
            'password' => env('DB_PASSWORD', 'B3roPJdl1DoxgHpKdeN3'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        // 'communica' => [
        //     'driver' => 'mysql',
        //     'host' => env('DB_HOST', '127.0.0.1'),
        //     'port' => env('DB_PORT', '3306'),
        //     'database' => env('DB_DATABASE', 'communica'),
        //     'username' => env('DB_USERNAME', 'communica'),
        //     'password' => env('DB_PASSWORD', 'M5Cti3TlhodS0E3ltllm'),
        //     'charset' => 'utf8',
        //     'collation' => 'utf8_unicode_ci',
        //     'prefix' => '',
        //     'strict' => false,
        //     'engine' => null,
        // ],

        'eldooffice' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'eldooffice'),
            'username' => env('DB_USERNAME', 'eldooffice'),
            'password' => env('DB_PASSWORD', 'EHSHDohPs82eF65t1P2q'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'moviemagic' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'moviemagic'),
            'username' => env('DB_USERNAME', 'moviemagic'),
            'password' => env('DB_PASSWORD', 'WNx6S32hZVbCWhMqCUaK'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'ahmedo' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'ahmedo'),
            'username' => env('DB_USERNAME', 'ahmedo'),
            'password' => env('DB_PASSWORD', '34icZDyjCNEFA7wEmUus'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'cloudtelecoms_wordpress' => [
            'driver' => 'mysql',
            'host' => 'host2.cloudtools.co.za',
            'port' => '3306',
            'database' => 'da12_wp540',
            'username' => 'remote',
            'password' => 'Webmin@786',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'cloud_telecoms' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'telecloud'),
            'username' => env('DB_USERNAME', 'telecloud'),
            'password' => env('DB_PASSWORD', 'B3roPJdl1DoxgHpKdeN3'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'telecloud_wordpress' => [
            'driver' => 'mysql',
            'host' => 'host1.cloudtools.co.za',
            'port' => '3306',
            'database' => 'teleclou_wp',
            'username' => 'teleclou_wp',
            'password' => 'AddingJackalToneWork94',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'erpcloud_wordpress' => [
            'driver' => 'mysql',
            'host' => 'host1.cloudtools.co.za',
            'port' => '3306',
            'database' => 'erpcloud_wp',
            'username' => 'erpcloud_wp',
            'password' => 'SirredWidthInsectSac56',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'bulkhub_wordpress' => [
            'driver' => 'mysql',
            'host' => 'host1.cloudtools.co.za',
            'port' => '3306',
            'database' => 'bulkhubc_wp759',
            'username' => 'bulkhubc_wp759',
            'password' => 'T(!39lapS8',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        // 'hajj' => [
        //     'driver' => 'mysql',
        //     'host' => env('DB_HOST', '127.0.0.1'),
        //     'port' => env('DB_PORT', '3306'),
        //     'database' => env('DB_DATABASE', 'versaflo_hajj'),
        //     'username' => env('DB_USERNAME', 'versaflo_hajj'),
        //     'password' => env('DB_PASSWORD', 'B3roPJdl1DoxgHpKdeN3'),
        //     'charset' => 'utf8',
        //     'collation' => 'utf8_unicode_ci',
        //     'prefix' => '',
        //     'strict' => false,
        //     'engine' => null,
        // ],

        'justice' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'justice'),
            'username' => env('DB_USERNAME', 'justice'),
            'password' => env('DB_PASSWORD', 'nHaYcE54VuL1Zm2RaoVC'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'vehicledb' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'vehicledb'),
            'username' => env('DB_USERNAME', 'vehicledb'),
            'password' => env('DB_PASSWORD', 'INY1q5pa3ROCaMU80qT2'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        // 'erpcloud' => [
        //     'driver' => 'mysql',
        //     'host' => env('DB_HOST', '127.0.0.1'),
        //     'port' => env('DB_PORT', '3306'),
        //     'database' => env('DB_DATABASE', 'versaflo_erpcloud'),
        //     'username' => env('DB_USERNAME', 'versaflo_erpcloud'),
        //     'password' => env('DB_PASSWORD', 'B3roPJdl1DoxgHpKdeN3'),
        //     'charset' => 'utf8',
        //     'collation' => 'utf8_unicode_ci',
        //     'prefix' => '',
        //     'strict' => false,
        //     'engine' => null,
        // ],

        // EXTERNAL CONNECTIONS - START

        'homer' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'homer.cloudtelecoms.co.za'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'airbyte'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'pbx_erp' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '156.0.96.60'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'erp'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'pbx' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '156.0.96.60'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'cloudpbx'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'pbx_cdr' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '156.0.96.60'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'cdr'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'prefix' => '',
            'strict' => false,
        ],
        'pbx_porting' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '156.0.96.60'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'porting'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'prefix' => '',
            'strict' => false,
        ],

        'freeswitch' => [
            'driver' => 'mysql',
            'host' => '156.0.96.60',
            'port' => '3306',
            'database' => 'freeswitch',
            'username' => 'remote',
            'password' => 'Webmin@786',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'smwatch' => [
            'driver' => 'mysql',
            'host' => 'smwatch.ddns.net',
            'port' => '3306',
            'database' => 'smwatch_gbo',
            'username' => 'root',
            'password' => 'l00kingg00d',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'energy_store' => [
            'driver' => 'mysql',
            'host' => 'host2.cloudtools.co.za',
            'port' => '3306',
            'database' => 'ener9_wp',
            'username' => 'remote',
            'password' => 'Webmin@786',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'iptv_store' => [
            'driver' => 'mysql',
            'host' => 'host2.cloudtools.co.za',
            'port' => '3306',
            'database' => 'movi4_wp840',
            'username' => 'remote',
            'password' => 'Webmin@786',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'bulkhub' => [
            'driver' => 'mysql',
            'host' => 'host2.cloudtools.co.za',
            'port' => '3306',
            'database' => 'bulk3_bagisto',
            'username' => 'bulk3_bagisto',
            'password' => 'NearPinnedNapkinSinewy11',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'helpdesk' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'erpcloud-helpdesk',
            'username' => 'erpcloud-helpdesk',
            'password' => 'JO8Ri7tfPybFnN4B88UR',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'backup_server' => [
            'driver' => 'mysql',
            'host' => '156.0.96.50',
            'port' => '3306',
            'database' => 'mysql',
            'username' => 'remote',
            'password' => 'Webmin@786',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        /*EXTERNAL CONNECTIONS START*/
        'supportboard' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'host1.cloudtools.co.za'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'teleclou_support'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'fbstats' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'versaflo_airbyte'),
            'username' => env('DB_USERNAME', 'remote'),
            'password' => env('DB_PASSWORD', 'Webmin@786'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        /*
        'tickets' => [
            'driver' => 'mysql',
            'database' => 'flexerpio_tenant',
            'host' => '156.0.96.71',
            'username' => 'remote',
            'password' => 'Webmin@786',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],
*/
        // EXTERNAL CONNECTIONS - END
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'erp_db_migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [
        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],
];
