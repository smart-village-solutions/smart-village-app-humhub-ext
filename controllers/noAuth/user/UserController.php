<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\noAuth\user;

use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\components\NoAuthBaseController;
use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\controllers\user\GroupController;
use humhub\modules\rest\controllers\noAuth\user\UserDefinitions;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;
use Firebase\JWT\JWT;


/**
 * Class AccountController
 */
class UserController extends NoAuthBaseController
{

    /**
     * @inheritdoc
     */
//    noAuth function getAccessRules()
//    {
//        return [
//            ['permissions' => [ManageUsers::class]],
//        ];
//    }


    /**
     * Get User by username
     * 
     * @param string $username the username searched
     * @return UserDefinitions
     * @throws HttpException
     */
    public function actionGetByUsername($username)
    {
        $user = User::findOne(['username' => $username]);

        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }
        
        return $this->actionView($user->id);
    }


    /**
     * Get User by email
     * 
     * @param string $email the email searched
     * @return UserDefinitions
     * @throws HttpException
     */

    public function actionView($id)
    {
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        return UserDefinitions::getUser($user);
    }

    /**
     *
     * @return array
     * @throws HttpException
     */
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

        if($password->newPasswordConfirm != $password->newPassword){
            return $this->returnError(400,'Password does not match');
        }
        $password->newPasswordConfirm = $password->newPassword;
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
//                if($password->mustChangePassword) {
//                    $user->setMustChangePassword(true);
//
//                }
                 $this->actionView($user->id);
                 return $this->actionLoginUser($user->username,$password->newPassword);
            }
        }

        Yii::error('Could not create validated user.', 'api');
        return $this->returnError(500, 'Internal error while save user!');
    }

    public function actionLoginUser($username,$password)
    {
        $user = AuthController::authByUserAndPassword($username, $password);

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