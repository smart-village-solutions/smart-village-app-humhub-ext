<?php

namespace humhub\modules\smartVillage\controllers\mail;

use humhub\modules\rest\components\BaseController;
use yii\db\Query;
use Yii;
use yii\data\Pagination;

class MessageController extends BaseController
{
    /**
     * Fetching conversation's data from both message and user_message of a user
     * @return array
     */
     public function actionIndex(){
         $results = [];

         $messagesQuery = new Query();
         $messagesQuery->select("*")->from('message')
                        ->leftJoin('user_message', 'user_message.message_id = message.id')
                        ->where(['user_id' => Yii::$app->user->id]);

         foreach ($messagesQuery->all() as $message) {
             $results[] = self::getMessage($message);
         }
         return $results;
     }

    /**
     * Check the new unread message is found or not.
     * Based on the $checkNewMessage array, it will set unread/read status of conversation
     * When first time new unread message is found, the seen_at key will be null
     *
     * @param $message
     * @return array
     */
      public static function getMessage($message){
          $checkNewMessage = new Query();
          $checkNewMessage->select("*")->from('message')
              ->leftJoin('user_message', 'user_message.message_id = message.id')
              ->where('message.updated_at > user_message.last_viewed OR user_message.last_viewed IS NULL')
              ->andWhere(['<>','message.updated_by',Yii::$app->user->id])
              ->andWhere(['user_message.user_id' => Yii::$app->user->id])
              ->andWhere(['message.id' => $message['id']]);

          $checkNewMessage = $checkNewMessage->count();
          return [
              'id' => $message['id'],
              'title' => $message['title'],
              'created_at' => $message['created_at'],
              'created_by' => $message['created_by'],
              'updated_at' => $message['updated_at'],
              'updated_by' => $message['updated_by'],
              'status' => $checkNewMessage>0?'unread':'read',
              'seen_at' => $message['last_viewed'],
          ];
      }

    /**
     * Handles pagination
     *
     * @param Query $query
     * @param int $limit
     * @return Pagination the pagination
     */
      protected function handlePagination(Query $query, $limit = 100)
    {
        $limit = (int)Yii::$app->request->get('limit', $limit);
        $page = (int)Yii::$app->request->get('page', 1);

        if ($limit > 100) {
            $limit = 100;
        }

        $page--;

        $countQuery = clone $query;
        $pagination = new Pagination(['totalCount' => $countQuery->count()]);
        $pagination->setPage($page);
        $pagination->setPageSize($limit);

        $query->offset($pagination->offset);
        $query->limit($pagination->limit);

        return $pagination;
    }

      /**
     * Generates pagination response
     *
     * @param Query $query
     * @param Pagination $pagination
     * @param $data array
     * @return array
     */
      protected function returnPagination(Query $query, Pagination $pagination, $data)
    {
        return [
            'total' => $pagination->totalCount,
            'page' => $pagination->getPage() + 1,
            'pages' => $pagination->getPageCount(),
            'links' => $pagination->getLinks(),
            'results' => $data,
        ];
    }
}