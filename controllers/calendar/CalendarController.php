<?php

namespace humhub\modules\smartVillage\controllers\calendar;

use Firebase\JWT\JWT;
use humhub\modules\calendar\helpers\RestDefinitions;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\user\models\User;
use Yii;


class CalendarController extends AuthBaseController
{
    /**
     * @return mixed
     * Get all the entries of calendar
     */
    public function actionFind(){
        $query = CalendarEntry::find()->joinWith('content')->orderBy(['content.created_at' => SORT_DESC]);

        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            $query = $query->where(['content.visibility' => Content::VISIBILITY_PUBLIC]);
        }

        //Filter the calendar entries by passing date range (start_date and end_date)
        if(isset($_GET['start_date']) || isset($_GET['end_date'])){
            if(isset($_GET['start_date']) && !isset($_GET['end_date'])) {
                $start = $_GET['start_date'];
                $query->andFilterWhere(['>=', 'start_datetime', $start])->orFilterWhere(['>=', 'end_datetime', $start]);
            }
            elseif (!isset($_GET['start_date']) && isset($_GET['end_date'])){
                $end = $_GET['end_date'];
                $query->andFilterWhere(['<=','end_datetime',$end]);
            }
            else{
                $start = $_GET['start_date'];
                $end = $_GET['end_date'];
                $query->andFilterWhere(['>=','start_datetime',$start])->andFilterWhere(['<=','end_datetime',$end]);
            }
        }

        $pagination = $this->handlePagination($query);

        $results = [];
        foreach ($query->all() as $contentRecord) {
            /** @var ContentActiveRecord $contentRecord */
            $results[] = RestDefinitions::getCalendarEntry($contentRecord);
        }

        return $this->returnPagination($query, $pagination, $results);
    }

    /**
     * Get calendar entry by passing id
     * @param $Id
     * @return array
     */
    public function actionView($Id){
        $entry = CalendarEntry::findOne(['id'=>$Id]);

        if($entry == null){

            return $this->returnError(404, "Requested content not found!");
        }

        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            if($entry->content->visibility == Content::VISIBILITY_PRIVATE){
                return $this->returnError(400,"Guest user cannot read this calendar entry data");
            }
        }

        if(isset($entry) && !empty($entry)){
            return RestDefinitions::getCalendarEntry($entry);

        }
    }

    /**
     * Finds content by given container
     *
     * @param integer $containerId the id of the content container
     * @return array the rest output
     * @throws \yii\db\IntegrityException
     */
    public function actionFindByContainer($containerId)
    {
        $contentContainer = ContentContainer::findOne(['id' => $containerId]);
        if ($contentContainer === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        /** @var ActiveQueryContent $query */
        $query = CalendarEntry::find()->contentContainer($contentContainer->getPolymorphicRelation())->orderBy(['content.created_at' => SORT_DESC]);

        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            $query = $query->andWhere(['content.visibility' => Content::VISIBILITY_PUBLIC]);
        }

        //Filter the calendar entries by passing date range (start_date and end_date)
        if(isset($_GET['start_date']) || isset($_GET['end_date'])){
            if(isset($_GET['start_date']) && !isset($_GET['end_date'])) {
                $start = $_GET['start_date'];
                $query->andFilterWhere(['>=', 'start_datetime', $start])->orFilterWhere(['>=', 'end_datetime', $start]);
            }
            elseif (!isset($_GET['start_date']) && isset($_GET['end_date'])){
                $end = $_GET['end_date'];
                $query->andFilterWhere(['<=','end_datetime',$end]);
            }
            else{
                $start = $_GET['start_date'];
                $end = $_GET['end_date'];
                $query->andFilterWhere(['>=','start_datetime',$start])->andFilterWhere(['<=','end_datetime',$end]);
            }
        }

        ContentDefinitions::handleTopicsParam($query, $containerId);

        $pagination = $this->handlePagination($query);

        $results = [];

        foreach ($query->all() as $contentRecord) {
            /** @var ContentActiveRecord $contentRecord */
            $results[] = RestDefinitions::getCalendarEntry($contentRecord);
        }

        return $this->returnPagination($query, $pagination, $results);
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