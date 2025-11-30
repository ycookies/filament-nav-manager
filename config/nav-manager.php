<?php

// config for Ycookies/FilamentNavManager
return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Roles
    |--------------------------------------------------------------------------
    |
    | Specify which roles are allowed to access the Nav Manager resource.
    | Set to null or empty array to allow all authenticated users.
    | You can use role names, IDs, or use a closure for custom logic.
    |
    */
    'allowed_roles' => null, // ['admin', 'super_admin'] or null for all authenticated users

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how long navigation should be cached (in seconds).
    | Set to 0 or null to disable caching.
    |
    */
    'cache_seconds' => 0,

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The database table name for storing navigation items.
    |
    */
    'table_name' => 'nav_manager',

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group name for the Nav Manager resource in Filament sidebar.
    | Set to null to use the default translation, or specify a custom group name.
    |
    */
    'navigation_group' => null, // null to use translation, or a custom group name like 'System'
];
