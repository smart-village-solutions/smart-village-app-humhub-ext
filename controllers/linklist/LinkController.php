<?php

namespace humhub\modules\smartVillage\controllers\linklist;

use humhub\components\access\ControllerAccess;
use humhub\modules\admin\permissions\ManageSpaces;
use humhub\modules\content\components\ContentContainerControllerAccess;
use humhub\modules\linklist\models\Category;
use humhub\modules\linklist\models\Link;
use humhub\modules\rest\definitions\CommentDefinitions;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\smartVillage\components\BaseContentContainerController;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii\web\HttpException;
use yii;

class LinkController extends BaseContentContainerController
{
    /** access level of the user currently logged in. 0 -> no write access / 1 -> create links and edit own links / 2 -> full write access. * */
    public $accessLevel = 0;

    public function getAccessRules()
    {
        return [
            [ContentContainerControllerAccess::RULE_USER_GROUP_ONLY => [Space::USERGROUP_MEMBER, User::USERGROUP_SELF]],
            [ControllerAccess::RULE_PERMISSION => [ManageSpaces::class], 'actions' => ['config']],
        ];
    }

    /**
     * Automatically loads the underlying contentContainer (User/Space) by using
     * the uguid/sguid request parameter
     */
    public function init()
    {
        parent::init();
        $this->accessLevel = $this->getAccessLevel();
    }

    /**
     * Get the acces level to the linklist of the currently logged in user.
     * @return number 0 -> no write access / 1 -> create links and edit own links / 2 -> full write access
     */
    private function getAccessLevel()
    {
        if ($this->contentContainer instanceof \humhub\modules\user\models\User) {
            return $this->contentContainer->id == Yii::$app->user->id ? 2 : 0;
        } else if ($this->contentContainer instanceof \humhub\modules\space\models\Space) {
            return $this->contentContainer->can(new \humhub\modules\post\permissions\CreatePost()) ? 2 : 1;
        }
    }

    public function actionIndex(){
        $links = Link::find()->contentContainer($this->contentContainer)->joinWith('content')->orderBy(['sort_order' => SORT_ASC]);

        if(empty($links->all())){
            return $this->returnError(400, "Link not found");
        }

        $pagination = $this->handlePagination($links);

        $results =[];

        foreach ($links->all() as $link) {
            $results[] = self::getLinkData($link);
        }
        return $this->returnPagination($links, $pagination, $results);
    }

    public function actionView($linkId){

        $link = Link::find()->joinWith('content')->contentContainer($this->contentContainer)->where(['linklist_link.id'=>$linkId])->orderBy(['sort_order' => SORT_ASC])->one();
        if($link == null){
            return $this->returnError(400,"Link not found!");
        }

        $result= self::getLinkData($link);


        return $result;


    }

    public function actionCreateLink(){
        if($this->accessLevel == 0 || $this->accessLevel == 1){
            throw new HttpException(404, Yii::t('LinklistModule.base', 'You miss the rights to create this link!'));
        }

        $link = new Link();
        $link->content->container = $this->contentContainer;
        $link->load(Yii::$app->request->getBodyParam("link",[]),'');
        //saving the data
        $link->validate();


        if($link->hasErrors()){
            return $this->returnError(400,"Validation failed",[$link->getErrors()]);
        }

        $category = Category::findOne($link->category_id);
        if($category == null){
            return $this->returnError(400,"Invalid category id");
        }

        if($link->save()){
            return $this->returnSuccess("Link successfully created",200,[
                'id' => $link->id,
                'category_id' => $link->category_id,
                'href' => $link->href,
                'title' => $link->title,
                'description' => $link->description,
                'sort_order' => $link->sort_order

            ]);
        }

    }

    public function actionEditLink($linkId){
        if($this->accessLevel == 0 || $this->accessLevel == 1){
            throw new HttpException(404, Yii::t('LinklistModule.base', 'You miss the rights to Update this Link!'));
        }

        $link = Link::findOne($linkId);

        if($link==null){
            return $this->returnError(400,"Link not found!");
        }
        $link->content->container = $this->contentContainer;
        $link->load(Yii::$app->request->getBodyParam("link",[]),'');

        if($link->hasErrors()){
            return $this->returnError(400,"Validation failed!",[$link->getErrors()]);
        }

        $category = Category::findOne($link->category_id);
        if($category == null){
            return $this->returnError(400,"Invalid category id");
        }

        if($link->save()){
            return $this->returnSuccess("Link successfully updated!",200,[
                'id' => $link->id,
                'category_id' => $link->category_id,
                'title' => $link->title,
                'href' => $link->href,
                'description' => $link->description,
                'sort_order' => $link->sort_order
            ]);
        }

    }

    public function actionDeleteLink($linkId){
        if($this->accessLevel == 0 || $this->accessLevel == 1){
            throw new HttpException(404, Yii::t('LinklistModule.base', 'You miss the rights to delete this link!'));
        }

        $link = Link::findOne($linkId);
        if($link==null){
            return $this->returnError(400, "Link not found!");
        }

        if($link->delete()){
            return $this->returnSuccess("Link successfully deleted",200);
        }else{
            return $this->returnError(400,'Link not deleted!');

        }
    }

    public function actionLinkCategory($categoryId){
        $links = Link::find()->joinWith('content')->contentContainer($this->contentContainer)->where(array('category_id' => $categoryId))->orderBy(['sort_order' => SORT_ASC]);

        if(empty($links->all())){
            return $this->returnError(400, "Link not found");
        }

        $pagination = $this->handlePagination($links);

        $results =[];

        foreach ($links->all() as $link) {
            $results[] = self::getLinkData($link);

        }

        return $this->returnPagination($links, $pagination, $results);
    }

    public static function getLinkData($link){
        return [
            "link_id" => $link['id'],
            "link_title" => $link['title'],
            "link_href" => $link['href'],
            "link_description" => $link['description'],
            "link_sort_order" => $link['sort_order'],
            "category_id" => $link->category['id'],
            "category_name" => $link->category['title'],
            "comments" => CommentDefinitions::getCommentsSummary($link->content),
            "likes" => LikeDefinitions::getLikesSummary($link->content),
        ];

    }
}