<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings specific to different
    | deployment environments (development, staging, production).
    |
    */

    'environments' => [
        'development' => [
            'cache_ttl' => 300, // 5 minutes
            'session_lifetime' => 240, // 4 hours
            'rate_limits' => [
                'api' => 120,
                'auth' => 10,
                'game' => 60,
            ],
            'logging' => [
                'level' => 'debug',
                'slow_query_threshold' => 500,
                'enable_query_logging' => true,
            ],
            'backup' => [
                'retention_days' => 7,
                'compression' => false,
            ],
            'monitoring' => [
                'health_check_enabled' => true,
                'alert_threshold' => 500,
            ],
        ],

        'staging' => [
            'cache_ttl' => 1800, // 30 minutes
            'session_lifetime' => 120, // 2 hours
            'rate_limits' => [
                'api' => 120,
                'auth' => 10,
                'game' => 60,
            ],
            'logging' => [
                'level' => 'debug',
                'slow_query_threshold' => 500,
                'enable_query_logging' => true,
            ],
            'backup' => [
                'retention_days' => 14,
                'compression' => true,
            ],
            'monitoring' => [
                'health_check_enabled' => true,
                'alert_threshold' => 500,
            ],
        ],

        'production' => [
            'cache_ttl' => 3600, // 1 hour
            'session_lifetime' => 120, // 2 hours
            'rate_limits' => [
                'api' => 60,
                'auth' => 5,
                'game' => 30,
            ],
            'logging' => [
                'level' => 'error',
                'slow_query_threshold' => 1000,
                'enable_query_logging' => false,
            ],
            'backup' => [
                'retention_days' => 30,
                'compression' => true,
            ],
            'monitoring' => [
                'health_check_enabled' => true,
                'alert_threshold' => 1000,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Current Environment Settings
    |--------------------------------------------------------------------------
    |
    | These are the settings for the current environment, automatically
    | selected based on the APP_ENV environment variable.
    |
    */

    'current' => function () {
        $env = env('APP_ENV', 'local');
        $environments = config('deployment.environments');
        
        // Map 'local' to 'development' settings
        if ($env === 'local') {
            $env = 'development';
        }
        
        return $environments[$env] ?? $environments['development'];
    },

    /*
    |--------------------------------------------------------------------------
    | Laravel Cloud Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Laravel Cloud deployment.
    |
    */

    'laravel_cloud' => [
        'database' => [
            'version' => 'pgsql-15',
            'ssl_mode' => env('APP_ENV') === 'production' ? 'require' : 'prefer',
            'timeout' => 30,
            'persistent' => false,
        ],
        
        'cache' => [
            'driver' => 'redis',
            'prefix' => env('CACHE_PREFIX', 'dgp-' . env('APP_ENV', 'local')),
        ],
        
        'queue' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        
        'storage' => [
            'disk' => env('APP_ENV') === 'production' ? 's3' : 'local',
        ],
        
        'scheduler' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings by Environment
    |--------------------------------------------------------------------------
    |
    | Security-related settings that vary by environment.
    |
    */

    'security' => [
        'force_https' => env('FORCE_HTTPS', env('APP_ENV') === 'production'),
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
        'csrf_token_lifetime' => env('CSRF_TOKEN_LIFETIME', 120), // 2 hours
        'password_timeout' => env('PASSWORD_TIMEOUT', 10800), // 3 hours
        'sanctum' => [
            'expiration' => env('SANCTUM_EXPIRATION', 525600), // 1 year in minutes
            'stateful_domains' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related settings that can be tuned per environment.
    |
    */

    'performance' => [
        'opcache_enabled' => env('OPCACHE_ENABLED', env('APP_ENV') === 'production'),
        'view_cache_enabled' => env('VIEW_CACHE_ENABLED', env('APP_ENV') === 'production'),
        'route_cache_enabled' => env('ROUTE_CACHE_ENABLED', env('APP_ENV') === 'production'),
        'config_cache_enabled' => env('CONFIG_CACHE_ENABLED', env('APP_ENV') === 'production'),
        'event_cache_enabled' => env('EVENT_CACHE_ENABLED', env('APP_ENV') === 'production'),
    ],

];