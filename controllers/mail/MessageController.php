<?php

namespace humhub\modules\smartVillage\controllers\mail;

use humhub\modules\rest\components\BaseController;
use humhub\modules\mail\models\Message;
use yii\db\Query;
use Yii;

class MessageController extends BaseController
{
    /**
     * Fetching conversation's data from both message and user_message of a user
     * @return array
     */
     public function actionIndex(){
         $results = [];

         $messagesQuery = Message::find()
             ->innerJoin('user_message','message_id = id')
             ->where(['user_id' => Yii::$app->user->id]);

         $pagination = $this->handlePagination($messagesQuery);


         foreach ($messagesQuery->all() as $message) {
             $results[] = self::getMessage($message);
         }

         if($results!=null){
             return $this->returnPagination($messagesQuery, $pagination, $results);
         }else{
             return $this->returnError(400,"Conversation not found!");
         }
     }

    /**
     * Check the new unread message is found or not.
     * Based on the $checkNewMessage array, it will set unread/read status of conversation
     * When first time new unread message is found, the seen_at key will be null
     *
     * @param $message
     * @return array
     */
      public static function getMessage(Message $message){

          $checkNewMessage = new Query();
          $checkNewMessage->select("*")->from('message')
              ->leftJoin('user_message', 'user_message.message_id = message.id')
              ->where('message.updated_at > user_message.last_viewed OR user_message.last_viewed IS NULL')
              ->andWhere(['<>','message.updated_by',Yii::$app->user->id])
              ->andWhere(['user_message.user_id' => Yii::$app->user->id])
              ->andWhere(['message.id' => $message['id']]);

          $checkNewMessage = $checkNewMessage->count();

          return [
              'id' => $message->id,
              'title' => $message->title,
              'created_at' => $message->created_at,
              'created_by' => $message->created_by,
              'updated_at' => $message->updated_at,
              'updated_by' => $message->updated_by,
              'status' =>  $checkNewMessage>0?'unread':'read',
              'seen_at' => $message->userMessage->last_viewed,
          ];
      }
}