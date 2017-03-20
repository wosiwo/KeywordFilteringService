<?php
if (empty(Swoole::$php->config['upload']))
{
    throw new Exception("require upload config");
}
else
{
    $config = Swoole::$php->config['upload'];
}
$upload = new Swoole\Upload($config);
return $upload;
