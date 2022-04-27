<?php

namespace humhub\modules\smartVillage\controllers\auth\user;

use humhub\modules\smartVillage\components\AuthBaseController;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;
use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\models\ConfigureForm;
use Firebase\JWT\JWT;

/*
  Register user without bearer token
  steps
  1. first, The user register in 'user' table.
  2. New user will automatically add in default humhub group, which is 'USER' Group whose (id=2)
  3. and finally, User will login with credentials.

  Note: We are using default password : 'SuperSecretPassword'
*/
class UserController extends AuthBaseController
{
    public function actionCreate()
    {
        $user = new User();
        $user->scenario = 'editAdmin';
        $user->load(Yii::$app->request->getBodyParam("account", []), '');
        $user->validate();

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
                Yii::$app->runAction('smartVillage/auth/user/group/member-add-without-auth',['id'=>GroupController::USER_DEFAULT_GROUP_ID,'userId'=>$user->id]);

                //Login New User with username and default password (SuperSecretPassword)
                return $this->userLogin($user->username,$password->newPassword);
            }
        }

        Yii::error('Could not create validated user.', 'api');
        return $this->returnError(500, 'Internal error while save user!');
    }

    public function userLogin($username,$password){

        $user = AuthController::authByUserAndPassword($username,$password); //Authenticate the user

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
}