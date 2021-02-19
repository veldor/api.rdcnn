<?php



/* @var $this View */
/* @var $task Task */
/* @var $possibleRoles Role[] */

use app\models\db\Role;
use app\models\db\Task;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;use yii\web\View;

?>

<ul>
    <?php
    if(!empty($possibleRoles)){
        foreach ($possibleRoles as $possibleRole) {
            if($possibleRole->id !== $task->target){
                echo "<li><a class='activator' href='#' data-action='task/set-target/{$task->id}/{$possibleRole->id}'><b>{$possibleRole->role_description}</b></a></li>";
            }
        }
    }
    else{
        echo "<li>Для этого подразделения ещё не зарегистрированы исполнители</li>";
    }
    ?>
</ul>

<script>handleAjaxActivators();</script>
