<?php

namespace app\controllers;

use app\exceptions\WrongArgumentException;
use app\models\db\Task;
use app\models\EditableUser;
use app\models\User;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;
use Yii;
use yii\base\Model;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ManageController extends Controller
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
                            'existent-users',
                            'add-users',
                            'delete-user',
                            'delete-task',
                        ],
                        'roles' => ['manager'],
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
     * Displays homepage.
     *
     */
    public function actionExistentUsers(): Response
    {
        $users = EditableUser::getAllUsers();
        if (Model::loadMultiple($users, Yii::$app->request->post())) {
            if (Model::validateMultiple($users)) {
                foreach ($users as $user) {
                    $user->saveChanges();
                }
                Yii::$app->session->addFlash('success', 'Изменения сохранены');
            } else {
                Yii::$app->session->addFlash('danger', 'Не удалось сохранить изменения, проверьте введённые данные!');
            }
        }
        return $this->redirect('/#management');
    }

    public function actionAddUsers(): Response
    {
        if (!empty(Yii::$app->request->post('UserModel'))) {
            foreach (Yii::$app->request->post('UserModel') as $userItems) {
                if (!empty($userItems)) {
                    foreach ($userItems as $item) {
                        var_dump($item);
                        $newItem = new EditableUser(
                            0,
                            $item['login'],
                            $item['userName'],
                            $item['email'],
                            $item['phone'],
                            $item['role'],
                            $item['adminRights'],
                            $item['newPass']
                        );
                        // сохраню пользователя, если данные заполнены верно
                        try {
                            $newItem->saveNewUser();
                        } catch (WrongArgumentException $e) {
                            Yii::$app->session->addFlash('danger', 'Не удалось сохранить изменения, проверьте введённые данные! ошибка: ' . $e->getMessage());
                            return $this->redirect('/#management');
                        }
                    }
                }
            }
        }
        Yii::$app->session->addFlash('success', 'Изменения сохранены');
        return $this->redirect('/#management');
    }

    /**
     * @param $userId
     * @return array
     * @throws NotFoundHttpException
     */
    #[ArrayShape(['status' => "int", 'message' => "string"])] public function actionDeleteUser($userId): array
    {
        if(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            User::deleteUser($userId);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['status' => 1, 'message' => 'Пользователь удалён', 'reload' => true];
        }
        throw new NotFoundHttpException();
    }

    #[ArrayShape(['status' => "int", 'message' => "string", 'reload' => "bool"])] public function actionDeleteTask($taskId): array
    {
        if(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            Task::deleteTask($taskId);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['status' => 1, 'message' => 'Задача удалёна', 'reload' => true];
        }
        throw new NotFoundHttpException();
    }
}
