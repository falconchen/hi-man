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


    public function __construct($router, $user=null)
    {

        $this->user = $user;

//        [
//            'label'   => '控制面板',
//            'icon'=>'default',
//            'url'     => 'admin',
//        ],

        $this->items = [
            [
                'label' => 'Overview',
                'url' => 'admin',
            ],

            [
                'label' => '权限管理',
                'url' => 'permission',
            ],

            [
                'label' => '用户管理', //含有子分类
                'url' => 'user',
                'icon'=>'fa fa-users fa-fw',
                'items' => [
                    [
                        'label' => '用户列表',
                        'url' => 'user',
                    ],
                    [
                        'label' => '添加用户',
                        'url' => 'useradd',
                    ],

                    [
                        'label' => '编辑用户',
                        'url' => 'useredit',
                    ],

                ]//items
            ],


            [
                'label' => '用户组管理',
                'url' => 'group',
            ],

            [
                'label' => '文章管理', //含有子分类
                'url' => $router->pathFor('post-admin'),
                'icon'=>'fa fa-users fa-fw',
                'visible'=>true, //不检查权限，直接显示

                'items' => [
                    [
                        'label' => '文章列表',
                        'url' => $router->pathFor('post-admin'),
                        'visible'=>true,
                    ],
                    [
                        'label' => '新建文章',
                        'url' => $router->pathFor('post-admin.new'),
                        'visible'=>true,
                    ],
                ]//items
            ]


        ];

    }

    public function getUserItems()
    {

        //
        $permissionToRoutes = Acl::getPermissionRoutes( $this->user->group_id );

        $permissionRoutes = [];
        if(!empty($permissionToRoutes)) {
            foreach($permissionToRoutes as $obj){
                $permissionRoutes[] = $obj->route;
            }
        }else{
            return [];
        }


        $userItems = $this->items;
        foreach( $userItems as $key => $item){

            if( !in_array($item['url'],$permissionRoutes)){

                if(!isset($item['visible']) || $item['visible'] == false){
                    unset($userItems[$key]);
                    continue;
                }

            }
            if(isset($item['items'])) {
                $subItems = $item['items'];
                foreach( $subItems as $k => $sub_item) {
                    if (!in_array($sub_item['url'], $permissionRoutes)) {
                        if(!isset($sub_item['visible']) || $sub_item['visible'] == false) {
                            unset($userItems[$key]['items'][$k]);
                            continue;
                        }
                    }
                }

            }
        }
        return $userItems;


    }



}