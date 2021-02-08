<?php



/* @var $this View */
/* @var $model Task */

use app\models\db\Task;
use yii\helpers\Html;
use yii\helpers\Url;use yii\web\View;

$taskItem = Task::getTaskItem($model);
?>

<tr>
    <td><a target="_blank" href="<?=Url::toRoute(['user/outgoing-task-details', 'taskId' => $model->id])?>"><?=Html::encode($model->task_header) ?></a></td>
    <td><?="Создана: {$taskItem->getTaskCreateTimeText()}<br/>"?></td>
    <td><?="Для: <b>{$taskItem->target}</b><br/>"?></td>
    <td><?php
        switch ($model->task_status){
            case 'accepted':
                echo '<b class="text-success">Статус: принята к исполнению</b>';
                break;
            case 'created':
                echo '<b class="text-info">Статус: ожидает подтверждения</b>';
                break;
            case 'finished':
                echo '<b class="text-success">Статус: завершена</b>';
                break;
            case 'cancelled_by_initiator':
                echo '<b class="text-danger">Статус: отменена создателем</b>';
                break;
            case 'cancelled_by_executor':
                echo '<b class="text-danger">Статус: отменена исполнителем</b>';
                break;
        }
        ?>
    </td>
</tr>
