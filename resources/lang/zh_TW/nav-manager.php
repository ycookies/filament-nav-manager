<?php

// translations for Ycookies/FilamentNavManager
return [
    'nav' => [
        'label' => '導航管理',
        'group' => '系統管理',
        'icon'  => 'heroicon-o-bars-3',
    ],

    'resource' => [
        'label'          => '導航管理',
        'plural_label'   => '導航項目',
        'singular_label' => '導航項目',
    ],

    'table' => [
        'title'        => '標題',
        'type'         => '類型',
        'icon'         => '圖標',
        'panel'        => '面板',
        'order'        => '排序',
        'show'         => '顯示',
        'badge'        => '徽標',
        'hide'         => '隱藏',
        'all'          => '全部',
        'is_collapsed' => '摺疊',
        'updated_at'   => '更新時間',
        'copied'       => '已複製',
    ],

    'form' => [
        'basic_info'  => '基本信息',
        'panel'       => '所屬面板',
        'parent_menu' => '父選單',
        'title'       => '標題',
        'type'        => '類型',
        'icon'        => '圖標',
        'uri'         => 'URI',
        'target'      => '目標',
        'show'        => '顯示',
        'permission'  => '權限',
    ],

    'types' => [
        'group'    => '分組',
        'resource' => '資源',
        'page'     => '頁面',
        'route'    => '路由',
        'url'      => '連結',
    ],

    'actions' => [
        'create'           => '添加導航選單',
        'sync'             => '同步 Filament 導航選單',
        'sync_description' => '這將同步所有 Filament 資源、頁面和導航組到選單資料庫。已存在的選單將被更新。',
        'sync_complete_title'     => '同步完成',
        'sync_success'     => '成功同步 :count 個選單項目',
        'sync_error'       => '同步選單失敗',
    ],

    'tree' => [
        'reorder' => [
            'enable'  => '啟用重新排序',
            'disable' => '禁用重新排序',
        ],
    ],
];