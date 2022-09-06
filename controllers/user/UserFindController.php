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

    /**
     * @return mixed
     * Issue-61
     * Get the list of all registered user, earlier only admin can get the user's list but now every registered user can get the user's list.
     */
   public function actionIndex(){
     $results = [];
     $query = User::find();

     $pagination = $this->handlePagination($query);
     foreach($query->all() as $user){
         $results[] = UserDefinitions::getUser($user);
     }
       return $this->returnPagination($query, $pagination, $results);
   }
}