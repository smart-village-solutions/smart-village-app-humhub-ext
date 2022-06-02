<?php


use yii\helpers\Html;

/* @var $token integer */

?>
<?= mb_strtoupper(Yii::t('UserModule.base', 'Welcome to %appName%', ['%appName%' => Yii::$app->name])) ?>


<?= Yii::t('UserModule.base',
    'Welcome to %appName%. Please enter the code below to proceed with your registration.',
    ['%appName%' => Yii::$app->name]); ?>


<?= Yii::t('UserModule.base', '%code%',['%code%' => $token]) ?>
