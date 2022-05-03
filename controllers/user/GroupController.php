<?php

namespace humhub\modules\smartVillage\controllers\user;


use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\user\models\User;
use \humhub\modules\user\models\Group;
use Yii;

class GroupController extends AuthBaseController
{
    /**
     *  By Default Humhub default group is USER group whose id is 2,
     */
    const USER_DEFAULT_GROUP_ID = 2;

    /**
     * Adds the user to a particular Humhub group by passing groupID and userID
     *
     * @param $id
     * @param $userId
     * @return array
     */
    public function actionMemberAddWithoutAuth($id,$userId){
        $group = Group::findOne(['id' => $id]);
        if ($group === null) {
            return $this->returnError(404, 'Group not found!');
        }

        $user = User::findOne(['id' => $userId]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        if ($group->isMember($userId)) {
            return $this->returnError(400, 'User is already a member of the group!');
        }

        //By Default isManager value is zero, which show that user is group manager or not
        if ($group->addUser($userId, !(empty(Yii::$app->request->get('isManager'))))) {
            return $this->returnSuccess('Member added!');
        }

        return $this->returnError(400, 'Could not add member!');
    }

}