<?php


use yii\helpers\Html;

/* @var $token integer */

?>
<?= mb_strtoupper(Yii::t('UserModule.base', 'Herzlich Willkommen auf der Ehrenamtsplattform in der Herzberg-App!', [])) ?>


<?= Yii::t('UserModule.base',
    'Um die Registrierung für die Ehrenamtsplattform abzuschließen, geben Sie bitte den Zahlencode in das entsprechende Feld in der Herzberg-App ein.',
    []); ?>


<?= Yii::t('UserModule.base', '%code%',['%code%' => $token]) ?>
