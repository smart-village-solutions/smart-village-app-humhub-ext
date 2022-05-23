<?php

namespace humhub\modules\smartVillage\controllers\space;

use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\space\models\Space;
use humhub\modules\rest\definitions\SpaceDefinitions;

class SpaceController extends AuthBaseController
{
    public function actionFind(){
        $results = [];
        $query = Space::find()->where(['visibility' => Space::VISIBILITY_ALL]);

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $space) {
            $results[] = SpaceDefinitions::getSpace($space);
        }
        if(count($results) > 0){
            return $this->returnPagination($query, $pagination, $results);
        }else{
            return $this->returnError(400,"No space data is publicly available!");
        }
    }

    public function actionView($spaceId){
        $checkSpace = Space::findOne($spaceId);
        $checkVisibility = Space::find()->where(['id'=> $spaceId])->andWhere(['visibility' => Space::VISIBILITY_ALL])->one();

        if(!isset($checkSpace)){
            return $this->returnError(400,"Space not found!");
        }

        if(!isset($checkVisibility)){
            return $this->returnError(400,"Guest user cannot read this space data");
        }

        $space = Space::find()->where(['id'=> $spaceId])->andWhere(['visibility' => Space::VISIBILITY_ALL])->one();

        if(isset($space) && !empty($space)){
            return SpaceDefinitions::getSpace($space);
        }
    }

}