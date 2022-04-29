<?php

use humhub\widgets\Button;

// Register our module assets, this could also be done within the controller
\humhub\modules\smartVillage\assets\Assets::register($this);

$displayName = (Yii::$app->user->isGuest) ? Yii::t('SmartVillageModule.base', 'Guest') : Yii::$app->user->getIdentity()->displayName;

// Add some configuration to our js module
$this->registerJsConfig("smartVillage", [
    'username' => (Yii::$app->user->isGuest) ? $displayName : Yii::$app->user->getIdentity()->username,
    'text' => [
        'hello' => Yii::t('SmartVillageModule.base', 'Hi there {name}!', ["name" => $displayName])
    ]
])

?>

<div class="panel-heading"><strong>SmartVillage</strong> <?= Yii::t('SmartVillageModule.base', 'overview') ?></div>

<div class="panel-body">
    <p><?= Yii::t('SmartVillageModule.base', 'Hello World!') ?></p>

    <?=  Button::primary(Yii::t('SmartVillageModule.base', 'Say Hello!'))->action("smartVillage.hello")->loader(false); ?></div>
