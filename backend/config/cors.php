<?php

$allowedOrigins = [];
$allowedOriginsPatterns = [];

// Add APP_URL host
$appUrl = env('APP_URL');
if ($appUrl) {
    $allowedOrigins[] = $appUrl;
}

// Add FRONTEND_URL
$frontendUrl = env('FRONTEND_URL');
if ($frontendUrl) {
    $allowedOrigins[] = $frontendUrl;
}

// Parse base domain for subdomain matching (use regex patterns)
$baseDomain = env('TENANCY_BASE_DOMAIN') ?: env('APP_DOMAIN');
if ($baseDomain) {
    $baseDomain = preg_replace('/^https?:\/\//i', '', $baseDomain);
    $baseDomain = preg_replace('/^www\./i', '', $baseDomain);
    $allowedOrigins[] = "https://{$baseDomain}";
    // Pattern for any subdomain
    $allowedOriginsPatterns[] = "/^https?:\\/\\/([a-z0-9-]+\\.)?".preg_quote($baseDomain, '/')."$/i";
}

// Add localhost for development
if (env('APP_ENV') !== 'production') {
    $allowedOriginsPatterns[] = '/^http:\\/\\/localhost(:[0-9]+)?$/';
    $allowedOriginsPatterns[] = '/^http:\\/\\/127\\.0\\.0\\.1(:[0-9]+)?$/';
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". CORS determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter($allowedOrigins))),

    'allowed_origins_patterns' => $allowedOriginsPatterns,

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
