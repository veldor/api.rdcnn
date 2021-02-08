<?php


namespace app\utils;


use DateTime;

class TimeHandler
{
    public static array $months = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря',];
    public const DAY_PERIOD = 60 * 60 * 24;

    public function getTimeInfo($timestamp): string
    {
        $now = time();
        // получу фактическую дату и время
        $taskCreateTime = $this->timestampToDate($timestamp);
        $difference = $timestamp - $now;
        $days = abs((int)($difference / self::DAY_PERIOD));
        if ($difference > 0) {
            if ($days === 0) {
                return "$taskCreateTime<br/> (сегодня)";
            }
            return "$taskCreateTime<br/> (через " . $this->getDaysCountPostfix($days) . ")";
        }
        if ($days === 0) {
            return "$taskCreateTime<br/> (меньше дня назад)";
        }
        return "$taskCreateTime<br/> (" . $this->getDaysCountPostfix($days) . " назад)";
    }

    public function timestampToDate(int $timestamp): string
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $answer = '';
        $day = $date->format('d');
        $answer .= $day;
        $month = mb_strtolower(self::$months[$date->format('m') - 1]);
        $answer .= ' ' . $month . ' ';
        $answer .= $date->format('Y') . ' года.';
        $answer .= $date->format(' H:i:s');
        return $answer;
    }

    public function getDaysCountPostfix($difference)
    {
        if ($difference === 1) {
            return 'день';
        }
        $param = (string)$difference;
        $last = substr($param, strlen($param) - 1);
        $prelast = $param[strlen($param) - 2];
        if ($prelast === '1') {
            return "$difference дней";
        }
        switch ($last) {
            case '1' :
                return "$difference день";
            case '2' :
            case '3' :
            case '4' :
                return "$difference дня";
            case '5' :
            case '6' :
            case '7' :
            case '8' :
            case '9' :
            case '0' :
                return "$difference дней";
        }
        return false;
    }
}