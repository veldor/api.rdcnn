<?php

use app\assets\IncomingTaskDetailsAsset;
use app\models\db\Email;
use app\models\db\Phone;
use app\models\TaskItem;
use app\utils\FileUtils;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;


/* @var $this View */
/* @var $taskInfo TaskItem */
IncomingTaskDetailsAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Входящая задача';

?>

<table class="table table-condenced table-striped table-hover">
    <tbody>
    <tr>
        <td colspan="2" class="text-center">
            <?php
            // если есть картинка- отображу превью
            $image = FileUtils::getTaskImage($taskInfo->id);
            if ($image !== null) {
                echo '<a target="_blank" href="' . $image . '"><img src="' . $image . '" alt="task_image" class="img-thumbnail preview"/></a>';
            }
            ?>
        </td>
    </tr>
    <?php
    $attachedFile = FileUtils::getAttachedFile($taskInfo->id);
    if ($attachedFile !== null) {
        echo <<<EOT
    <tr>
        <td>
            Вложенный файл
        </td>
        <td>
            <a target="_blank" href="$attachedFile" class="btn btn-default"><span class="glyphicon glyphicon-arrow-down"></span> Скачать вложение</a>
        </td>
    </tr>
EOT;

    }
    ?>
    <tr>
        <td>
            Тема
        </td>
        <td>
            <?= $taskInfo->task_header ?>
        </td>
    </tr>
    <tr>
        <td>
            Содержание
        </td>
        <td>
            <?= $taskInfo->task_body ?>
        </td>
    </tr>
    <tr>
        <td>
            Статус задачи
        </td>
        <td>
            <?= $taskInfo->getTaskStatusText() ?>
        </td>
    </tr>

    <tr>
        <td>
            Заказчик
        </td>
        <td>
            <?= $taskInfo->getCustomerInfo() ?>
        </td>
    </tr>
    <tr>
        <td>
            Время создания задачи
        </td>
        <td>
            <?= $taskInfo->getTaskCreateTimeText() ?>
        </td>
    </tr>
    <tr>
        <td>
            Время передачи задачи исполнителю
        </td>
        <td>
            <?= $taskInfo->getTaskAcceptTimeText() ?>
        </td>
    </tr>
    <tr>
        <td>
            Планируемое время завершения задачи
        </td>
        <td>
            <?= $taskInfo->getTaskPlannedFinishTimeText() ?>

        </td>
    </tr>
    <tr>
        <td>
            Время завершения задачи
        </td>
        <td>
            <?= $taskInfo->getTaskFinishTimeText() ?>

        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="btn-group">
                <?php
                echo Email::getEmailButton($taskInfo->initiator);
                echo Phone::getPhoneButton($taskInfo->initiator);
                if ($taskInfo->task_status === 'created') {
                    echo "<button id='acceptTaskBtn' class='btn btn-success' data-task-id=\"{$taskInfo->id}\">Принять задачу</button><button id='cancelTaskBtn' class='btn btn-danger' data-task-id=\"{$taskInfo->id}\">Отказаться от выполнения задачи</button>";
                } else if ($taskInfo->task_status === 'accepted') {
                    echo "<button id='finishTaskBtn' class='btn btn-success' data-task-id=\"{$taskInfo->id}\">Отметить задачу выполненной</button><button id='cancelTaskBtn' class='btn btn-danger' data-task-id=\"{$taskInfo->id}\">Отказаться от выполнения задачи</button>";
                }
                ?>
            </div>
        </td>
    </tr>
    </tbody>
</table>
