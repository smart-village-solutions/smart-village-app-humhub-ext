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
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>/request', 'route' => 'smartVillage/user/membership/request-for-membership', 'verb' => 'POST'],
            ['pattern' => 'mail', 'route' => 'smartVillage/mail/message/index', 'verb' => 'GET'],
            //mark unread message of conversation as read
            ['pattern' => 'mail/<messageId:\d+>/entries', 'route' => 'smartVillage/mail/entry/index', 'verb' => 'GET'],
        ], 'smartVillage');
    }
}
