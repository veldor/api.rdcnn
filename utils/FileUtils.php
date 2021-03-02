<?php


namespace app\utils;


use app\models\db\Task;
use Yii;
use yii\web\NotFoundHttpException;

class FileUtils
{

    public static function getTaskImage(int $id): ?string
    {
        $dir = Yii::$app->getBasePath() . '/task_images';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $item) {
                    if (str_starts_with($item, "$id.")) {
                        return "/task_images/$item";
                    }
                }
            }
        }
        return null;
    }

    public static function getAttachedFile(int $id): ?string
    {
        $dir = Yii::$app->getBasePath() . '/task_attachments';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $item) {
                    if (str_starts_with($item, "$id.")) {
                        return "/task_attachments/$item";
                    }
                }
            }
        }
        return null;
    }

    public static function isAttachment(Task $item): bool
    {
        return is_file(Yii::$app->getBasePath() . '/task_attachments/' . $item->id . '.zip');
    }

    public static function isImage(Task $item): bool
    {
        $dir = Yii::$app->getBasePath() . '/task_images';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $file) {
                    if (str_starts_with($file, "{$item->id}.")) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function loadTaskImage(string $taskId): void
    {
        $dir = Yii::$app->getBasePath() . '/task_images';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $item) {
                    if (str_starts_with($item, "$taskId.")) {
                        $filename = $dir = Yii::$app->getBasePath() . '/task_images/' . $item;
                        if(is_file($filename)){
                            Yii::$app->response->sendFile($filename, 'photo.jpg');
                            Yii::$app->response->send();
                        }
                    }
                }
            }
        }
    }
    public static function loadTaskAttachment(string $taskId)
    {
        $dir = Yii::$app->getBasePath() . '/task_attachments';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $item) {
                    if (str_starts_with($item, "$taskId.")) {
                        $filename = $dir = Yii::$app->getBasePath() . '/task_attachments/' . $item;
                        if(is_file($filename)){
                            Yii::$app->response->sendFile($filename, 'photo.zip');
                            Yii::$app->response->send();
                        }
                    }
                }
            }
        }
        throw new NotFoundHttpException();
    }

    public static function showScheduleHash()
    {
        $file = Yii::$app->getBasePath() . '/web/files/schedule.xlsx';
        if(is_file($file)){
            echo hash_file('md5', 'example.txt');
        }
        echo 0;
    }
}