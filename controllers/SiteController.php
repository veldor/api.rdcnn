<?php

namespace app\controllers;

use app\models\Api;
use app\models\db\Task;
use app\models\LoginForm;
use app\models\User;
use app\utils\Telegram;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ErrorAction;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'api') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

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
                            'index',
                            'error',
                        ],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'login'
                        ],
                        'roles' => ['?'],
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
    public function actionIndex(): Response|string
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect('/login');
        }
        $outgoingTasks = Task::getTaskList(Yii::$app->user->getId());
        return $this->render("index", ['outgoingTasks' => $outgoingTasks]);
    }

    /**
     * @throws Exception
     */
    public function actionLogin(): Response|string
    {
        if (Yii::$app->request->isGet) {
            $model = new LoginForm(['scenario' => LoginForm::SCENARIO_USER_LOGIN]);
            return $this->render('login', ['model' => $model]);
        }
        if (Yii::$app->request->isPost) {
            // попробую залогинить
            $model = new LoginForm(['scenario' => LoginForm::SCENARIO_USER_LOGIN]);
            $model->load(Yii::$app->request->post());
            if ($model->loginUser()) {
                Telegram::sendDebug("Залогинился пользователь " . $model->username);
                // загружаю личный кабинет пользователя
                return $this->redirect('/', 301);
            }
            return $this->render('login', ['model' => $model]);
        }
        throw new NotFoundHttpException();
    }

    public function actionApi(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Api::handleRequest();
    }
}
