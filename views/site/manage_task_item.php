<?php



/* @var $this View */
/* @var $model Task */

use app\models\db\Role;
use app\models\db\Task;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;use yii\web\View;

$taskItem = Task::getTaskItem($model);
$initiatorInfo = User::findIdentity($model->initiator);
?>

<tr>
    <td><a target="_blank" href="<?=Url::toRoute(['executor/incoming-task-details', 'taskId' => $model->id])?>"><?=Html::encode($model->task_header) ?></a></td>
    <td><?="Создана: {$taskItem->getTaskCreateTimeText()}<br/>"?></td>
    <td><?="От: <b>{$initiatorInfo->name}</b><br/>" . Role::getPersonRole($initiatorInfo)?></td>
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
    <td>
        <button class="btn btn-danger activator" data-action="/delete-task/<?=$taskItem->id?>"><span class="glyphicon glyphicon-trash"></span></button>
    </td>
</tr>
