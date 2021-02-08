<?php

use app\models\db\Role;
use app\models\TaskItem;
use app\models\User;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $model TaskItem */
/* @var $user User */

$form = ActiveForm::begin([
    'id' => 'addOutgoingTaskForm',
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
    'validateOnChange' => true,
    'validateOnSubmit' => true,
    'validateOnBlur' => true,
    'action' => '/user/add-outgoing-task'
]);
echo $form->field($model, 'initiator')->hiddenInput(['value' => $user->getId()])->label(false);
echo $form->field($model, 'task_header')->textInput()->label("Заголовок задачи");
echo $form->field($model, 'task_body')->textarea(['cols' => 3])->label('Суть задачи');
echo $form->field($model,'target')->dropDownList(Role::getExecutorsList(),['prompt'=>'Выберите цель'])->label('Кому адресовано');
echo $form->field($model, 'imageFile')->fileInput(['accept' => 'image/*'])->label("Прикреплённое изображение");
echo $form->field($model, 'attachmentFile')->fileInput(['accept' => 'application/zip'])->label("Zip-архив с вложением");
echo Html::submitButton('Отправить', ['class' => 'btn btn-success']);
ActiveForm::end();
