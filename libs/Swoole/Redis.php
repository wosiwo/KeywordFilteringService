<?php
namespace Swoole;

class Redis
{
    public static $prefix = "autoinc_key:";
    static function getIncreaseId($appKey, $init_id = 1000)
    {
        if (empty($appKey))
        {
            return false;
        }
        $main_key = self::$prefix . $appKey;
        //已存在 就加1
        if (\Swoole::$php->redis->exists($main_key))
        {
            $inc = \Swoole::$php->redis->incr($main_key);
            if (empty($inc))
            {
                \Swoole::$php->log->put("redis::incr() failed. Error: ".\Swoole::$php->redis->getLastError());
                return false;
            }
            return $inc;
        }
        //上面的if条件返回false,可能是有错误，或者key不存在，这里要判断下
        else if(\Swoole::$php->redis->getLastError())
        {
            return false;
        }
        //这才是说明key不存在，需要初始化
        else
        {
            $init = \Swoole::$php->redis->set($main_key, $init_id);
            if ($init == false)
            {
                \Swoole::$php->log->put("redis::set() failed. Error: ".\Swoole::$php->redis->getLastError());
                return false;
            }
            else
            {
                return $init_id;
            }
        }
    }
}