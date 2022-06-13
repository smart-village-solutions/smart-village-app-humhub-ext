<?php

namespace  humhub\modules\smartVillage;

use Yii;
use yii\helpers\Url;

class Events
{
    /**
     * Defines what to do when the top menu is initialized.
     *
     * @param $event
     */
    public static function onTopMenuInit($event)
    {
        $event->sender->addItem([
            'label' => 'SmartVillage',
            'icon' => '<i class="fa fa-adjust"></i>',
            'url' => Url::to(['/smartVillage/index']),
            'sortOrder' => 99999,
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'smartVillage' && Yii::$app->controller->id == 'index'),
        ]);
    }

    /**
     * Defines what to do if admin menu is initialized.
     *
     * @param $event
     */
    public static function onAdminMenuInit($event)
    {
        $event->sender->addItem([
            'label' => 'SmartVillage',
            'url' => Url::to(['/smartVillage/admin']),
            'group' => 'manage',
            'icon' => '<i class="fa fa-adjust"></i>',
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'smartVillage' && Yii::$app->controller->id == 'admin'),
            'sortOrder' => 99999,
        ]);
    }

    public static function onRestApiAddRules()
    {
        /* @var \humhub\modules\rest\Module $restModule */
        $restModule = Yii::$app->getModule('smartVillage');
        $restModule->addRules([
            ['pattern' => 'auth/register', 'route' => 'smartVillage/user/user/create', 'verb' => 'POST'],
            ['pattern' => 'auth/signup', 'route' => 'smartVillage/user/user/signup', 'verb' => 'POST'],

            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>/request', 'route' => 'smartVillage/user/membership/request-for-membership', 'verb' => 'POST'],
            ['pattern' => 'mail', 'route' => 'smartVillage/mail/message/index', 'verb' => 'GET'],
            //mark unread message of conversation as read
            ['pattern' => 'mail/<messageId:\d+>/entries', 'route' => 'smartVillage/mail/entry/index', 'verb' => 'GET'],

            //calendar
            ['pattern' => 'calendar', 'route' => 'smartVillage/calendar/calendar/find', 'verb' => 'GET'],
            ['pattern' => 'calendar/entry/<Id:\d+>', 'route' => 'smartVillage/calendar/calendar/view', 'verb' => 'GET'],
            ['pattern' => 'calendar/container/<containerId:\d+>', 'route' => 'smartVillage/calendar/calendar/find-by-container', 'verb' => ['GET', 'HEAD']],

            //post
            ['pattern' => 'post', 'route' => 'smartVillage/post/post/find', 'verb' => 'GET'],
            ['pattern' => 'post/<Id:\d+>', 'route' => 'smartVillage/post/post/view', 'verb' => 'GET'],
            ['pattern' => 'post/container/<containerId:\d+>', 'route' => 'smartVillage/post/post/find-by-container', 'verb' => ['GET', 'HEAD']],

            //space
            ['pattern' => 'space', 'route' => 'smartVillage/space/space/find', 'verb' => 'GET'],
            ['pattern' => 'space/<spaceId:\d+>', 'route' => 'smartVillage/space/space/view', 'verb' => 'GET'],

            //space membership
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>', 'route' => 'smartVillage/space/membership/create', 'verb' => 'POST'],

            //linklist category
            ['pattern' => 'categories', 'route' => 'smartVillage/linklist/category/index', 'verb' => 'GET'],
            ['pattern' => 'category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/category/view', 'verb' => 'GET'],
            ['pattern' => 'category', 'route' => 'smartVillage/linklist/category/create-category', 'verb' => 'POST'],
            ['pattern' => 'category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/category/edit-category', 'verb' => ['PUT','PATCH']],
            ['pattern' => 'delete/category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/category/delete-category', 'verb' => 'DELETE'],

            //linklist links
            ['pattern' => 'links', 'route' => 'smartVillage/linklist/link/index', 'verb' => 'GET'],
            ['pattern' => 'link/<linkId:\d+>', 'route' => 'smartVillage/linklist/link/view', 'verb' => 'GET'],
            ['pattern' => 'link', 'route' => 'smartVillage/linklist/link/create-link', 'verb' => 'POST'],
            ['pattern' => 'link/<linkId:\d+>', 'route' => 'smartVillage/linklist/link/edit-link', 'verb' => ['PUT','PATCH']],
            ['pattern' => 'delete/link/<linkId:\d+>', 'route' => 'smartVillage/linklist/link/delete-link', 'verb' => 'DELETE'],
            ['pattern' => 'link/category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/link/link-category', 'verb' => 'GET'],

            //E-mail and Web Notification Setting
            ['pattern' => 'settings', 'route' => 'smartVillage/setting/notification/save-settings', 'verb' => 'PUT'],
        ], 'smartVillage');
    }
}
