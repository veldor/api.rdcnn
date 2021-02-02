<?php

/* @var $this yii\web\View */

use app\assets\IndexAsset;
use app\models\db\Role;
use app\models\User;
use app\models\UserModel;
use unclead\multipleinput\MultipleInput;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

IndexAsset::register($this);

if (Yii::$app->user->can("manage ticket")) {
    // получу список пользователей
    $usersList = User::find()->all();
    ?>

    <ul class="nav nav-tabs">
        <li id="bank_set_li" class="active"><a href="#global_actions" data-toggle="tab" class="active">Обшие
                действия</a></li>
        <li><a href="#management" data-toggle="tab">Управление пользователями</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="global_actions">
            <h1 class="text-center">Действия</h1>
        </div>
        <div class="tab-pane" id="management">
            <h1 class="text-center">Пользователи</h1>

            <h2 class="text-center">Добавление новых пользователей</h2>
            <?php
            $roleList = Role::getList();
            $model = new UserModel();
            // тут выведу форму для редактирования данных всех пользователей
            $form = ActiveForm::begin([
                'enableAjaxValidation' => true,
                'enableClientValidation' => false,
                'validateOnChange' => false,
                'validateOnSubmit' => true,
                'validateOnBlur' => false,
            ]);
            echo $form->field($model, 'items')->widget(
                MultipleInput::class, [
                'max' => 4,
                'columns' => [
                    [
                        'name' => 'name',
                        'title' => 'ФИО',
                    ],
                    [
                        'name' => 'login',
                        'title' => 'Логин',
                    ],
                    [
                        'name' => 'password',
                        'title' => 'Пароль',
                    ],
                    [
                        'name' => 'email',
                        'title' => 'Электронная почта',
                    ],
                    [
                        'name' => 'phone',
                        'title' => 'Номер телефона',
                    ],
                    [
                        'name' => 'role',
                        'type' => 'dropDownList',
                        'title' => 'Должность',
                        'items' => $roleList
                    ],
                    [
                        'name' => 'previlegies',
                        'type' => 'checkbox',
                        'title' => 'Права администратора',
                    ]
                ]
            ]);
            echo Html::submitButton('Update', ['class' => 'btn btn-success']);
            ActiveForm::end();
            ?>
        </div>
    </div>

    <?php
}
