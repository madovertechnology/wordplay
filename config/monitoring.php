<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for error tracking and monitoring features.
    |
    */

    'error_threshold' => env('MONITORING_ERROR_THRESHOLD', 10),
    'critical_error_threshold' => env('MONITORING_CRITICAL_ERROR_THRESHOLD', 5),
    
    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for performance monitoring and alerting.
    |
    */

    'performance' => [
        'slow_query_threshold' => env('MONITORING_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'slow_request_threshold' => env('MONITORING_SLOW_REQUEST_THRESHOLD', 2000), // milliseconds
        'memory_threshold' => env('MONITORING_MEMORY_THRESHOLD', 128), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for user analytics and tracking.
    |
    */

    'analytics' => [
        'enabled' => env('MONITORING_ANALYTICS_ENABLED', true),
        'track_page_views' => env('MONITORING_TRACK_PAGE_VIEWS', true),
        'track_user_actions' => env('MONITORING_TRACK_USER_ACTIONS', true),
        'track_game_events' => env('MONITORING_TRACK_GAME_EVENTS', true),
        'retention_days' => env('MONITORING_ANALYTICS_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for error and performance notifications.
    |
    */

    'notifications' => [
        'slack_webhook' => env('MONITORING_SLACK_WEBHOOK'),
        'email_recipients' => env('MONITORING_EMAIL_RECIPIENTS', ''),
        'critical_errors_only' => env('MONITORING_CRITICAL_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep different types of logs.
    |
    */

    'log_retention' => [
        'error_logs' => env('LOG_ERROR_DAYS', 30),
        'performance_logs' => env('LOG_PERFORMANCE_DAYS', 7),
        'security_logs' => env('LOG_SECURITY_DAYS', 90),
        'analytics_logs' => env('LOG_ANALYTICS_DAYS', 30),
    ],

];