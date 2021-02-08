<?php


namespace app\models;


use app\exceptions\WrongArgumentException;
use app\models\db\Email;
use app\models\db\Phone;
use http\Exception\InvalidArgumentException;
use Yii;
use yii\base\Model;

class EditableUser extends Model
{
    public function __construct(
        public int $id = 0,
        public string $login = '',
        public string $userName = '',
        public string $email = '',
        public string $phone = '',
        public string $role = '',
        public bool $adminRights = false,
        public string $newPass = '',
    )
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            // атрибут required указывает, что name, email, subject, body обязательны для заполнения
            [['id', 'userName', 'login', 'role', 'adminRights'], 'required'],
            // атрибут email указывает, что в переменной email должен быть корректный адрес электронной почты
            ['email', 'email'],
            ['phone', 'validatePhoneNumber', 'skipOnEmpty' => true]
        ];
    }

    public function validatePhoneNumber($attribute, $params)
    {
        // ошищу лишние символы
        $pattern = '/[^\d]/';
        $number = preg_replace($pattern, '', $this->$attribute);
        if(!empty($number) && strlen($number) === 11){
            $this->$attribute = $number;
        }
        else{
            $this->addError($attribute, 'Неверный номер телефона');
        }
    }

    /**
     * @return EditableUser[]
     */
    public static function getAllUsers(): array
    {
        $answer = [];
        $usersList = User::find()->all();
        if (!empty($usersList)) {
            foreach ($usersList as $user) {
                $editableUser = new EditableUser(
                    $user->id,
                    $user->name,
                    $user->username,
                    Email::getFirstEmail($user) ?? '',
                    Phone::getFirstPhone($user) ?? '',
                    $user->role,
                    !empty(Yii::$app->authManager->getRolesByUser($user->id)['manager']) ? true : false
                );
                $answer[] = $editableUser;
            }
        }
        return $answer;
    }

    public function saveChanges()
    {
        $existentUser = User::findIdentity($this->id);
        if ($existentUser !== null) {
            if (!empty($this->email)) {
                Email::addEmail($existentUser, $this->email);
            }
            if (!empty($this->phone)) {
                Phone::addPhone($existentUser, $this->phone);
            }
            if(!empty($this->newPass)){
                $existentUser->password_hash = Yii::$app->security->generatePasswordHash($this->newPass);
                // сменю токены доступа
                $existentUser->access_token = Yii::$app->security->generateRandomString(255);
                $existentUser->auth_key = Yii::$app->security->generateRandomString(255);
            }
            if(!empty($this->login) && $this->login !== $existentUser->username){
                $existentUser->username = $this->login;
            }
            if(!empty($this->userName) && $this->userName !== $existentUser->name){
                $existentUser->name = $this->userName;
            }
            if($this->role !== $existentUser->role){
                $existentUser->role = $this->role;
            }
            $existentUser->save();
        }
    }

    /**
     *
     */
    public function saveNewUser()
    {
        // проверю правильность заполнения данных
        if(empty($this->login) || !empty(User::findByUsername($this->login))){
            throw new WrongArgumentException('Неверный логин. Пуст или уже используется');
        }
        if(empty($this->newPass)){
            throw new WrongArgumentException('Не заполнен пароль');
        }
        if(empty($this->userName)){
            throw new WrongArgumentException('Не заполнен пароль');
        }
        if(empty($this->role)){
            throw new WrongArgumentException('Не заполнена должность');
        }
        // сохраню пользователя, добавлю ему роль
        $newUser = new User([
            'name' => $this->userName,
            'username' => $this->login,
            'password_hash' => Yii::$app->security->generatePasswordHash($this->newPass),
            'role' => $this->role,
            'access_token' => Yii::$app->security->generateRandomString(255),
            'auth_key' => Yii::$app->security->generateRandomString(255)
        ]);
        $newUser->save();
        // теперь добавлю права доступа
        $auth = Yii::$app->authManager;
        $roleName = $this->adminRights ? 'manager' : ($this->role > 3 ? 'user' : 'handler');
        $role = $auth->getRole($roleName);
        $auth->assign($role, $newUser->getId());
        if (!empty($this->email)) {
            Email::addEmail($newUser, $this->email);
        }
        if (!empty($this->phone)) {
            $this->validatePhoneNumber('phone', []);
            Phone::addPhone($newUser, $this->phone);
        }
    }
}