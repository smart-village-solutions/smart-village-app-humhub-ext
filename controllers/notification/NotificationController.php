<?php

namespace humhub\modules\smartVillage\controllers\setting;

use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\models\forms\NotificationSettings;
use humhub\modules\notification\targets\BaseTarget;
use humhub\modules\rest\components\BaseController;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii;

class NotificationController extends BaseController
{
    /**
     * @return array
     * @throws \Throwable
     * Show the list of  web as well as email notification setting of the user
     */
    public function actionIndex(){
        $form = new NotificationSettings(['user' => Yii::$app->user->getIdentity()]);
        $result = [];
        foreach($form->categories() as $category){
            //List down all category name like calendar, mail, conversation etc.
            $categoriesData = $category->getTitle();
            $result[] = $categoriesData;

            foreach($form->targets() as $target) {
                //Get the name of the setting, It will return the setting name like this(NotificationSettings[settings][notification.admin_web])
                $formName = $form->getSettingFormname($category, $target);

                //Get the status of setting i.e true(1) or false(0)
                $categoryEnabled = $target->isCategoryEnabled($category, $form->user);

                //List down the all notification setting name
                $settingNames = array("admin_web","admin_email","calendar_web","calendar_email","mail_web","mail_email","mail_conversation_web",
                    "mail_conversation_email","comments_web",
                    "comments_email","content_created_web","content_created_email","like_web","like_email",
                    "space_member_web","space_member_email","followed_web","followed_email","mentioned_web","mentioned_email");

                foreach($settingNames as $name){
                    //Check the setting name we are getting from $fromName is matching in $settingNames or not
                    if(strpos($formName,$name)!=FALSE){
                        $result[] = self::getData($name,$categoryEnabled);
                    }
                }
            }
        }
        return $result;

    }
    public static function getData($name,$status){
        return [
            $name => $status,
        ];
    }


    /**
     * @return array|int[]
     * Save the setting of notification for space, email and  web
     *
     * Create functions for space,email and web setting
     */
    public function actionSaveSettings(){
        $user='';
        if(!Yii::$app->user->isAdmin()){
            $user = User::findOne(Yii::$app->user->id);
        }

        $keys =  Yii::$app->request->getBodyParam('settings',[]);

        //save Space setting
        $saveSpaceSetting = $this->saveSpaceSetting($keys,$user);
        if($saveSpaceSetting['code']==400){
            return $saveSpaceSetting;
        }

        $settings = $this->getSettings($user);

        $validInput = array(1,true,0,false);
        if(count($keys)>0){
            //save Email setting
            $saveEmailSetting = $this->saveEmailSetting($keys,$user,$settings,$validInput);
            if($saveEmailSetting['code']==400){
                return $saveEmailSetting;
            }

            //Save Web setting
            $saveWebSetting = $this->saveWebSetting($keys,$user,$settings,$validInput);
            if($saveWebSetting['code']==400){
                return $saveWebSetting;
            }

            return $this->returnSuccess("Setting updated successfully",200);
        }else{
            return $this->returnError(400,"Data is required");
        }

    }

    /**
     * @param $keys
     * @param $user
     * @return array|int[]
     * Save the setting of space
     * $keys = data of body
     * $user = user data (if the user is admin then $user is null)
     *
     * working flow
     * 1. check the space id is exists or not
     * 2. check the user is member of that space or not
     * 3. set the space setting using space's guid
     */
    public function saveSpaceSetting($keys,$user){
        foreach($keys['spaces'] as $spaceId){
            $space = Space::find()->where(['id'=>$spaceId])->one();

            if(empty($space)){
                return $this->returnError(400,$spaceId." Space Id not exists");
            }

            //Check user is member of that space or not
            //check status of user in space_membership status
            //If status = 3 (member), status = 2(applicant) and status = 1 (Invite)
            $spaceMember = Membership::find()->where(['space_id'=>$spaceId,'user_id'=>$user->id])->one();

            if($spaceMember['status']== Membership::STATUS_INVITED || $spaceMember['status']== Membership::STATUS_APPLICANT){
                return $this->returnError(400,'You are not member of space : '.$spaceId);
            }

            Yii::$app->notification->setSpaces($space['guid'], $user);
        }
        return array("code"=>200);
    }

    /**
     * @param $keys
     * @param $user
     * @param $settings
     * @param $validInput
     * @return array|int[]
     * Save the setting of email notification
     * $keys = data of body
     * $user = user data
     * $settings = current user's setting status
     * $validInput = set of valid input values
     *
     * working flows
     * 1. check the module is activated or not
     * 2. check the key's value is disabled or not
     * 3. validate the input's values
     * 4. set the values of email notification
     */
    public function saveEmailSetting($keys,$user,$settings,$validInput){
        foreach($keys['email'] as $key => $value){

            //Check module is activated or exists or not
            $checkModuleStatus = $this->checkModule($key);
            if($checkModuleStatus['code']==400){
                return $checkModuleStatus;
            }

            //Check Key is disabled or not
            $checkKeyStatus = $this->checkKey($user,$keys);
            if($checkKeyStatus['code']==400){
                return $checkKeyStatus;
            }

            //Check value of key is blanked or not
            if($value===""){
                return $this->returnError(400,$key." value cannot be blanked");
            }

            //Validate the value
            if(!in_array($value,$validInput,true)){
                return $this->returnError(400,"Invalid value of ".$key." only 1,0,true or false values are accepted");
            }

            $key = "notification.".$key;
            $settings->set($key, $value);
        }
        return array("code"=>200);
    }

    /**
     * @param $keys
     * @param $user
     * @param $settings
     * @param $validInput
     * @return array|int[]
     * Save the setting of web notification
     * $keys = data of body
     * $user = user data
     * $settings = current user's setting status
     * $validInput = set of valid input values
     *
     * working flows
     * 1. check the module is activated or not
     * 2. check the key's value is disabled or not
     * 3. validate the input's values
     * 4. set the values of web notification
     */
    public function saveWebSetting($keys,$user,$settings,$validInput){
        foreach($keys['web'] as $key => $value){

            //Check module is activated or exists or not
            $checkModuleStatus = $this->checkModule($key);
            if($checkModuleStatus['code']==400){
                return $checkModuleStatus;
            }

            //Check Key is disabled or not
            $checkKeyStatus = $this->checkKey($user,$keys);
            if($checkKeyStatus['code']==400){
                return $checkKeyStatus;
            }

            //Check value of key is blanked or not
            if($value===""){
                return $this->returnError(400,$key." value cannot be blanked");
            }

            //Validate the value
            if(!in_array($value,$validInput,true)){
                return $this->returnError(400,"Invalid value of ".$key." only 1,0,true or false values are accepted");
            }

            $key = "notification.".$key;
            $settings->set($key, $value);
        }
        return array("code"=>200);
    }

    /**
     * @param $keyName
     * @return array|int[]
     * check current module is activated or not
     * $KeyName = Name of the current key like calendar, mail etc.
     *
     * working flows
     * 1. fetch the all module setting name
     * 2. compare with current key name ($keyName)
     * 3. If, it is not match then return error message with module name
     */
    public function checkModule($keyName){
        $setting = new NotificationSettings();
        foreach($setting->categories() as $category){
            $categoryEmailName = $category->id.'_email';
            $categoryWebName = $category->id.'_web';
            if($keyName==$categoryEmailName || $keyName==$categoryWebName){
                return array("code"=>200);
            }
        }
        //Separate module name from _
        $keyName = explode("_",$keyName);
        return $this->returnError(400,$keyName[0]. " Module is not present or disable");


    }

    /**
     * @param $user
     * @param $keys
     * @return array|int[]
     * Check the value of key can be updated or not (generally web key name  is disable)
     * $user = user's data
     * $keys = data of body
     *
     * working flows
     * 1. fetch the key name that can be updated and stored in settingKeys variable
     * 2. compare with keys of web
     * 3. If the key name not exists in settingKeys variable name then we simply return error with key name
     */
    public function checkKey($user,$keys){
        //Stored the all key that can be updated in settingkeys array
        foreach ($this->targets($user) as $target) {
            if (!$target->isEditable($user)) {
                continue;
            }

            foreach ($this->categories($user) as $category) {
                if ($category->isFixedSetting($target)) {
                    continue;
                }

                $settingKeys[] = $target->getSettingKey($category);

            }
        }

        //Check,each value of body can be updated or not
        foreach($keys['web'] as $keyName=>$value){
            $keyName = "notification.".$keyName;
            if(!in_array($keyName,$settingKeys,true)){
                return $this->returnError(400,$keyName." value not updated because it might be disabled");
            }
        }
        return array("code"=>200);

    }

    /**
     * @param $user
     * @return mixed|object|null
     * list of all setting both email and web of the user with key value pair
     */
    public function getSettings($user)
    {
        $module = Yii::$app->getModule('notification');

        return ($user) ? $module->settings->user($user) : $module->settings;
    }

    /**
     * @return BaseTarget[] the notification targets enabled for this user (or global)
     */
    public function targets($user)
    {
        $setting = new NotificationSettings();
        if (!$setting->_targets) {
            $setting->_targets = Yii::$app->notification->getTargets($user);
        }

        return $setting->_targets;
    }

    /**
     * @return NotificationCategory[] NotificationCategories enabled for this user (or global)
     */
    public function categories($user)
    {
        return Yii::$app->notification->getNotificationCategories($user);
    }
}