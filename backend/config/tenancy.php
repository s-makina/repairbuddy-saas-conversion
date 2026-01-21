<?php

return [
    'resolution' => env('TENANCY_RESOLUTION', 'path'),
    'route_param' => env('TENANCY_ROUTE_PARAM', 'business'),
    'header' => env('TENANCY_HEADER', 'X-Tenant'),
];
