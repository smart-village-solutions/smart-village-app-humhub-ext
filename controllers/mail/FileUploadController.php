<?php

namespace humhub\modules\smartVillage\controllers\mail;

use humhub\modules\file\models\FileUpload;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\FileDefinitions;
use yii\web\UploadedFile;
use Yii;

class FileUploadController extends BaseController
{
    public function actionIndex($messageId){

        $contentRecord = Message::findOne(['id' => $messageId]);

        if ($contentRecord === null) {
            return $this->returnError(404, 'Content record not found!');
        }
        $hideInStream = 1;
        $uploadedFiles = UploadedFile::getInstancesByName('files');


        if (empty($uploadedFiles)) {
            return $this->returnError(400, 'No files to upload.');
        }

        $files = [];
        foreach($uploadedFiles as $cFile){
            $file = Yii::createObject(FileUpload::class);
            $file->setUploadedFile($cFile);

            if ($hideInStream) {
                $file->show_in_stream = false;
            }
            if ($file->save()) {

                $message = '!['.$file["file_name"].'](file-guid:'.$file["guid"].' "'.$file["file_name"].'")';
                $messageEntry = new MessageEntry([
                    'message_id' => $contentRecord->id,
                    'user_id' => Yii::$app->user->id,
                    'content' => $message
                ]);
                if($messageEntry->save()){
                    $messageEntry->refresh();
                    $messageEntry->notify();
                    $messageEntry->fileManager->attach($file);
                }

            }
            $files[] = $file;

        }
        if (empty($files)) {
            return $this->returnError(500, 'Internal error while saving file.');
        }

        $fileDefinitions = [];
        foreach ($files as $file) {
            $fileDefinitions[] = FileDefinitions::getFile($file);
        }

        return $this->returnSuccess('Files successfully uploaded.', 200, ['files' => $fileDefinitions]);

    }

}