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
            //---auth---
            ['pattern' => 'auth/register', 'route' => 'smartVillage/user/user/create', 'verb' => 'POST'],
            ['pattern' => 'auth/signup', 'route' => 'smartVillage/user/user/signup', 'verb' => 'POST'],

            //---user---
            ['pattern' => 'user/<userId:\d+>','route' => 'smartVillage/user/user-find/find-user', 'verb' => 'GET'],
            ['pattern' => 'user', 'route' => 'smartVillage/user/user-find/index', 'verb' => 'GET'],

            //---mail---
            ['pattern' => 'mail', 'route' => 'smartVillage/mail/message/index', 'verb' => 'GET'],

            //upload
            ['pattern' => 'mail/<messageId:\d+>/upload-files', 'route' => 'smartVillage/mail/file-upload/index', 'verb' => 'POST'],

            //mark unread message of conversation as read
            ['pattern' => 'mail/<messageId:\d+>/entries', 'route' => 'smartVillage/mail/entry/index', 'verb' => 'GET'],

            //---calendar---
            ['pattern' => 'calendar', 'route' => 'smartVillage/calendar/calendar/find', 'verb' => 'GET'],
            ['pattern' => 'calendar/entry/<Id:\d+>', 'route' => 'smartVillage/calendar/calendar/view', 'verb' => 'GET'],
            ['pattern' => 'calendar/container/<containerId:\d+>', 'route' => 'smartVillage/calendar/calendar/find-by-container', 'verb' => ['GET', 'HEAD']],

            //recurring events endpoints
            ['pattern' => 'calendar/recurring', 'route' => 'smartVillage/calendar/recurring-events/index', 'verb' => 'GET'],
            ['pattern' => 'calendar/container/<containerId:\d+>/recurring', 'route'=> 'smartVillage/calendar/recurring-events/recurring-container','verb'=>'GET'],
            ['pattern' => 'calendar/entry/<Id:\d+>/recurring','route' => 'smartVillage/calendar/recurring-events/recurring-entry','verb'=>'GET'],

            //---post---
            ['pattern' => 'post', 'route' => 'smartVillage/post/post/find', 'verb' => 'GET'],
            ['pattern' => 'post/<Id:\d+>', 'route' => 'smartVillage/post/post/view', 'verb' => 'GET'],
            ['pattern' => 'post/container/<containerId:\d+>', 'route' => 'smartVillage/post/post/find-by-container', 'verb' => ['GET', 'HEAD']],

            //---space---
            ['pattern' => 'space', 'route' => 'smartVillage/space/space/find', 'verb' => 'GET'],
            ['pattern' => 'space/<spaceId:\d+>', 'route' => 'smartVillage/space/space/view', 'verb' => 'GET'],

            //space membership
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>', 'route' => 'smartVillage/space/membership/create', 'verb' => 'POST'],
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>', 'route' => 'smartVillage/space/membership/remove', 'verb' => 'DELETE'],
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>/request', 'route' => 'smartVillage/user/membership/request-for-membership', 'verb' => 'POST'],

            //get all the members list of a space (issue-45 new endpoint)
            ['pattern' => 'space/<spaceId:\d+>/membership', 'route' => 'smartVillage/space/membership/index', 'verb' => 'GET'],

            //get the list of all spaces of the user (issue-8 new endpoint)
            ['pattern' => 'space/memberships', 'route' => 'smartVillage/space/membership/get-spaces', 'verb' => 'GET'],

            //---linklist---
            //category
            ['pattern' => 'categories', 'route' => 'smartVillage/linklist/category/index', 'verb' => 'GET'],
            ['pattern' => 'category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/category/view', 'verb' => 'GET'],
            ['pattern' => 'category', 'route' => 'smartVillage/linklist/category/create-category', 'verb' => 'POST'],
            ['pattern' => 'category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/category/edit-category', 'verb' => ['PUT','PATCH']],
            ['pattern' => 'category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/category/delete-category', 'verb' => 'DELETE'],

            //link
            ['pattern' => 'links', 'route' => 'smartVillage/linklist/link/index', 'verb' => 'GET'],
            ['pattern' => 'link/<linkId:\d+>', 'route' => 'smartVillage/linklist/link/view', 'verb' => 'GET'],
            ['pattern' => 'link', 'route' => 'smartVillage/linklist/link/create-link', 'verb' => 'POST'],
            ['pattern' => 'link/<linkId:\d+>', 'route' => 'smartVillage/linklist/link/edit-link', 'verb' => ['PUT','PATCH']],
            ['pattern' => 'link/<linkId:\d+>', 'route' => 'smartVillage/linklist/link/delete-link', 'verb' => 'DELETE'],
            ['pattern' => 'link/category/<categoryId:\d+>', 'route' => 'smartVillage/linklist/link/link-category', 'verb' => 'GET'],
        ], 'smartVillage');
    }
}
