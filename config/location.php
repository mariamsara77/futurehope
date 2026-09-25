<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    */

    'driver' => Stevebauman\Location\Drivers\IpApi::class,

    /*
    |--------------------------------------------------------------------------
    | Driver Fallbacks
    |--------------------------------------------------------------------------
    */

    'fallbacks' => [
        Stevebauman\Location\Drivers\IpInfo::class,
        Stevebauman\Location\Drivers\GeoPlugin::class,
        // Stevebauman\Location\Drivers\MaxMind::class, // disable kore din
    ],

    /*
    |--------------------------------------------------------------------------
    | Position
    |--------------------------------------------------------------------------
    */

    'position' => Stevebauman\Location\Position::class,

    /*
    |--------------------------------------------------------------------------
    | Http Client Options
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => 3,
        'connect_timeout' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | IpApi Configuration (Free)
    |--------------------------------------------------------------------------
    */

    'ipapi' => [
        'token' => env('IPAPI_TOKEN'), // optional - free plan e empty rakhte paren
    ],

    /*
    |--------------------------------------------------------------------------
    | MaxMind (Disable)
    |--------------------------------------------------------------------------
    */

    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),
        'local' => [
            'type' => 'city',
            'path' => storage_path('app/geoip/GeoLite2-City.mmdb'),
            'url' => sprintf('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', env('MAXMIND_LICENSE_KEY')),
        ],
        'web' => [
            'enabled' => false,
            'user_id' => env('MAXMIND_USER_ID'),
            'license_key' => env('MAXMIND_LICENSE_KEY'),
            'options' => [
                'host' => 'geoip.maxmind.com',
            ],
        ],
    ],

];