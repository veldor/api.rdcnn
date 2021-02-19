<?php



/* @var $this View */
/* @var $possibleExecutors User[] */
/* @var $taskId string */

use app\models\db\Role;
use app\models\db\Task;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;use yii\web\View;

?>

<ul>
    <?php
    if(!empty($possibleExecutors)){
        foreach ($possibleExecutors as $possibleExecutor) {
            echo "<li><a class='activator' href='#' data-action='/set-executor/{$taskId}/{$possibleExecutor->id}'><b>{$possibleExecutor->name}</b></a></li>";
        }
    }
    else{
        echo "<li>Для этого подразделения ещё не зарегистрированы исполнители</li>";
    }
    ?>
</ul>

<script>handleAjaxActivators();</script>
