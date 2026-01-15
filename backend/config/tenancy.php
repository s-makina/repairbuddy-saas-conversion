<?php

return [
    'resolution' => env('TENANCY_RESOLUTION', 'header'),
    'header' => env('TENANCY_HEADER', 'X-Tenant'),
];
