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

    public static function loadTaskImage(string $taskId)
    {
        $dir = Yii::$app->getBasePath() . '/task_images';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $item) {
                    if (str_starts_with($item, "$taskId.")) {
                        Yii::$app->response->sendFile("$dir/$item", $item);
                        return;
                    }
                }
            }
        }
        throw new NotFoundHttpException();
    }
    public static function loadTaskAttachment(string $taskId)
    {
        $dir = Yii::$app->getBasePath() . '/task_attachments';
        if (is_dir($dir)) {
            $fileList = scandir($dir);
            if (!empty($fileList)) {
                foreach ($fileList as $item) {
                    if (str_starts_with($item, "$taskId.")) {
                        Yii::$app->response->sendFile("$dir/$item", $item);
                        return;
                    }
                }
            }
        }
        throw new NotFoundHttpException();
    }
}