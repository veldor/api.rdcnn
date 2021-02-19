<?php

namespace app\controllers;

use app\models\db\Task;
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

class ExecutorController extends Controller
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
                            'incoming-task-details',
                            'accept-task',
                            'cancel-task',
                            'finish-task',
                            'cancel-task',
                            'select-order',
                        ],
                        'roles' => ['manager', 'handler'],
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
     * @param $taskId
     * @return string
     * @throws Exception
     */
    public function actionIncomingTaskDetails($taskId): string
    {
        $taskInfo = Task::getTaskInfo($taskId);
        return $this->render('incoming-task-details', ['taskInfo' => $taskInfo]);
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
                    'name' => 'incomingFilter',
                    'value' => serialize($filter),
                    'httpOnly' => false,
                ]));
            }
            if(!empty($order)){
                // добавлю куку сортировки
                $cookies->add(new Cookie([
                    'path' => '/',
                    'name' => 'incomingOrderBy',
                    'value' => $order,
                    'httpOnly' => false,
                ]));
            }
        }
        return $this->redirect('/#incomingTickets');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    #[ArrayShape(['status' => "int", 'message' => "string", 'reload' => "int"])] public function actionAcceptTask(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
            $taskId = Yii::$app->request->post('taskId');
            $daysForFinish = Yii::$app->request->post('daysForFinish');
            if(!empty($taskId) && !empty($daysForFinish)){
                /** @noinspection PhpParamsInspection */
                Task::setTaskConfirmed($taskId, $daysForFinish, Yii::$app->user->getIdentity());
                return ['status' => 1, 'message' => 'Заявка успешно принята', 'reload' => 1];
            }
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException|Throwable
     */
    #[ArrayShape(['status' => "int", 'message' => "string", 'reload' => "int"])] public function actionFinishTask(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
            $taskId = Yii::$app->request->post('taskId');
            if(!empty($taskId)){
                Task::setTaskFinished($taskId, Yii::$app->user->getIdentity());
                return ['status' => 1, 'message' => 'Спасибо за работу!', 'reload' => 1];
            }
        }
        throw new NotFoundHttpException();
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
            $cancelReason = Yii::$app->request->post('reason');
            if(!empty($taskId) && !empty($cancelReason)){
                Task::setTaskDismissed($taskId, $cancelReason, Yii::$app->user->getIdentity());
                return ['status' => 1, 'message' => 'Принято, задача отменена!', 'reload' => 1];
            }
        }
        throw new NotFoundHttpException();
    }


}
