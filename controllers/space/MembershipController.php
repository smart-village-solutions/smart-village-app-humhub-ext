<?php

namespace humhub\modules\smartVillage\controllers\space;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\rest\definitions\UserDefinitions;
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

    /**
     * @param $spaceId
     * @return mixed
     * Get list of all the member present/exists in the space
     */
    //Get the list of all member of a space (issue-45 new endpoint)
    public function actionIndex($spaceId){
        $space = Space::findOne(['id'=> (int)$spaceId]);

        if($space == null){
            return $this->returnError(404, 'Space not found!');
        }

        $query = Membership::find()->where(['space_id' => (int)$spaceId]);

        if($query->count() == 0){
            return $this->returnError(404,'No member is exist in space : '.$spaceId);
        }
        $results = [];
        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $membership){
            /** @var Membership $membership */
            $results[] = SpaceDefinitions::getSpaceMembership($membership);
        }
        return $this->returnPagination($query,$pagination,$results);
    }

    /**
     * @return void
     * Get all the spaces list of the user
     */
    //Get the list of all spaces of the user (issue-8 new endpoint)
    public function actionGetSpaces(){
       $userId = Yii::$app->user->id;

       $user = User::findOne($userId);

       if($user == null){
           return $this->returnError(404, 'User not found!');
       }

       $spaceMemberships = Membership::find()->where(['user_id' => $userId]);

        if($spaceMemberships->all() == null){
            return $this->returnError(404, 'Space not found for the user : '.$userId);
        }

        $results = [];
        $pagination = $this->handlePagination($spaceMemberships);

        foreach ($spaceMemberships->all() as $spaceMembership){
            $results[] = $this->getSpacesListData($spaceMembership);
        }

        return $this->returnPagination($spaceMemberships,$pagination,$results);
    }

    public function getSpacesListData(Membership $membership){
        return [
            'owner' => UserDefinitions::getUserShort($membership->space->ownerUser),
            'id' => $membership->space_id,
            'guid' => $membership->space->guid,
            'name' => $membership->space->name,
            'description' => $membership->space->description,
            'visibility' => $membership->space->visibility,
            'tags' => $membership->space->getTags(),
            'contentcontainer_id' => $membership->space->contentcontainer_id,
            'role' => $membership->group_id,
            'status' => $membership->status,
            'can_cancel_membership' => $membership->can_cancel_membership,
            'send_notifications' => $membership->send_notifications,
            'show_at_dashboard' => $membership->show_at_dashboard,
            'member_since' => $membership->created_at,
            'request_message' => $membership->request_message,
            'updated_at' => $membership->updated_at,
            'last_visit' => $membership->last_visit,
        ];
    }
}