<?php



/* @var $this View */
/* @var $model Claim */

use app\models\db\Claim;
use app\models\db\Task;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$taskInfo = Task::findOne($model->taskId);
$claimer = User::findIdentity($model->claimerId);

if($taskInfo === null || $claimer === null){
    echo "<tr><td colspan='3'>Задача не найдена</td></tr>";
}
else{
?>

<tr>
    <td><a target="_blank" href="<?=Url::toRoute(['executor/incoming-task-details', 'taskId' => $model->id])?>"><?=Html::encode($taskInfo->task_header) ?></a></td>
    <td><?="От: {$claimer->name}"?></td>
    <td><?=$model->claimText?></td>
    <td>
        <button class="btn btn-danger activator" data-action="/close-claim/<?=$model->id?>">В архив</button>
    </td>
</tr>
<?php
}