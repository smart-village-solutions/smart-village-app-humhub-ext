<?php

namespace humhub\modules\smartVillage\controllers\space;

use Firebase\JWT\JWT;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\space\models\Space;
use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\user\models\User;
use Yii;

class SpaceController extends AuthBaseController
{
    public function actionFind(){
        $results = [];

        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            $query = Space::find()->where(['visibility' => Space::VISIBILITY_ALL]);
        }else{
            $query = Space::find()->where(['visibility' => Space::VISIBILITY_REGISTERED_ONLY])->orWhere(['visibility' => Space::VISIBILITY_ALL]);

        }

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $space) {
            $results[] = SpaceDefinitions::getSpace($space);
        }
        if(count($results) > 0){
            return $this->returnPagination($query, $pagination, $results);
        }
    }

    public function actionView($spaceId){
        $space = Space::findOne($spaceId);

        if(!isset($space)){
            return $this->returnError(400,"Space not found!");
        }

        $user = $this->checkUserIsRegistered();
        if($user == false){
            if($space->visibility == Space::VISIBILITY_REGISTERED_ONLY || $space->visibility == Space::VISIBILITY_NONE){
                return $this->returnError(400,"Guest user cannot read this space data");
            }
        }

        if(isset($space) && !empty($space)){
            return SpaceDefinitions::getSpace($space);
        }
    }

    private function checkUserIsRegistered(){
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if (!empty($authHeader) && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];

            $validData = JWT::decode($token, ConfigureForm::getInstance()->jwtKey, ['HS512']);
            if (!empty($validData->uid)) {
                return User::find()->active()->andWhere(['user.id' => $validData->uid])->one();
            }
        }
        return false;
    }


}