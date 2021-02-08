<?php

namespace app\controllers;

use app\models\db\Task;
use app\models\TaskItem;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class FilesController extends Controller
{
    #[ArrayShape(['access' => "array"])] public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    throw new RuntimeException('У вас нет доступа к этой странице');
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'show-task-image',
                            'load-task-attachment',
                        ],
                        'roles' => ['user', 'manager', 'handler'],
                    ],
                ],
            ],
        ];
    }

    #[ArrayShape(['error' => "string[]"])] public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    public function actionShowTaskImage($imageName){
        $filteredName = stripcslashes($imageName);
        $file = Yii::$app->getBasePath() . '/task_images/' . $filteredName;
        if(is_file($file)){
            Yii::$app->response->sendFile($file, 'image.' . substr($imageName, strrpos($imageName, '.')), ['inline' => true]);
        }
        else{
            throw new NotFoundHttpException();
        }
    }

    public function actionLoadTaskAttachment($fileName){
        $filteredName = stripcslashes($fileName);
        $file = Yii::$app->getBasePath() . '/task_attachments/' . $filteredName;
        if(is_file($file)){
            Yii::$app->response->sendFile($file, 'attachment.' . substr($fileName, strrpos($fileName, '.'))
            );
        }
        else{
            throw new NotFoundHttpException();
        }
    }
}
