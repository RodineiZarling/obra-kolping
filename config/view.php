<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Where compiled Blade templates are stored.
    |
    | On Windows, the default directory can occasionally hit file locks
    | (e.g. antivirus/indexers) causing "Access denied" during atomic rename.
    | Using a dedicated directory reduces collisions while keeping the same
    | behavior and format.
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views-compiled')),

];
