<?php
if (!empty(Swoole::$php->config['session']['use_swoole_sesion']) or defined('SWOOLE_SERVER'))
{
    if (empty(Swoole::$php->config['cache']['session']))
    {
        $cache = Swoole::$php->cache;
    }
    else
    {
        $cache = Swoole\Factory::getCache('session');
    }
    $session = new Swoole\Session($cache);
    $session->use_php_session = false;
}
else
{
    $session = new Swoole\Session;
}
return $session;