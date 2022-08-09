<?php

namespace humhub\modules\smartVillage\controllers\user;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\user\models\User;

class UserFindController extends BaseController
{
    /**
     * Get User by Id
     *
     * @param integer $userId the userId searched
     * @return array
     */

   public function actionFindUser($userId){
       $user = User::findOne($userId);

       if($user == null){
           return $this->returnError(404, 'User not found!');
       }

       return UserDefinitions::getUser($user);
   }
}