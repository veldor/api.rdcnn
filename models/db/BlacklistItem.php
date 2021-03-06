<?php


namespace app\models\db;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $ip [char(15)]  IP
 * @property int $last_try [int(15)]  Время последней попытки входа
 * @property int $try_count [int(11)]  Количество неудачных попыток
 * @property int $missed_execution_number [int(11)]  Сколько раз пользователь не угадал с логином
 */

class BlacklistItem extends ActiveRecord
{
    public static function tableName()
    {
        return 'blacklist';
    }

    public static function clear()
    {
        $results = self::find()->all();
        if(!empty($results)){
            foreach ($results as $result) {
                $result->delete();
            }
        }
    }
}