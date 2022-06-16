<?php

use humhub\modules\smartVillage\Events;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\widgets\TopMenu;

return [
	'id' => 'smartVillage',
	'class' => 'humhub\modules\smartVillage\Module',
	'namespace' => 'humhub\modules\smartVillage',
	'events' => [
        //Hide SmartVillage from header and sidebar for users
//		[
//			'class' => TopMenu::class,
//			'event' => TopMenu::EVENT_INIT,
//			'callback' => [Events::class, 'onTopMenuInit'],
//		],
//		[
//			'class' => AdminMenu::class,
//			'event' => AdminMenu::EVENT_INIT,
//			'callback' => [Events::class, 'onAdminMenuInit']
//		],
        [
             'class' => 'humhub\modules\rest\Module',
             'event' => 'restApiAddRules' ,
             'callback' => [Events::class, 'onRestApiAddRules']
        ],
    ],
];
