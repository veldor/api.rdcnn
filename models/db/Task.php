<?php


namespace app\models\db;

use app\models\TaskItem;
use app\models\User;
use app\utils\FirebaseHandler;
use Throwable;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Exception;

/**
 * @property int $id [int(10) unsigned]
 * @property int $initiator [int(10) unsigned]
 * @property int $target [int(10) unsigned]
 * @property int $executor [int(10) unsigned]
 * @property int $task_creation_time [int(10) unsigned]
 * @property int $task_accept_time [int(10) unsigned]
 * @property int $task_planned_finish_time [int(10) unsigned]
 * @property int $task_finish_time [int(10) unsigned]
 * @property string $task_header [varchar(255)]
 * @property string $task_body [varchar(255)]
 * @property string $task_status [varchar(255)]
 * @property string $executor_comment [varchar(255)]
 */
class Task extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'tasks';
    }

    /**
     * @param int $id
     * @return TaskItem[]
     */
    public static function getTaskList(int $id): array
    {
        $existent = self::find()->where(['initiator' => $id])->all();
        $result = [];
        if (!empty($existent)) {
            /** @var Task $item */
            foreach ($existent as $item) {
                $result[] = self::getTaskItem($item);
            }
        }
        return $result;
    }

    /**
     * @param Task $item
     * @return TaskItem
     */
    public static function getTaskItem(Task $item): TaskItem
    {
        $task = new TaskItem();
        $task->task = $item;
        $task->id = $item->id;
        $initiator = User::findIdentity($item->initiator);
        if ($initiator !== null) {
            $task->initiator = $initiator->name;
        }
        if (!empty($item->executor)) {
            $executor = User::findIdentity($item->executor);
            if ($executor !== null) {
                $task->executor = $executor->name;
            }
        } else {
            $task->executor = '';
        }
        /** @var Role $target */
        $target = Role::findOne($item->target);
        if ($target !== null) {
            $task->target = $target->role_name;
        }
        $task->task_creation_time = $item->task_creation_time;
        $task->task_accept_time = $item->task_accept_time ?: 0;
        $task->task_planned_finish_time = $item->task_planned_finish_time ?: 0;
        $task->task_finish_time = $item->task_finish_time ?: 0;
        $task->task_header = $item->task_header ?: '';
        $task->task_body = $item->task_body;
        $task->task_status = $item->task_status;
        $task->executor_comment = $item->executor_comment ?: '';
        return $task;
    }

    /**
     * @param $taskId
     * @return TaskItem
     * @throws \Exception
     */
    public static function getTaskInfo($taskId): TaskItem
    {
        $item = self::findOne($taskId);
        if ($item !== null) {
            return self::getTaskItem($item);
        }
        throw new Exception("Неверный идентификатор задачи");
    }

    public static function setTaskConfirmed($taskId, $adysForFinish, User $user): void
    {
        $item = self::findOne($taskId);
        if ($item !== null) {
            $now = time();
            $item->task_accept_time = $now;
            $item->task_planned_finish_time = $now + $adysForFinish * 86400;
            $item->executor = $user->id;
            $item->task_status = 'accepted';
            $item->save();
            // отправлю сообщение инициатору о том, что задача принята
            FirebaseHandler::sendTaskAccepted($item);
        }
    }

    public static function setTaskCancelled($taskId): void
    {
        $item = self::findOne($taskId);
        if ($item !== null) {
            $now = time();
            $item->task_finish_time = $now;
            $item->task_status = 'cancelled_by_initiator';
            $item->save();
            FirebaseHandler::sendTaskCancelled($item);
        }
    }

    public static function findNew(User $user): int
    {
        return self::find()->where(['target' => $user->role, 'task_status' => 'created'])->count();
    }

    /**
     * @param $taskId
     * @throws Throwable
     */
    public static function setTaskFinished($taskId): void
    {
        $item = self::findOne($taskId);
        if ($item !== null && $item->task_status !== 'finished' && $item->executor === Yii::$app->user->getIdentity()->getId()) {
            $now = time();
            $item->task_finish_time = $now;
            $item->task_status = 'finished';
            $item->save();
            // отправлю сообщение инициатору о том, что задача принята
            FirebaseHandler::sendTaskFinished($item);
        }
    }

    public static function setTaskDismissed($taskId, $reason): void
    {
        $item = self::findOne($taskId);
        if ($item !== null) {
            $now = time();
            $item->task_finish_time = $now;
            $item->task_status = 'cancelled_by_executor';
            $item->executor_comment = $reason;
            $item->save();
            // отправлю сообщение инициатору о том, что задача отменена
            FirebaseHandler::sendTaskDismissed($item);
        }
    }
}