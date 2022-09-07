<?php

namespace humhub\modules\smartVillage\controllers\user;

use humhub\modules\rest\components\BaseController;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\space\notifications\ApprovalRequest;

class MembershipController extends BaseController
{
    /**
     * group_id of space membership request will be 'member'
     */
    const USERGROUP_MEMBER = 'member';

    /**
     *  group_id of admin of a particular space will be 'admin'
     */
    const USERGROUP_ADMIN = 'admin';

    /**
     * Status of space membership request will be 2
     */
    const STATUS_APPLICANT = 2;

    /**
     * Request for space membership with API
     *
     * Steps
     * 1. It will check both spaceId and userId is valid or not.
     * 2. It will check the user is already member of that space or not
     * 3. Finally, Request for space membership will be sent by calling addMembership function
     *
     * Note: Once the request for space membership will be sent then admin of that space will get a notification
     *
     * @param $spaceId
     * @param $userId
     * @return array|void
     */
    public function actionRequestForMembership($spaceId,$userId){

         $space = Space::findOne(['id'=>$spaceId]);
         $user = User::findOne(['id' => $userId]);

         if(empty($space)){
             return $this->returnError(400,"Space not found");
         }
         if(empty($user)){
               return $this->returnError(400, "User not found");
         }

         //Check user is already member or not
         $checkUserMember = Membership::findOne(['user_id' => $userId, 'space_id' => $spaceId]);
         if(isset($checkUserMember) && !empty($checkUserMember)){
             return $this->returnError(400,'User is already member');
         }

        return $this->addMembership($spaceId,$userId);
     }

    /**
     * Store the data of space membership request
     * Return the response after saving the data
     *
     * @param $spaceId
     * @param $userId
     * @return array|void
     */
     public function addMembership($spaceId,$userId){
         // Add Membership
         $membership = new Membership([
             'space_id' => $spaceId,
             'user_id' => $userId,
             'status' => MembershipController::STATUS_APPLICANT,
             'group_id' => MembershipController::USERGROUP_MEMBER
             ]);

         if($membership->save()){
             $membership = Membership::findOne(['user_id' => $userId, 'space_id' => $spaceId]);
             $user = User::findOne(['id' => $userId]);
             $space = Space::findOne(['id'=>$spaceId]);

             ApprovalRequest::instance()->from($user)->about($space)->sendBulk($this->getAdminsQuery($space));

             return $this->returnSuccess("Success",200,[
                'space_id' =>  $membership->space_id,
                'user_id' => $membership->user_id,
                'status' =>  $membership->status,
                'group_id' => $membership->group_id
             ]);
         }else{
             return $this->returnError(400,"Member not added!");
         }
     }

    public function getAdminsQuery($space){
        $query = Membership::getSpaceMembersQuery($space);
        $query->andWhere(['space_membership.group_id' => MembershipController::USERGROUP_ADMIN]);

        return $query;
    }
}