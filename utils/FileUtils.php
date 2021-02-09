<?php


namespace app\utils;


use Yii;

class FileUtils
{

    public static function getTaskImage(int $id): ?string
    {
        $dir = Yii::$app->getBasePath() . '/task_images';
        if(is_dir($dir)){
            $fileList = scandir($dir);
            if(!empty($fileList)){
                foreach ($fileList as $item) {
                    if(str_starts_with($item, "$id.")){
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
        if(is_dir($dir)){
            $fileList = scandir($dir);
            if(!empty($fileList)){
                foreach ($fileList as $item) {
                    if(str_starts_with($item, "$id.")){
                        return "/task_attachments/$item";
                    }
                }
            }
        }
        return null;
    }
}