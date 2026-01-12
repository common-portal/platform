<?php

return [
    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA v3 Configuration
    |--------------------------------------------------------------------------
    */

    'site_key' => env('RECAPTCHA_SITE_KEY', ''),
    'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    
    // Minimum score threshold (0.0 - 1.0). Recommended: 0.5
    // Lower scores indicate more likely bot activity
    'minimum_score' => env('RECAPTCHA_MINIMUM_SCORE', 0.5),
    
    // Enable/disable reCAPTCHA validation
    'enabled' => env('RECAPTCHA_ENABLED', true),
];
