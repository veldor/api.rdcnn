<?php

/* @var $this yii\web\View */

use app\assets\IndexAsset;
use app\models\db\Email;
use app\models\db\Phone;
use app\models\db\Role;
use app\models\db\Task;
use app\models\EditableUser;
use app\models\TaskItem;
use app\models\User;
use app\models\UserModel;
use nirvana\showloading\ShowLoadingAsset;
use unclead\multipleinput\MultipleInput;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\ListView;

IndexAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Тикеты РДЦ';

//
try {
    $isExecutor = Yii::$app->user->can("handle ticket") && Yii::$app->user->getIdentity()->role < 4;
} catch (Throwable) {
}


$order = '';
$value = '';
$cookies = Yii::$app->request->cookies;
$sortCookie = $cookies->get('outgoingOrderBy');
if ($sortCookie !== null) {
    $value = $sortCookie->value;
    $order = match ($sortCookie->value) {
        'state' => 'task_status',
        'name' => 'task_header',
        'time' => 'task_creation_time',
        'destination' => 'target',
        'finish' => 'task_finish_time',
        default => 'id'
    };
}
$filterValue = ["created", "accepted"];
$filterCookie = $cookies->get('outgoingFilter');
if ($filterCookie !== null && $filterCookie->value !== null) {
    /** @noinspection UnserializeExploitsInspection */
    $filterValue = unserialize($filterCookie->value);
}

/* @var $outgoingTasks TaskItem[] */
?>

<ul class="nav nav-tabs">
    <?php
    if (Yii::$app->user->can("create ticket")) {
        echo ' <li id="bank_set_li" class="active"><!--suppress HtmlUnknownAnchorTarget -->
<a href="#outgoingTickets" data-toggle="tab" class="active">Исходящие
                заявки</a></li>';
    }
    try {
        if (Yii::$app->user->can("handle ticket") && Yii::$app->user->getIdentity()->role < 4) {
            echo '<li id="bank_set_li"><a href="#incomingTickets" data-toggle="tab">Входящие заявки ' . Yii::$app->user->getIdentity()->getUnhandledTasks() . '</a></li>
    ';
        }
    } catch (Throwable) {
    }
    if (Yii::$app->user->can("manage ticket")) {
        echo '<li><!--suppress HtmlUnknownAnchorTarget -->
<a href="#management" data-toggle="tab">Управление пользователями</a></li><li><a href="#taskManagement" data-toggle="tab">Управление задачами</a></li>';
    }
    ?>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="outgoingTickets">
        <div class="row">
            <div class="col-sm-12 text-center">
                <button id="createTaskBtn" class="btn btn-success">Создать новую заявку</button>
                <h2 class="text-center">Список заявок</h2>
                <?= Html::beginForm(['/user/select-order'], 'post', ['id' => 'listStyleForm']) ?>
                <label>
                    <b>Сортировка результатов</b>
                    <?= Html::dropDownList(
                        'orderBy',
                        $value,
                        [
                            'state' => 'По статусу',
                            'name' => 'По названию',
                            'time' => 'По времени добавления',
                            'destination' => 'По назначению',
                            'finish' => 'По времени завершения',
                        ],
                        [
                            'class' => 'form-control',
                            'onchange' => "this.form.submit()"
                        ]
                    ) ?>
                </label>
                <div class="btn-group filter-view">
                    <button type="button" id="filterResultsBtn" class="btn btn-default dropdown-toggle"
                            data-toggle="dropdown">Фильтрация <span
                                class="caret"></span></button>

                    <ul class="dropdown-menu" role="menu">
                        <li><?= Html::checkboxList(
                                'filtered',
                                $filterValue,
                                [
                                    'created' => 'Ждут подтверждения',
                                    'accepted' => 'В работе',
                                    'finished' => 'Завершённые',
                                    'cancelled_by_initiator' => 'Отменённые пользователем',
                                    'cancelled_by_executor' => 'Отменённые исполнителем',
                                ],
                                ['class' => 'filter-item']
                            )
                            ?></li>
                    </ul>
                </div>
                <?= Html::endForm() ?>
                <table class="table table-striped table-condenced table-hover">
                    <tbody>
                    <?php
                    $query = Task::find()->where(['initiator' => Yii::$app->user->getId()]);
                    if (!empty($filterValue)) {
                        $query->andWhere(['task_status' => $filterValue]);
//                        foreach ($filterValue as $item) {
//                            $query->andFilterHaving(['task_status' => $item]);
//                        }
                    }
                    $dataProvider = new ActiveDataProvider([
                        'query' => $query->orderBy($order),
                        'pagination' => [
                            'pageSize' => 20,
                        ],
                    ]);
                    try {
                        echo ListView::widget([
                            'dataProvider' => $dataProvider,
                            'itemView' => 'outgoing_task_item',
                        ]);
                    } catch (Exception) {
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="incomingTickets">
        <div class="row">
            <div class="col-sm-12 text-center">
                <?php
                $incomingOrder = '';
                $incomingValue = '';
                $incomingSortCookie = $cookies->get('incomingOrderBy');
                if ($incomingSortCookie !== null) {
                    $incomingValue = $incomingSortCookie->value;
                    $incomingOrder = match ($incomingSortCookie->value) {
                        'state' => 'task_status',
                        'name' => 'task_header',
                        'time' => 'task_creation_time',
                        'destination' => 'target',
                        'finish' => 'task_finish_time',
                        default => 'id'
                    };
                }
                $incomingFilterValue = ["created", "accepted"];
                $incomingFilterCookie = $cookies->get('incomingFilter');
                if ($incomingFilterCookie !== null && $incomingFilterCookie->value !== null) {
                    /** @noinspection UnserializeExploitsInspection */
                    $incomingFilterValue = unserialize($incomingFilterCookie->value);
                }
                ?>
                <h2 class="text-center">Входящие заявки</h2>
                <?= Html::beginForm(['/executor/select-order'], 'post', ['id' => 'incomingListStyleForm']) ?>
                <label>
                    <b>Сортировка результатов</b>
                    <?= Html::dropDownList(
                        'orderBy',
                        $incomingValue,
                        [
                            'state' => 'По статусу',
                            'name' => 'По названию',
                            'time' => 'По времени добавления',
                            'destination' => 'По назначению',
                            'finish' => 'По времени завершения',
                        ],
                        [
                            'class' => 'form-control',
                            'onchange' => "this.form.submit()"
                        ]
                    ) ?>
                </label>
                <div class="btn-group incoming-filter-view">
                    <button type="button" id="filterResultsBtn" class="btn btn-default dropdown-toggle"
                            data-toggle="dropdown">Фильтрация <span
                                class="caret"></span></button>

                    <ul class="dropdown-menu" role="menu">
                        <li><?= Html::checkboxList(
                                'filtered',
                                $incomingFilterValue,
                                [
                                    'created' => 'Ждут подтверждения',
                                    'accepted' => 'В работе',
                                    'finished' => 'Завершённые',
                                    'cancelled_by_initiator' => 'Отменённые пользователем',
                                    'cancelled_by_executor' => 'Отменённые исполнителем',
                                ],
                                ['class' => 'filter-item']
                            )
                            ?></li>
                    </ul>
                </div>
                <?= Html::endForm() ?>
                <table class="table table-striped table-condensed table-hover">
                    <tbody>
                    <?php
                    try {
                        $query = Task::find()->where(['executor' => Yii::$app->user->getId()])->orWhere(['target' => Yii::$app->user->getIdentity()->role, 'task_status' => 'created']);
                    } catch (Throwable) {
                    }
                    if (!empty($incomingFilterValue)) {
                        $query->andWhere(['task_status' => $incomingFilterValue]);
                    }
                    $dataProvider = new ActiveDataProvider([
                        'query' => $query->orderBy($incomingOrder),
                        'pagination' => [
                            'pageSize' => 20,
                        ],
                    ]);
                    try {
                        echo ListView::widget([
                            'dataProvider' => $dataProvider,
                            'itemView' => 'incoming_task_item',
                        ]);
                    } catch (Exception) {
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="management">
        <h1 class="text-center">Пользователи</h1>
        <?php

        // получу список пользователей
        $usersList = User::find()->all();
        $roles = Role::getList();
        echo '<h2 class="text-center">Управление существующими пользователями</h2>';
        $form = ActiveForm::begin([
            'enableAjaxValidation' => false,
            'enableClientValidation' => false,
            'validateOnChange' => false,
            'validateOnSubmit' => false,
            'validateOnBlur' => false,
            'action' => '/manage/existent-users'
        ]);
        echo '<table class="multiple-input-list table table-condensed table-renderer"><thead><tr><th>Логин</th><th>ФИО</th><th>Пароль</th><th>Электронная почта</th><th>Номер телефона</th><th>Должность</th><th>Права администратора</th><th></th></tr></thead>';
        /** @var User $user */
        foreach ($usersList as $index => $user) {
            $editableUser = new EditableUser(
                $user->id,
                $user->username,
                $user->name,
                Email::getFirstEmail($user) ?? '',
                Phone::getFirstPhone($user) ?? '',
                $user->role,
                !empty(Yii::$app->authManager->getRolesByUser($user->id)['manager'])
            );
            echo $form->field($editableUser, "[$index]id")->label(false)->hiddenInput();
            echo '<tr>';
            echo '<td>' . $form->field($editableUser, "[$index]login")->label(false) . '</td>';
            echo '<td>' . $form->field($editableUser, "[$index]userName")->label(false) . '</td>';
            echo '<td>' . $form->field($editableUser, "[$index]newPass")->textInput(['type' => 'password', 'autocomplete' => 'off', 'autofill' => 'off'])->label(false) . '</td>';
            echo '<td>' . $form->field($editableUser, "[$index]email")->label(false) . '</td>';
            echo '<td>' . $form->field($editableUser, "[$index]phone")->label(false) . '</td>';
            echo '<td>' . $form->field($editableUser, "[$index]role")->dropDownList($roles)->label(false) . '</td>';
            echo '<td>' . $form->field($editableUser, "[$index]adminRights")->checkbox([], false)->label(false) . '</td>';
            echo '<td><button class="btn btn-default activator" data-action="/user/delete/' . $user->getId() . '"><span class="glyphicon-trash glyphicon text-danger"></span></button></td>';
            echo '</tr>';
        }

        echo '</table>';
        echo Html::submitButton('Update', ['class' => 'btn btn-success']);
        ActiveForm::end();
        ?>
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
            'action' => '/manage/add-users'
        ]);
        try {
            echo $form->field($model, 'items')->widget(
                MultipleInput::class, [
                'max' => 4,
                'columns' => [
                    [
                        'name' => 'login',
                        'title' => 'Логин',
                    ],
                    [
                        'name' => 'userName',
                        'title' => 'ФИО',
                    ],
                    [
                        'name' => 'newPass',
                        'title' => 'Пароль',
                        'type' => 'textInput',
                        'options' => ['type' => 'password', 'autocomplete' => 'off', 'autofill' => 'off']

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
                        'name' => 'adminRights',
                        'type' => 'checkbox',
                        'title' => 'Права администратора',
                    ]
                ]
            ]);
        } catch (Exception) {
        }
        echo Html::submitButton('Update', ['class' => 'btn btn-success']);
        ActiveForm::end();
        ?>
    </div>
    <div class="tab-pane" id="taskManagement">
        <table class="table table-striped table-condensed table-hover">
            <tbody>
            <?php
            try {
                $query = Task::find();
            } catch (Throwable) {
            }
            $dataProvider = new ActiveDataProvider([
                'query' => $query->orderBy($incomingOrder),
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]);
            try {
                echo ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => 'manage_task_item',
                ]);
            } catch (Exception) {
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
