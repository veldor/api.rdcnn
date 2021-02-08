<?php


namespace app\models;

use app\exceptions\WrongArgumentException;
use app\models\db\Email;
use app\models\db\Phone;
use app\models\db\Task;
use app\utils\FirebaseHandler;
use app\utils\Telegram;
use app\utils\TimeHandler;
use Yii;
use yii\base\Model;

class TaskItem extends Model
{
    public const SCENARIO_NEW = 'new';

    public int $id;
    public string $initiator;
    public string $target = '';
    public string $executor;
    public int $task_creation_time;
    public string $task_accept_time;
    public string $task_planned_finish_time;
    public string $task_finish_time;
    public string $task_header = '';
    public string $task_body = '';
    public string $task_status = '';
    public string $executor_comment;
    public mixed $imageFile;
    public Task $task;
    public mixed $attachmentFile;

    public function scenarios(): array
    {
        $thisScenarios = [
            self::SCENARIO_NEW => ['initiator', 'task_header', 'task_body', 'target', 'imageFile', 'attachmentFile'],
        ];
        return array_merge($thisScenarios, parent::scenarios());
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            // атрибут required указывает, что name, email, subject, body обязательны для заполнения
            [['initiator', 'task_header', 'task_body', 'target'], 'required', 'on' => self::SCENARIO_NEW],
            ['task_header', 'required', 'message' => 'Please choose a task header.'],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, gif'],
            [['attachmentFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip'],
        ];
    }

    public function createTask(): void
    {
        $task = new Task();
        $task->initiator = $this->initiator;
        $task->task_header = $this->task_header;
        $task->task_body = $this->task_body;
        $task->task_creation_time = time();
        $task->task_status = 'created';
        $task->target = $this->target;
        $task->save();
        $this->uploadImageFile($task->id);
        $this->uploadAttachmentFile($task->id);
        FirebaseHandler::sendTaskCreated($task);
        Telegram::sendDebug("Добавлена новая задача");
    }

    /**
     * @param $taskId
     */
    public function uploadImageFile($taskId): void
    {
        //  сохраню файл, если он существует, в папку с изображениями к задачам
        if ($this->imageFile !== null) {
            $this->imageFile->saveAs(Yii::$app->getBasePath() . '/task_images/' . $taskId . '.' . $this->imageFile->extension);
        }
    }

    /**
     * @param $taskId
     */
    public function uploadAttachmentFile($taskId): void
    {
        //  сохраню файл, если он существует, в папку с изображениями к задачам
        if ($this->attachmentFile !== null) {
            $this->attachmentFile->saveAs(Yii::$app->getBasePath() . '/task_attachments/' . $taskId . '.' . $this->attachmentFile->extension);
        }
    }

    /**
     * @return string
     * @throws WrongArgumentException
     */
    public function getTaskStatusText(): string
    {
        switch ($this->task_status){
            case 'accepted':
                return '<b class="text-success">принята к исполнению</b>';
            case 'created':
                return '<b class="text-info">ожидает подтверждения</b>';
            case 'finished':
                return '<b class="text-success">завершена</b>';
            case 'cancelled_by_initiator':
                return '<b class="text-danger">отменена создателем</b>';
            case 'cancelled_by_executor':
                return '<b class="text-danger">отменена исполнителем</b>';
        }
        throw new WrongArgumentException("Неизвестный статус: {$this->task_status}");
    }

    /**
     * @return string
     */
    public function getTargetText(): string
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getExecutorInfo(): string
    {
        if(empty($this->executor)){
            return '<b class="text-danger">Пока не назначен</b>';
        }

        return "<b class='text-success'>Исполнитель: {$this->executor}</b><br/><div class='btn-group'>" . Email::getEmailButton($this->task->executor) . Phone::getPhoneButton($this->task->executor) . '</div>';
    }

    /**
     * @return string
     */
    public function getCustomerInfo(): string
    {
        return "<b class='text-success'>Заказчик: {$this->initiator}</b><br/><div class='btn-group'>" . Email::getEmailButton($this->task->initiator) . Phone::getPhoneButton($this->task->initiator) . '</div>';
    }

    public function getTaskCreateTimeText(): string
    {
        if($this->task_creation_time !== null && $this->task_creation_time > 0){
            return (new TimeHandler())->getTimeInfo($this->task_creation_time);
        }
        return '<b class="text-danger">Пока не назначено</b>';
    }
    public function getTaskAcceptTimeText(): string
    {
        if($this->task_accept_time !== null && $this->task_accept_time > 0){
            return (new TimeHandler())->getTimeInfo($this->task_accept_time);
        }
        return '<b class="text-danger">Пока не назначено</b>';
    }
    public function getTaskPlannedFinishTimeText(): string
    {
        if($this->task_planned_finish_time !== null && $this->task_planned_finish_time > 0){
            return (new TimeHandler())->getTimeInfo($this->task_planned_finish_time);
        }
        return '<b class="text-danger">Пока не назначено</b>';
    }
    public function getTaskFinishTimeText(): string
    {
        if($this->task_finish_time !== null && $this->task_finish_time > 0){
            return (new TimeHandler())->getTimeInfo($this->task_finish_time);
        }
        return '<b class="text-danger">Пока не назначено</b>';
    }

}