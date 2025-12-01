<?php

// translations for Ycookies/FilamentNavManager
return [
    'nav' => [
        'label' => 'Navigation Manager',
        'group' => 'System',
        'icon'  => 'heroicon-o-bars-3',
    ],

    'resource' => [
        'label'          => 'Navigation Manager',
        'plural_label'   => 'Navigation Items',
        'singular_label' => 'Navigation Item',
    ],

    'table' => [
        'title'        => 'Title',
        'type'         => 'Type',
        'icon'         => 'Icon',
        'panel'        => 'Panel',
        'order'        => 'Order',
        'show'         => 'Show',
        'badge'        => 'Badge',
        'hide'         => 'Hide',
        'all'          => 'All',
        'is_collapsed' => 'Collapsed',
        'updated_at'   => 'Updated At',
        'copied'       => 'Copied',
    ],

    'form' => [
        'basic_info'  => 'Basic Information',
        'panel'       => 'Panel',
        'parent_menu' => 'Parent Menu',
        'title'       => 'Title',
        'type'        => 'Type',
        'icon'        => 'Icon',
        'uri'         => 'URI',
        'target'      => 'Target',
        'show'        => 'Show',
        'permission'  => 'Permission',
    ],

    'types' => [
        'group'    => 'Group',
        'resource' => 'Resource',
        'page'     => 'Page',
        'route'    => 'Route',
        'url'      => 'URL',
    ],

    'actions' => [
        'create'           => 'Create Navigation Item',
        'sync'             => 'Sync Filament Nav Menu',
        'sync_description' => 'This will sync all Filament resources, pages and navigation groups to the menu database. Existing menus will be updated.',
        'sync_complete_title'     => 'Sync Complete',
        'sync_success'     => 'Successfully synced :count menu items',
        'sync_error'       => 'Failed to sync menus',
    ],
];
