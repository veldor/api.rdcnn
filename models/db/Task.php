<?php


namespace app\models\db;

use app\models\TaskItem;
use app\models\User;
use app\utils\FileUtils;
use app\utils\FirebaseHandler;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;

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
     * @param null $filter
     * @param null $sort
     * @param bool $revertSort
     * @param null $limit
     * @param null $page
     * @return TaskItem[]
     */
    public static function getTaskList(int $id, $filter = null, $sort = null, $revertSort = false, $limit = null, $page = null): array
    {
        $query = self::find()->where(['initiator' => $id]);
        $incomingFilterValue = [];
        if ($filter !== null) {
            $filterArray = str_split($filter);
            if ($filterArray[0] === '1') {
                if ($revertSort) {
                    $incomingFilterValue[] = "created";
                } else {
                    $incomingFilterValue[] = "created DESC";
                }
            }
            if ($filterArray[1] === '1') {
                if ($revertSort) {
                    $incomingFilterValue[] = "accepted";
                } else {
                    $incomingFilterValue[] = "accepted DESC";
                }
            }
            if ($filterArray[2] === '1') {
                if ($revertSort) {
                    $incomingFilterValue[] = "finished";
                } else {
                    $incomingFilterValue[] = "finished DESC";
                }
            }
            if ($filterArray[3] === '1') {
                if ($revertSort) {
                    $incomingFilterValue[] = "cancelled_by_initiator";
                } else {
                    $incomingFilterValue[] = "cancelled_by_initiator DESC";
                }
            }
            if ($filterArray[4] === '1') {
                if ($revertSort) {
                    $incomingFilterValue[] = "cancelled_by_executor";
                } else {
                    $incomingFilterValue[] = "cancelled_by_executor DESC";
                }
            }
            $query->andWhere(['task_status' => $incomingFilterValue]);
        }
        if ($sort !== null) {
            switch ($sort) {
                case "0":
                    $query->orderBy('task_status');
                    break;
                case "1":
                    $query->orderBy('task_header');
                    break;
                case "2":
                    $query->orderBy('task_creation_time');
                    break;
                case "3":
                    $query->orderBy('target');
                    break;
                case "4":
                    $query->orderBy('task_finish_time');
                    break;
            }
        }
        if ($limit !== null) {
            $query->limit($limit);
        } else {
            $limit = 0;
        }
        if ($page !== null) {
            $query->offset($limit * $page);
        }
        $existent = $query->all();
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
     * @param bool $skipAddLinkToTask
     * @return TaskItem
     */
    public static function getTaskItem(Task $item): TaskItem
    {
        $task = new TaskItem();
        $task->id = $item->id;
        $initiator = User::findIdentity($item->initiator);
        if ($initiator !== null) {
            $task->initiator = $initiator->name;
            $task->initiatorEmail = Email::getFirstEmail($initiator) ?? '';
            $task->initiatorPhone = Phone::getFirstPhone($initiator) ?? '';
        }
        if (!empty($item->executor)) {
            $executor = User::findIdentity($item->executor);
            if ($executor !== null) {
                $task->executor = $executor->name;
                $task->executorEmail = Email::getFirstEmail($executor) ?? '';
                $task->executorPhone = Phone::getFirstPhone($executor) ?? '';
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
        $task->imageFile = FileUtils::isImage($item);
        $task->attachmentFile = FileUtils::isAttachment($item);
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
    public static function setTaskFinished($taskId, User $executor): void
    {
        $item = self::findOne($taskId);
        if ($item !== null && $item->task_status !== 'finished' && $item->executor === $executor->getId()) {
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

    public static function getTasksForExecutor(User $user): array
    {
        return self::find()->where(['executor' => $user->id])->orWhere(['executor' => null, 'target' => $user->role])->all();
    }

    /**
     * @param $taskId
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function deleteTask($taskId): void
    {
        if (!empty($taskId)) {
            $task = self::findOne($taskId);
            if ($task !== null) {
                $task->delete();
            }
        }
    }
}