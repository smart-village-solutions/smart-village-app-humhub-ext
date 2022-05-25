<?php

namespace humhub\modules\smartVillage\controllers\post;

use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\post\models\Post;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\rest\definitions\PostDefinitions;
use humhub\modules\smartVillage\components\AuthBaseController;

class PostController extends AuthBaseController
{
    public function actionFind(){
        $query = Post::find()->joinWith('content')->orderBy(['content.created_at' => SORT_DESC])->readable();

        $pagination = $this->handlePagination($query);

        $results = [];
        foreach ($query->all() as $post) {
            $results[] = PostDefinitions::getPost($post);

        }

        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($Id){
        $post = Post::findOne(['id'=>$Id]);

        if(isset($post) && !empty($post)){
            return PostDefinitions::getPost($post);

        }else{
            return $this->returnError(400, "Requested post not found!");
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
        $query = Post::find()->contentContainer($contentContainer->getPolymorphicRelation())->orderBy(['content.created_at' => SORT_DESC])->readable();

        ContentDefinitions::handleTopicsParam($query, $containerId);

        $pagination = $this->handlePagination($query);

        $results = [];

        foreach ($query->all() as $post) {
            /** @var ContentActiveRecord $post */
            $results[] = PostDefinitions::getPost($post);
        }

        return $this->returnPagination($query, $pagination, $results);
    }
}