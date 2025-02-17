<?php

    use Aws\Laravel\AwsServiceProvider;

    return [
        'credentials' => [
            'key'    => 'AKIAJGDPRX4VXX3F5OXA',
            'secret' => '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',
        ],
        'region' => 'us-west-2',
        'version' => 'latest',
    ];

    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `Aws\Sdk` object, from which all client objects are created. The minimum
    | required options are declared here, but the full set of possible options
    | are documented at:
    | http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
    |
    *
    return [

        'region' => env('AWS_REGION', 'us-east-1'),
        'version' => 'latest',
        'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
        ],
        // You can override settings for specific services

        'Ses' => [
            'region' => 'us-east-1',
        ],
    ];


    */
