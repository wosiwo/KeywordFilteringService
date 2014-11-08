<?php
namespace Swoole;

class Http
{
    static function __callStatic($func, $params)
    {
        return call_user_func_array(array(\Swoole::$php->http, $func), $params);
    }
}
