<?php

// translations for Ycookies/FilamentNavManager
return [
    'nav' => [
        'label' => '导航管理',
        'group' => '系统管理',
        'icon'  => 'heroicon-o-bars-3',
    ],

    'resource' => [
        'label'          => '导航管理',
        'plural_label'   => '导航管理',
        'singular_label' => '导航管理',
    ],

    'table' => [
        'title'        => '菜单名称',
        'type'         => '类型',
        'icon'         => '图标',
        'panel'        => '面板',
        'order'        => '排序',
        'show'         => '显示',
        'badge'        => '徽标',
        'hide'         => '隐藏',
        'all'          => '全部',
        'is_collapsed' => '折叠',
        'updated_at'   => '更新时间',
        'copied'       => '已复制',
    ],

    'form' => [
        'basic_info'  => '基本信息',
        'panel'       => '所属面板',
        'parent_menu' => '父菜单',
        'title'       => '标题',
        'type'        => '类型',
        'icon'        => '图标',
        'uri'         => 'URI',
        'target'      => '目标',
        'show'        => '显示',
        'permission'  => '权限',
    ],

    'types' => [
        'group'    => '分组',
        'resource' => '资源',
        'page'     => '页面',
        'route'    => '路由',
        'url'      => '链接',
    ],

    'actions' => [
        'create'           => '添加导航菜单',
        'sync'             => '同步 Filament 导航菜单',
        'sync_description' => '这将同步所有 Filament 资源、页面和导航组到菜单数据库。已存在的菜单将被更新。',
        'sync_complete_title'     => '同步完成',
        'sync_success'     => '成功同步 :count 个菜单项',
        'sync_error'       => '同步导航菜单失败',
    ],
];
