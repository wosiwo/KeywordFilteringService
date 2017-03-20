<?php
if (defined('SWOOLE_SERVER'))
{
    $http = new Swoole\Http\PWS();
}
elseif (defined('SWOOLE_HTTP_SERVER'))
{
    $http = Swoole::$php->ext_http_server;
}
else
{
    $http = new Swoole\Http\LAMP();
}
return $http;