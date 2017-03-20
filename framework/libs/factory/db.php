<?php
global $php;
$configs = $php->config['db'];
if (empty($configs[$php->factory_key]))
{
    throw new Swoole\Exception\Factory("db->{$php->factory_key} is not found.");
}
$config = $configs[$php->factory_key];
if (!empty($config['use_proxy']))
{
    $db = new Swoole\Database\Proxy($config);
}
else
{
    $db = new Swoole\Database($config);
    $db->connect();
}
return $db;
