<?php

return [
    'email' => [
        'logo_url' => env('BRAND_EMAIL_LOGO_URL', rtrim((string) env('FRONTEND_URL', (string) env('APP_URL', '')), '/').'/brand/repair-buddy-logo.png'),
        'logo_alt' => env('BRAND_EMAIL_LOGO_ALT', (string) config('app.name', '99smartx')),
        'primary_color' => env('BRAND_PRIMARY_COLOR', '#063e70'),
        'accent_color' => env('BRAND_ACCENT_COLOR', '#fd6742'),
        'background_color' => env('BRAND_EMAIL_BACKGROUND_COLOR', '#f7f7f7'),
        'surface_color' => env('BRAND_EMAIL_SURFACE_COLOR', '#ffffff'),
        'border_color' => env('BRAND_EMAIL_BORDER_COLOR', '#ededed'),
        'text_color' => env('BRAND_EMAIL_TEXT_COLOR', '#2c304d'),
        'muted_text_color' => env('BRAND_EMAIL_MUTED_TEXT_COLOR', '#667085'),
        'radius_px' => (int) env('BRAND_EMAIL_RADIUS_PX', 15),
    ],
];
