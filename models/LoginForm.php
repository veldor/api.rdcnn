<?php

namespace app\models;

use app\models\db\BlacklistItem;
use app\utils\GrammarHandler;
use JetBrains\PhpStorm\ArrayShape;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\db\StaleObjectException;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public const SCENARIO_ADMIN_LOGIN = 'admin_login';
    public const SCENARIO_USER_LOGIN = 'user_login';


    /**
     * @return string[][]
     */
    #[ArrayShape([self::SCENARIO_ADMIN_LOGIN => "string[]", self::SCENARIO_USER_LOGIN => "string[]"])] public function scenarios():array
    {
        return [
            self::SCENARIO_ADMIN_LOGIN => ['username', 'password'],
            self::SCENARIO_USER_LOGIN => ['username', 'password'],
        ];
    }

    public string $username = '';
    public string $password = '';
    public bool $rememberMe = false;

    private $_user = null;


    /**
     * @return array the validation rules.
     */
    public function rules():array
    {
        return [
            // username and password are both required
            [['username', 'password', 'rememberMe'], 'required', 'on' => self::SCENARIO_USER_LOGIN],
        ];
    }

    #[ArrayShape(['username' => "string", 'password' => "string", 'rememberMe' => "string"])] public function attributeLabels():array
    {
        return [
            'username' => 'Логин',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     */
    public function validatePassword(string $attribute): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if ($user !== null) {
                // проверю, если было больше 5 неудачных попыток ввода пароля- время между попытками должно составлять не меньше 10 минут
                if ($user->failed_try > 2 && $user->last_login_try > time() - 600) {
                    $this->addError($attribute, 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
                    return;
                }
                if ($user->failed_try > 5) {
                    $this->addError($attribute, 'Учётная запись заблокирована. Обратитесь к администратору для восстановления доступа');
                    return;
                }

                if (!$user->validatePassword(trim($this->$attribute))) {
                    $user->last_login_try = time();
                    $user->failed_try = ++$user->failed_try;
                    $user->save();
                } else {
                    return;
                }
            }
            $this->addError($attribute, 'Неверный номер обследования или пароль');
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User
     */
    public function getUser(): ?User
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername(trim($this->username));
        }
        return $this->_user;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function loginUser(): bool
    {
        // проверю, не занесён ли IP в чёрный список
        $blocked = $this->checkBlacklist();
        if ($blocked) {
            // если прошло больше суток с последнего ввода пароля- уберу IP из блеклиста
            if(time() - $blocked->last_try > 60 * 60 * 24){
                try {
                    $blocked->delete();
                } catch (StaleObjectException $e) {
                } catch (Throwable $e) {
                    // ошибка при удалении блокировки
                }
            }
            // если количество неудачных попыток больше 3 и не прошло 10 минут- отправим ожидать
            elseif($blocked->try_count > 3 && (time() - $blocked->last_try < 600)){
                $this->addError('username', 'Слишком много неверных попыток ввода пароля. Должно пройти не менее 10 минут с последней попытки');
                return false;
            }
            elseif ($blocked->missed_execution_number > 20){
                $this->addError('username', 'Слишком много попыток ввода номера обследования. Попробуйте снова через сутки');
                return false;
            }
        }
        // получу данные о пользователе
        $user = User::findByUsername(GrammarHandler::toLatin($this->username));
        if ($user !== null) {
            if ($user->failed_try > 20) {
                $this->addError('username', 'Было выполнено слишком много неверных попыток ввода пароля. В целях безопасности данные были удалены. Вы можете обратиться к нам для восстановления доступа');
                return false;
            }
            if (!$user->validatePassword(trim($this->password))) {
                $user->last_login_try = time();
                $user->failed_try = ++$user->failed_try;
                $user->save();
                $this->addError('username', 'Неверный номер обследования или пароль');
                if($blocked){
                    $blocked->updateCounters(['try_count' => 1]);
                    $blocked->last_try = time();
                    $blocked->save();
                }
                else{
                    $this->registerWrongTry();
                }
                return false;
            }
            // логиню пользователя
            $user->failed_try = 0;
            if (empty($user->access_token)) {
                $user->access_token = Yii::$app->getSecurity()->generateRandomString(255);
            }
            $user->save();
            return Yii::$app->user->login($user, $this->rememberMe ? 3600*24*30 : 0);
        }
        $this->addError('username', 'Неверный номер обследования или пароль');
        // добавлю пользователя в список подозрительных
        if($blocked){
            $blocked->updateCounters(['missed_execution_number' => 1]);
            $blocked->save();
        }
        else{
            $this->registerWrongTry();
        }
        return false;
    }


    private function checkBlacklist(): ?BlacklistItem
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        return BlacklistItem::findOne(['ip' => $ip]);
    }

    private function registerWrongTry(): void
    {
        // проверю, не занесён ли уже IP в базу данных
        $ip = $_SERVER['REMOTE_ADDR'];
        $is_blocked = BlacklistItem::findOne(['ip' => $ip]);
        if ($is_blocked === null) {
            // внесу IP в чёрный список
            $blacklist = new BlacklistItem();
            $blacklist->ip = $ip;
            $blacklist->try_count = 1;
            $blacklist->last_try = time();
            $blacklist->save();
        }
    }
}
