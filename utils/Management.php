<?php


namespace app\models;


use app\models\utils\ComHandler;
use app\utils\Telegram;
use Yii;

class Management
{
    /**
     * @param string $file
     */
    public static function startScript(string $file): void
    {
        if (is_file($file)) {
            $command = $file . ' ' . Yii::$app->basePath;
            $outFilePath = Yii::$app->basePath . '\\logs\\update_file.log';
            $outErrPath = Yii::$app->basePath . '\\logs\\update_err.log';
            $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
            ComHandler::runCommand($command);
            Telegram::sendDebug('Запущено обновление ПО через GitHub');
        }
    }
}