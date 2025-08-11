<?php

return [
    'link_expiration' => 15, // in minutes
    'login_redirect' => '/dashboard',
    'email_subject' => 'Your Magic Login Link',
    'prefix' => 'magic-link',
    'middleware' => ['web'],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for magic link requests to prevent abuse.
    | You can set different limits for different scenarios.
    |
    */
    'rate_limiting' => [
        // Rate limit for magic link requests per email address
        'per_email' => [
            'max_attempts' => 3,        // Maximum attempts
            'decay_minutes' => 60,      // Time window in minutes
        ],
        
        // Rate limit for magic link requests per IP address
        'per_ip' => [
            'max_attempts' => 10,       // Maximum attempts
            'decay_minutes' => 60,      // Time window in minutes
        ],
        
        // Global rate limit for all magic link requests
        'global' => [
            'max_attempts' => 100,      // Maximum attempts
            'decay_minutes' => 60,      // Time window in minutes
        ],
        
        // Enable/disable rate limiting
        'enabled' => true,
    ],
];
