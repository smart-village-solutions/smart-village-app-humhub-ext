<?php

namespace humhub\modules\smartVillage\controllers\space;

use humhub\modules\rest\components\BaseController;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

class MembershipController extends BaseController
{
    /**
     * $spaceId = space id of that space you want adding member in it
     * $userId = user id
     * @param $spaceId
     * @param $userId
     * @return mixed
     * Adding members without admin and space's owner.
     */
     public function actionCreate($spaceId,$userId){
         $space = Space::findOne(['id' => (int)$spaceId]);
         $user = User::findOne(['id' => (int)$userId]);

         if($space == null){
           return $this->returnError(404,"Space not found");
         }
         if($user == null){
             return $this->returnError(404,"User not found");
         }

         //check user is already a member of that space or not
         $checkMembership = Membership::find()
                             ->where(['space_id'=>$spaceId, 'user_id'=> $userId, 'status'=>Membership::STATUS_MEMBER])
                             ->one();
         if($checkMembership == null){
             $space->addMember($userId, Yii::$app->request->get('canLeave', true), Yii::$app->request->get('silent', false));
             return $this->returnSuccess('Member added!',200);
         }else{
             return $this->returnError(400,"User is already member of that space!");
         }
     }
}