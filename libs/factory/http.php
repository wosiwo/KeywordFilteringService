<?php
if(defined('SWOOLE_SERVER'))
{
    $http = new Swoole\Http\PWS();
}
else
{
    $http = new Swoole\Http\LAMP();
}