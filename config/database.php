<?php

return [
    'host' => env('DB_HOST', 'default_host'),
    'name' => env('DB_NAME', 'default_name'),
    'user' => env('DB_USER', 'default_user'),
    'pass' => env('DB_PASS', 'default_pass'),
    'error_mode' => env('DEV', false) ? E_ALL : 0
];
