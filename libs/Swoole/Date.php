<?php
namespace Swoole;

class Date
{
    static $week_two = '周';
    static $week_three = '星期';

    static function num2week($num,$two=true)
    {
        if($num=='6') $num = '日';
        else $num = Tool::num2han($num+1);

        if($two) return self::$week_two.$num;
        else return self::$week_three.$num;
    }

    static function getDate($param,$day=null,$date_format='Y-m-d')
    {
        if(!empty($day)) $tm = strtotime($day);
        else $tm = time();
        return date($date_format,strtotime($param,$tm));
    }
}
