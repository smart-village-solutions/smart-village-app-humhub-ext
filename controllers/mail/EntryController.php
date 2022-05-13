<?php

namespace humhub\modules\smartVillage\controllers\mail;

use humhub\modules\rest\components\BaseController;
use humhub\modules\mail\controllers\rest\MessageController;
use humhub\modules\mail\models\UserMessage;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use yii\db\Query;
use Yii;

class EntryController extends BaseController
{
    /**
     * Fetch all the message entries of a particular conversation by passing messageId
     *
     * Working Flows:
     * step 1 : First it wil check the requested conversation is exists and current user has allowed to view the conversation.
     * step 2 : Then, it will check the any new unread message is present in that conversation.
     * step 3 : If yes, then it will update the conversation marked as read.
     * step 4 : And finally, fetch the all messages of that conversation with status key.
     *
     * @param $messageId
     * @return mixed
     */
     public function actionIndex($messageId){
         // Check the requested Conversation exists and allowed to view by current User
         MessageController::getMessage($messageId);

         //Check the Conversation is read/unread
         $checkNewMessage = new Query();
         $checkNewMessage->select("*")->from('message')
             ->leftJoin('user_message', 'user_message.message_id = message.id')
             ->where('message.updated_at > user_message.last_viewed OR user_message.last_viewed IS NULL')
             ->andWhere(['<>','message.updated_by',Yii::$app->user->id])
             ->andWhere(['user_message.user_id' => Yii::$app->user->id])
             ->andWhere(['message.id' => $messageId]);

         $checkNewMessage = $checkNewMessage->count();
         $messageMarkedStatus = false;
         if($checkNewMessage>0){
             //Update the conversation from unread to read.
             $messageMarkedStatus = true;
             $this->addReadStatus($messageId);
         }

         $results = [];
         $entriesQuery = MessageEntry::find()->where(['message_id' => $messageId]);

         $pagination = $this->handlePagination($entriesQuery);
         foreach ($entriesQuery->all() as $entry) {
             $results[] = self::getMessageEntry($entry,$messageMarkedStatus);
         }
         return $this->returnPagination($entriesQuery, $pagination, $results);

     }

    /**
     * This function will mark unread conversation as read.
     *
     * Working Flow:
     * step 1 : First, it will update the updated_at and updated_by column of message table.
     * step 2 : Second, it will update the last_viewed column value of user_message table.
     * step 3 : And finally, if all values will be updated then response will be sent true, otherwise false.
     *
     * @param $messageId
     * @return bool
     */
     public function addReadStatus($messageId){
        //Update the updated_at  column value in message table
        $message = Message::findOne(['id'=>$messageId]);
        if(isset($message) && !empty($message)) {
            $message->updated_at = date('Y-m-d H:i:s');

            if (!$message->save()) {
                Yii::error('Could not update the conversation status.', 'api');
                return $this->returnError(500, 'Message update failed!');
            }
        }

            //Update the last_viewed in user_message table
             $userMessage = UserMessage::findOne(['user_id'=> Yii::$app->user->id,'message_id'=> $messageId]);
             if(isset($userMessage) && !empty($userMessage)){
                $userMessage->last_viewed = date('Y-m-d H:i:s');
                if(!$userMessage->save()){
                    Yii::error('Could not update the last_viewed.', 'api');
                    return $this->returnError(500, 'User message not updated!');
                }
            }
    }

    /**
     * Return the result.
     *
     * @param MessageEntry $entry
     * @param $messageMarkedStatus
     * @return array
     */
    public static function getMessageEntry(MessageEntry $entry,$messageMarkedStatus)
    {
        return [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'file_id' => $entry->file_id,
            'content' => $entry->content,
            'created_at' => $entry->created_at,
            'created_by' => $entry->created_by,
            'updated_at' => $entry->updated_at,
            'updated_by' => $entry->updated_by,
            'status' => $messageMarkedStatus?'read':'already read'
        ];
    }


}