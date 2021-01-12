<?php

namespace App\Helper;

use App\Helper\Acl;

/**
 *
 */
class Menu
{


    private $items;
    private $user;
    private $route;


    public function __construct($route, $user = null)
    {
        $this->route = $route;

        $this->user = $user;

        //        [
        //            'label'   => '控制面板',
        //            'icon'=>'default',
        //            'route'     => 'admin',
        //        ],

        $this->items = [
            [
                'label' => 'Overview',
                'route' => 'admin',
            ],

            [
                'label' => '权限管理',
                'route' => 'permission',
            ],

            [
                'label' => '用户管理', //含有子分类
                'route' => 'user',
                'icon' => 'fa fa-users fa-fw',
                'items' => [
                    [
                        'label' => '用户列表',
                        'route' => 'user',
                    ],
                    [
                        'label' => '添加用户',
                        'route' => 'useradd',
                    ],

                    [
                        'label' => '编辑用户',
                        'route' => 'useredit',
                    ],

                ] //items
            ],


            [
                'label' => '用户组管理',
                'route' => 'group',
            ],

            [
                'label' => '文章管理', //含有子分类
                'route' => 'post-admin',
                'icon' => 'fa fa-file-text-o fa-fw',
                'skip' => true, //不检查权限，直接显示

                'items' => [
                    [
                        'label' => '文章列表',
                        'route' => 'post-admin',
                        'skip' => true,
                    ],
                    [
                        'label' => '写文章',
                        'route' => 'post-admin.new',
                        'skip' => true,
                    ],

                    [
                        'label' => '编辑文章',
                        'route' => 'post-admin.edit',
                        'skip' => true,
                        'hide' => true, //不显示在菜单里
                    ],

                    [
                        'label' => '我的文集',
                        'route' => 'collection-admin',
                        'skip' => true,
                        
                    ],

                    
                    

                ] //items
            ]
            


        ];
    }

    public function getUserItems()
    {

        $currentRoute = $this->route->getName();

        $permissionToRoutes = Acl::getPermissionRoutes($this->user->group_id);

        $permissionRoutes = [];
        if (!empty($permissionToRoutes)) {
            foreach ($permissionToRoutes as $obj) {
                $permissionRoutes[] = $obj->route;
            }
        } else {
            return [];
        }


        $userItems = $this->items;
        foreach ($userItems as $key => $item) {

            if ($currentRoute == $item['route']) {
                $userItems[$key]['current'] = true;
            }

            if (!in_array($item['route'], $permissionRoutes)) {


                if (!isset($item['skip']) || $item['skip'] == false) {
                    unset($userItems[$key]);
                    continue;
                }
            }

            if (isset($item['items'])) {

                $subItems = $item['items'];

                foreach ($subItems as $k => $sub_item) {

                    if ($currentRoute == $sub_item['route']) {
                        $userItems[$key]['current'] = true;
                        $userItems[$key]['items'][$k]['current'] = true;
                    }

                    if (isset($sub_item['hide']) && $sub_item['hide'] ) {
                        unset($userItems[$key]['items'][$k]);
                    }

                    if (!in_array($sub_item['route'], $permissionRoutes)) {
                        if (!isset($sub_item['skip']) || $sub_item['skip'] == false) {
                            unset($userItems[$key]['items'][$k]);
                            //continue;
                        }
                    }
                }
            }
        }
        return $userItems;
    }
}