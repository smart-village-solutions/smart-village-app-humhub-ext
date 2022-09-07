<?php

namespace humhub\modules\smartVillage\controllers\post;

use Firebase\JWT\JWT;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\post\models\Post;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\rest\definitions\PostDefinitions;
use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\user\models\User;
use humhub\modules\rest\models\ConfigureForm;
use Yii;

class PostController extends AuthBaseController
{
    public function actionFind(){
        $query = Post::find()->joinWith('content')->orderBy(['content.created_at' => SORT_DESC]);

        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            $query = $query->andWhere(['content.visibility' => Content::VISIBILITY_PUBLIC]);
        }

        $pagination = $this->handlePagination($query);

        $results = [];
        foreach ($query->all() as $post) {
            $results[] = PostDefinitions::getPost($post);

        }

        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($Id){
        $post = Post::findOne(['id'=>$Id]);

        if($post == null){
            return $this->returnError(404, "Requested post not found!");
        }
        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            if($post->content->visibility == Content::VISIBILITY_PRIVATE){
                return $this->returnError(400,"Guest user cannot read this post data");
            }
        }

        if(isset($post) && !empty($post)){
            return PostDefinitions::getPost($post);

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

        //Check the requested user is guest or not
        $user = $this->checkUserIsRegistered();
        if($user == false){
            $query = $query->andWhere(['content.visibility' => Content::VISIBILITY_PUBLIC]);
        }

        ContentDefinitions::handleTopicsParam($query, $containerId);

        $pagination = $this->handlePagination($query);

        $results = [];

        foreach ($query->all() as $post) {
            /** @var ContentActiveRecord $post */
            $results[] = PostDefinitions::getPost($post);
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