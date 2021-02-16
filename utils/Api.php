<?php


namespace app\utils;

use app\models\db\FirebaseClient;
use app\models\db\Task;
use app\models\User;
use Exception;
use JsonException;
use Throwable;
use Yii;
use yii\web\UploadedFile;

class Api
{
    private static array $data;

    /**
     * Обработка запроса
     * @return array
     * @throws Exception|Throwable
     */
    public static function handleRequest(): array
    {
        if (!empty($_POST)) {
            $command = Yii::$app->request->post('cmd');
            if (!empty($command) && $command === 'newTask') {
                return self::createNewTask();
            }
            return ['status' => 'success', 'message' => serialize($_POST)];
        }
        try {
            self::$data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            if (!empty(self::$data['cmd'])) {
                switch (self::$data['cmd']) {
                    case 'login':
                        return self::login();
                    case 'getTaskList':
                        return self::getTaskList();
                    case 'getIncomingTaskList':
                        return self::getIncomingTaskList();
                    case 'newTask':
                        return self::createNewTask();
                    case 'getTaskInfo':
                        return self::getTaskInfo();
                    case 'confirmTask':
                        return self::confirmTask();
                    case 'cancelTask':
                        return self::cancelTask();
                    case 'finishTask':
                        return self::finishTask();
                    case 'getNewTasks':
                        return self::getNewTasks();
                    case 'dismissTask':
                        return self::dismissTask();
                }
            }
            return ['status' => 'failed', 'message' => 'invalid data'];
        } catch (JsonException $e) {
            return ['status' => 'failed', 'message' => 'invalid json: ' . $e->getMessage()];
        }
    }

    private static function login(): array
    {
        $login = self::$data['login'];
        $password = self::$data['pass'];
        $firebaseToken = self::$data['firebase_token'];
        $user = User::findByUsername($login);
        if ($user !== null) {
            if ($user->validatePassword($password)) {
                // добавлю токен
                FirebaseClient::add($user->id, $firebaseToken);
                // всё верно, верну токен доступа
                Telegram::sendDebug("Успешный вход: " . $user->name);
                return ['status' => 'success', 'token' => $user->access_token, 'role' => $user->role];
            }
            Telegram::sendDebug("Неудачная попытка входа " . $user->name . ", логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
            return ['status' => 'failed', 'message' => 'invalid data'];
        }
        Telegram::sendDebug("Неудачная попытка входа, логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function getTaskList(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $filter = Yii::$app->request->post('filter');
                $sort = Yii::$app->request->post('sort');
                $sortReverse = Yii::$app->request->post('sortReverse') === '1';
                $limit = Yii::$app->request->post('limit');
                $page = Yii::$app->request->post('page');
                $list = Task::getTaskList($user->id, $filter, $sort, $sortReverse, $limit, $page);
                return ['status' => 'success', 'list' => $list];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function createNewTask(): array
    {
        // получу учётную запись по токену
        $token = Yii::$app->request->post('token');
        if (!empty($token)) {
            $user = User::findOne(['access_token' => $token]);
            if ($user !== null) {
                // добавлю новую задачу
                $theme = Yii::$app->request->post('title');
                $text = Yii::$app->request->post('text');
                $target = Yii::$app->request->post('target');
                $t = match ($target) {
                    'IT-отдел' => 2,
                    'Инженерная служба' => 3,
                    'Офис' => 4,
                    default => 0
                };
                $task = new Task();
                $task->initiator = $user->id;
                $task->task_header = $theme ?: 'Без названия';
                $task->task_body = $text;
                $task->task_creation_time = time();
                $task->task_status = 'created';
                $task->target = $t;
                $task->save();
                // добавлю фото при наличии
                $image = UploadedFile::getInstanceByName('task_image');
                if ($image !== null) {
                    $image->saveAs(Yii::$app->getBasePath() . '/task_images/' . $task->id . '.jpg');
                }
                // добавлю документ при наличии
                $zip = UploadedFile::getInstanceByName('task_document');
                if ($zip !== null) {
                    $zip->saveAs(Yii::$app->getBasePath() . '/task_attachments/' . $task->id . '.zip');
                }
                FirebaseHandler::sendTaskCreated($task);
                Telegram::sendDebug("Добавлена новая задача");
                return ['status' => 'success'];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function getIncomingTaskList(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $tasks = Task::getTasksForExecutor($user);
                $result = [];
                if (!empty($tasks)) {
                    /** @var Task $item */
                    foreach ($tasks as $item) {
                        $result[] = Task::getTaskItem($item);
                    }
                }
                return ['status' => 'success', 'list' => $result];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return array
     * @throws Exception
     */
    private static function getTaskInfo(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                return ['status' => 'success', 'task_info' => Task::getTaskInfo($taskId)];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private static function confirmTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                $plannedTime = self::$data['plannedTime'];
                Task::setTaskConfirmed($taskId, $plannedTime, $user);
                return self::getTaskInfo();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private static function cancelTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                Task::setTaskCancelled($taskId);
                return self::getTaskInfo();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return array
     */
    private static function getNewTasks(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                return ['status' => 'success', 'new_tasks_count' => Task::findNew($user)];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     * @throws Exception|Throwable
     */
    private static function finishTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                Task::setTaskFinished($taskId, $user);
                return self::getTaskInfo();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     */
    private static function dismissTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                $reason = self::$data['reason'];
                Task::setTaskDismissed($taskId, $reason);
                return ['status' => 'success'];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return void
     */
    private static function getImage(): void
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                try {
                    FileUtils::loadTaskImage($taskId);
                } catch (Throwable $e) {
                    Telegram::sendDebug($e->getMessage());
                }
            }
        }
    }

    /**
     * @return void
     */
    private static function getFile(): void
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = User::findIdentityByAccessToken($token);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                try {
                    FileUtils::loadTaskAttachment($taskId);
                } catch (Exception $e) {
                    Telegram::sendDebug($e->getMessage());
                }
            }
        }
    }

    /**
     * @throws JsonException
     */
    public static function handleFileRequest(): void
    {
        self::$data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!empty(self::$data['cmd'])) {
            switch (self::$data['cmd']) {
                case 'getImage':
                    self::getImage();
                    break;
                case 'getAttachment':
                    self::getFile();
                    break;
            }
        }
    }
}