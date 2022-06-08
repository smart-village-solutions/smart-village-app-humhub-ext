<?php

namespace humhub\modules\smartVillage\controllers\user;

use humhub\components\access\ControllerAccess;
use humhub\modules\legal\models\Page;
use humhub\modules\legal\models\RegistrationChecks;
use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\user\models\Invite;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use humhub\modules\user\Module;
use Yii;
use yii\web\HttpException;
use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\models\ConfigureForm;
use Firebase\JWT\JWT;


class UserController extends AuthBaseController
{
    /**
     * Register user without bearer token
     *
     * Steps
     * 1. First, The user register in 'user' table with status = 2 (approval required)
     * 2. New user will be automatically adds in default humhub group, which is 'USER' Group whose (id=2)
     * 3. User will automatically accept privacy policy condition during registration
     * 4. An email will be sent to user email address with 6 digit random number code/token for verification
     *
     * @return array
     */
    public function actionCreate(){
        //Check the legal module is active or not
        $legalModule = Yii::$app->getModule('legal');
        if($legalModule !== null){
            $model = new RegistrationChecks();
            $legalKey = $model->load(Yii::$app->request->getBodyParam("legal", []), '');

            //check the legal key is present or not
            if(!$legalKey){
                return $this->returnError(400,"Registration failed, legal module is activated but key is missing");
            }

            //Only allow 1, true, false and 0 value of dataPrivacyCheck key
            $checkKeyValue = array(1,true,false,0);
            if(!in_array($model->dataPrivacyCheck,$checkKeyValue,true)){
                return $this->returnError(400,"Invalid value of dataPrivacyCheck key");
            }

            //check if the value of dataPrivacyCheck key is false
            if(!$model->dataPrivacyCheck){
                return $this->returnError(400,"Registration failed, dataPrivacyCheck key is false");
            }
        }
        $user = new User();
        $user->scenario = 'editAdmin';
        $user->load(Yii::$app->request->getBodyParam("account", []), '');
        $user->validate();

        $user->status = User::STATUS_NEED_APPROVAL;

        $profile = new Profile();
        $profile->scenario = 'editAdmin';
        $profile->load(Yii::$app->request->getBodyParam("profile", []), '');
        $profile->validate();

        $password = new Password();
        $password->scenario = 'registration';
        $password->load(Yii::$app->request->getBodyParam("password", []), '');

        //Check Password is equal to confirm password or not
        if($password->newPassword != $password->newPasswordConfirm){
            return $this->returnError(400,"Password doesn't match!");
        }
        $password->validate();

        if ($user->hasErrors() || $password->hasErrors() || $profile->hasErrors()) {
            return $this->returnError(400, 'Validation failed', [
                'password' => $password->getErrors(),
                'profile' => $profile->getErrors(),
                'account' => $user->getErrors(),
            ]);
        }

        if ($user->save()) {
            $profile->user_id = $user->id;
            $password->user_id = $user->id;
            $password->setPassword($password->newPassword);
            if ($profile->save() && $password->save()) {

                //Add user to default humhub group (which is USER Group whose ID=2)
                Yii::$app->runAction('smartVillage/user/group/member-add-without-auth',['id'=>GroupController::USER_DEFAULT_GROUP_ID,'userId'=>$user->id]);

                //Check the legal module is active or not
                if($legalModule!==null){
                    //Accept the privacy policy page
                    $this->acceptPrivacy($user);
                }
                return $this->inviteUser($user->email);
            }
        }

        Yii::error('Could not create validated user.', 'api');
        return $this->returnError(500, 'Internal error while save user!');
    }

    /**
     * Store the user details like email, token in user_invite table
     * Generate six digit random number code
     * @param $email
     * @return array|false
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function inviteUser($email){
        $invite = new Invite();
        $invite->scenario = 'invite';

        //If the site in maintenance mode
        if (Yii::$app->settings->get('maintenanceMode')) {
            Yii::$app->getView()->warn(ControllerAccess::getMaintenanceModeWarningText());
            return false;
        }

        $invite->source = Invite::SOURCE_SELF;
        $invite->language = Yii::$app->language;
        $invite->email = $email;

        // Delete existing invite for e-mail - but reuse token
        $existingInvite = Invite::findOne(['email' => $email]);
        if ($existingInvite !== null) {
            $invite->token = $existingInvite->token;
            $existingInvite->delete();
        }

        $invite->token = $this->generateCode();

        //We set validation false because we are not fill recaptcha value in API
        if($invite->save(false)){
            $this->sendMail($invite);
            return $this->returnSuccess("Registration successfully done, Please check your email, Code is send to your email address ".$email,200);
        }
        return $this->returnError(400,$invite->errors);

    }

    public function generateCode()
    {
        $code = random_int(100000, 999999);
        $checkCodeExist = Invite::find()->where(['token'=>$code])->one();
        if($checkCodeExist!=null) {
            $this->generateCode();
         }
        return $code;
        }

    /**
     * Send code to user email address
     * @param $invite
     * @return void
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function sendMail($invite)
    {

        /** @var Module $module */
        $module = Yii::$app->moduleManager->getModule('user');

        // User requested registration link by its self
        if ($invite->source == Invite::SOURCE_SELF) {
            $mail = Yii::$app->mailer->compose([
                'html' => '@humhub/modules/smartVillageAPI/views/email_template/UserInviteSelf',
                'text' => '@humhub/modules/smartVillageAPI/views/email_template/plain_text/UserInviteSelf'
            ], [
                'token' => $invite->token,
            ]);
            $mail->setTo($invite->email);
            $mail->setSubject(Yii::t('UserModule.base', 'Welcome to %appName%', ['%appName%' => Yii::$app->name]));
            $mail->send();
        }
    }

    /**
     * Check the user is exists or not
     * If, the user is exists then token will be generated by JWT
     * JWT token expire time (in seconds) can be set from module's configuration setting in website
     *
     * @param $username
     * @param $password
     * @return array
     */
    public function userLogin($user){

        if ($user === null) {
            return $this->returnError(400, 'Wrong username or password');
        }

        if (!$this->isUserEnabled($user)) {
            return $this->returnError(401, 'Invalid user!');
        }

        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'uid' => $user->id,
            'email' => $user->email
        ];

        $config = ConfigureForm::getInstance();
        if (!empty($config->jwtExpire)) {
            $data['exp'] = $issuedAt + (int)$config->jwtExpire;
        }

        $jwt = JWT::encode($data, $config->jwtKey, 'HS512');

        return $this->returnSuccess('Success', 200, [
            'auth_token' => $jwt,
            'expired_at' => (!isset($data['exp'])) ? 0 : $data['exp']
        ]);

    }

    /**
     * Accept the privacy policy, After the login
     * We are sending dataPrivacyCheck key with status(true/false), if it is true only then accept the privacy policy
     *
     * @param $user
     * @return array|bool|void
     */
    public function acceptPrivacy($user){
        //Accept the privacy policy
        $model = new RegistrationChecks();
        $model->load(Yii::$app->request->getBodyParam("legal", []), '');

            //Check privacy policy is enabled or not
            if($model->showPrivacyCheck()){

                //Find the privacy policy page
                $page = Page::getPage(Page::PAGE_KEY_PRIVACY_PROTECTION);

                if (!isset($page) || $page === null) {
                    throw new HttpException('404', 'Could not find privacy policy page!');
                }
                //Accept the privacy policy by setting the values
                $module = Yii::$app->getModule('legal');
                $module->settings->user($user)->set(RegistrationChecks::SETTING_KEY_PRIVACY, true);
                $module->settings->user($user)->set(RegistrationChecks::SETTING_KEY_PRIVACY . 'Time', time());
            }

            return true;

    }

    /**
     * verify the user with code/token
     * steps
     * 1. check the email id and code/token is correct or not
     * 2. If email and code is valid then change the user status from 2 to 1(enabled)
     * 3. delete the record from user_invite table
     * 4. and finally, user will directly log in the system
     * @return array|void
     */
    public function actionSignup(){
        $signup = Yii::$app->request->getBodyParam("signup",[]);

        if(!isset($signup['email'])){
            return $this->returnError(400,"Email is required");
        }
        if($signup['email']==null){
            return $this->returnError(400,"Email value cannot be blanked");
        }

        if(!isset($signup['token'])){
            return $this->returnError(400,"Token is required");
        }
        if($signup['token']==null){
            return $this->returnError(400,"Token value cannot be blanked");
        }

        $checkEmailCodeIsValid = Invite::find()->where(['email' =>$signup['email'], "token" => $signup['token']])->one();
        if(!isset($checkEmailCodeIsValid)){
            return $this->returnError(400,"Invalid email address or code!");
        }

        //Change the user status from 2 to 1
        $user = User::findOne(['email' => $signup['email']]);

        if($user!=null){
            $user->status = User::STATUS_ENABLED;
            if($user->save()){
                $checkEmailCodeIsValid->delete();

                return  $this->userLogin($user);
            }
        }


    }

}