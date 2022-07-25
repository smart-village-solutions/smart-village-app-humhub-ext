<?php

namespace humhub\modules\smartVillage\controllers\linklist;

use humhub\components\access\ControllerAccess;
use humhub\modules\admin\permissions\ManageSpaces;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\components\ContentContainerControllerAccess;
use humhub\modules\linklist\models\Category;
use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\smartVillage\components\BaseContentContainerController;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii\web\HttpException;
use Yii;

class CategoryController extends BaseContentContainerController
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
        $results = [];
        $categoryBuffer = Category::find()->contentContainer($this->contentContainer)->orderBy(['sort_order' => SORT_ASC]);

        if(empty($categoryBuffer->all())){
            return $this->returnError(400, "Category not found");
        }

        $pagination = $this->handlePagination($categoryBuffer);


        $space = Space::findOne(['guid' => Yii::$app->request->get('cguid')]);
        $user = User::findOne(['guid' => Yii::$app->request->get('cguid')]);

        //If we are trying to fetch categories of space
        if(isset($space) && !empty($space)){
            foreach($categoryBuffer->all() as $category){
                $results[] = self::getCategoryDataWithSpace($category,$space);
            }
        }

        //If we are trying to fetch categories of user
        if(isset($user) && !empty($user)){
            foreach($categoryBuffer->all() as $category){
                $results[] = self::getCategoryDataWithUser($category,$user);
            }
        }

        return $this->returnPagination($categoryBuffer, $pagination, $results);
    }

    public function actionView($categoryId){
        $result = [];
        $categoryBuffer = Category::find()->contentContainer($this->contentContainer)->where(['linklist_category.id' => $categoryId])->one();

        if(empty($categoryBuffer)){
            return $this->returnError(400, "Category not found, check category id or cguid!");
        }

        $space = Space::findOne(['guid' => Yii::$app->request->get('cguid')]);
        $user = User::findOne(['guid' => Yii::$app->request->get('cguid')]);

        //If we are trying to fetch categories of space
        if(isset($space) && !empty($space)){
            $result = self::getCategoryDataWithSpace($categoryBuffer,$space);

        }

        //If we are trying to fetch categories of user
        if(isset($user) && !empty($user)){
            $result = self::getCategoryDataWithUser($categoryBuffer,$user);
        }

        return $result;

    }

    public function actionCreateCategory(){
        if($this->accessLevel == 0 || $this->accessLevel == 1){
            throw new HttpException(404, Yii::t('LinklistModule.base', 'You miss the rights to create this category!'));
        }

        $category = new Category();
        $category->content->container = $this->contentContainer;
        $category->load(Yii::$app->request->getBodyParam("category",[]),'');
        $category->validate();

        if($category->hasErrors()){
            return $this->returnError(400,'Validation failed',[$category->getErrors()]);
        }

        if($category->save()){
            return $this->returnSuccess("Category successfully created",200,[
                'id' => $category->id,
                'title' => $category->title,
                'description' => $category->description,
                'sort_order' => $category->sort_order
            ]);
        }else{
            return $this->returnError(400,"Category not created!");
        }

    }

    public function actionEditCategory($categoryId){
        if($this->accessLevel == 0 || $this->accessLevel == 1){
            throw new HttpException(404, Yii::t('LinklistModule.base', 'You miss the rights to update this category!'));
        }

        $category = Category::find()->contentContainer($this->contentContainer)->where(array('linklist_category.id' => $categoryId))->one();
        if(isset($category) && !empty($category)){
            $category->content->container = $this->contentContainer;
            $category->load(Yii::$app->request->getBodyParam("category",[]),'');

            $category->validate();

            if($category->hasErrors()){
                return $this->returnError(400,'Validation failed',[$category->getErrors()]);
            }
            //Update the category data
            if($category->save()){
                return $this->returnSuccess("Category successfully updated!",200,[
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
                    'sort_order' => $category->sort_order
                ]);
            }else{
                return $this->returnError(400,"Category not updated!");
            }
        }else{
            return $this->returnError(400, "Category not found, check category id or cguid!");
        }
    }

    public function actionDeleteCategory($categoryId){
        if($this->accessLevel == 0 || $this->accessLevel == 1){
            throw new HttpException(404, Yii::t('LinklistModule.base', 'You miss the rights to delete this category!'));
        }

        $category = Category::find()->contentContainer($this->contentContainer)->where(array('linklist_category.id' => $categoryId))->one();

        if($category == null){
            return $this->returnError(400,"Category not found, check category id or cguid!");
        }
        if($category->delete()){
            return $this->returnSuccess("Category successfully deleted!",200);
        }else{
            return $this->returnError(400,"Category not Deleted");
        }
    }


    public static function getCategoryDataWithSpace($category,$space){
        return [
            'category_id' => $category['id'],
            'category_name' => $category['title'],
            'content_guid' => $category->content['guid'],
            'space_id' => $space['id'],
            'space_guid' => $space['guid'],
            'space_name' => $space['name'],
            'space_created_by' => $space['created_by'],
            'content_container_id' => $category->content['contentcontainer_id']
        ];
    }

    public static function getCategoryDataWithUser($category,$user){
        return [
            'category_id' => $category['id'],
            'category_name' => $category['title'],
            'content_guid' => $category->content['guid'],
            'user_id' => $user['id'],
            'user_guid' => $user['guid'],
            'user_name' => $user['username'],
            'user_created_by' => $user['created_by'],
            'user_email' => $user['email'],
            'content_container_id' => $category->content['contentcontainer_id']
        ];
    }
}