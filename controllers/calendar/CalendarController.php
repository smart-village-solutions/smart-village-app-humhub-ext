<?php

namespace humhub\modules\smartVillage\controllers\calendar;

use humhub\modules\calendar\helpers\RestDefinitions;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\calendar\models\CalendarEntry;


class CalendarController extends AuthBaseController
{
    /**
     * @return mixed
     * Get all the entries of calendar
     */
    public function actionFind(){
        $query = CalendarEntry::find()->joinWith('content')->orderBy(['content.created_at' => SORT_DESC])->readable();

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

        if(isset($entry) && !empty($entry)){
            return RestDefinitions::getCalendarEntry($entry);

        }else{
            return $this->returnError(400, "Requested content not found!");
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
        $query = CalendarEntry::find()->contentContainer($contentContainer->getPolymorphicRelation())->orderBy(['content.created_at' => SORT_DESC])->readable();

        ContentDefinitions::handleTopicsParam($query, $containerId);

        $pagination = $this->handlePagination($query);

        $results = [];

        foreach ($query->all() as $contentRecord) {
            /** @var ContentActiveRecord $contentRecord */
            $results[] = RestDefinitions::getCalendarEntry($contentRecord);
        }

        return $this->returnPagination($query, $pagination, $results);
    }

}