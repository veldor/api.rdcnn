<?php

use app\assets\OutgoingTaskDetailsAsset;
use app\models\TaskItem;
use app\utils\FileUtils;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;


/* @var $this View */
/* @var $taskInfo TaskItem */
OutgoingTaskDetailsAsset::register($this);
ShowLoadingAsset::register($this);


$this->title = 'Моя заявка';
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
            Назначение
        </td>
        <td>
            <?= $taskInfo->getTargetText() ?>
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
            Исполнитель
        </td>
        <td>
            <?= $taskInfo->getExecutorInfo() ?>
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
        <td>
            Комментарий исполнителя
        </td>
        <td>
            <?= $taskInfo->executor_comment ?>
        </td>
    </tr>
    <tr>
        <td>

            <?php
            if ($taskInfo->task_status === 'accepted' || $taskInfo->task_status === 'created') {
                echo "<button class=\"btn btn-default\" id=\"cancelTaskBtn\" data-task-id=\"{$taskInfo->id}\">Уже неактуально
            </button>";
            }
            ?>
        </td>
    </tr>
    </tbody>
</table>
