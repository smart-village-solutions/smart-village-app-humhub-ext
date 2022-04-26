<?php

use humhub\modules\smartVillage\smartVillage\Events;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\widgets\TopMenu;

return [
	'id' => 'smartVillage',
	'class' => 'humhub\modules\smartVillage\smartVillage\Module',
	'namespace' => 'humhub\modules\smartVillage\smartVillage',
	'events' => [
		[
			'class' => TopMenu::class,
			'event' => TopMenu::EVENT_INIT,
			'callback' => [Events::class, 'onTopMenuInit'],
		],
		[
			'class' => AdminMenu::class,
			'event' => AdminMenu::EVENT_INIT,
			'callback' => [Events::class, 'onAdminMenuInit']
		],
        [
             'class' => 'humhub\modules\rest\Module',
             'event' => 'restApiAddRules' ,
             'callback' => [Events::class, 'onRestApiAddRules']
        ],
    ],
];
