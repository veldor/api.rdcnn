<?php

namespace app\controllers;

use app\models\db\Task;
use app\models\TaskItem;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\ErrorAction;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class UserController extends Controller
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
                            'get-outgoing-form',
                            'add-outgoing-task',
                            'outgoing-task-details',
                            'cancel-task',
                            'select-order',
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

    /**
     * @return array
     * @throws Throwable
     */
    #[ArrayShape(['status' => "int", 'form' => "string"])] public function actionGetOutgoingForm(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $user = Yii::$app->user->getIdentity();
        $model = new TaskItem(['scenario' => TaskItem::SCENARIO_NEW]);
        $view = $this->renderAjax('outgoingTask', ['model' => $model, 'user' => $user]);
        return ['status' => 1, 'form' => $view];
    }

    /**
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionAddOutgoingTask(): Response
    {
        if (Yii::$app->request->isPost) {
            $model = new TaskItem(['scenario' => TaskItem::SCENARIO_NEW]);
            $model->load(Yii::$app->request->post());
            if ($model->validate()) {
                // подгружу файлы при наличии
                $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
                $model->attachmentFile = UploadedFile::getInstance($model, 'attachmentFile');
                $model->createTask();
                Yii::$app->session->addFlash('success', 'Задача добавлена.');

            } else {
                Yii::$app->session->addFlash('danger', 'Обнаружены ошибки ' . serialize($model->errors));
            }
            return $this->redirect('/#outgoingTickets');
        }
        throw new NotFoundHttpException();
    }

    /**
     * @param $taskId
     * @return string
     * @throws Exception
     */
    public function actionOutgoingTaskDetails($taskId): string
    {
        $taskInfo = Task::getTaskInfo($taskId);
        return $this->render('outgoing-task-details', ['taskInfo' => $taskInfo]);
    }

    /**
     * @return array
     * @throws NotFoundHttpException|Throwable
     */
    #[ArrayShape(['status' => "int", 'message' => "string", 'reload' => "int"])] public function actionCancelTask(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
            $taskId = Yii::$app->request->post('taskId');
            if(!empty($taskId)){
                Task::setTaskCancelled($taskId, Yii::$app->user->getIdentity());
                return ['status' => 1, 'message' => 'Заявка отменена', 'reload' => 1];
            }
        }
        throw new NotFoundHttpException();
    }

    public function actionSelectOrder(): Response
    {
        if (Yii::$app->request->isPost){
            $order = Yii::$app->request->post('orderBy');
            $filter = Yii::$app->request->post('filtered');
            $cookies = Yii::$app->response->cookies;
            // добавлю куку фильтрации результатов
            if(!empty($filter)){
                // добавлю куку сортировки
                $cookies->add(new Cookie([
                    'path' => '/',
                    'name' => 'outgoingFilter',
                    'value' => serialize($filter),
                    'httpOnly' => false,
                ]));
            }
            if(!empty($order)){
                // добавлю куку сортировки
                $cookies->add(new Cookie([
                    'path' => '/',
                    'name' => 'outgoingOrderBy',
                    'value' => $order,
                    'httpOnly' => false,
                ]));
            }
        }
        return $this->redirect('/#outgoingTickets');
    }
}
