<?php
global $php;
$config = $php->config['event'];
if (empty($config[$php->factory_key]))
{
    throw new Swoole\Exception\Factory("event->{$php->factory_key} is not fund.");
}
$config = $config[$php->factory_key];
if (empty($config) or !isset($config['async']))
{
    throw new Exception("require event[$php->factory_key] config.");
}
if ($config['async'] && empty($config['type']))
{
    throw new Exception("\"type\" config required in event aysnc mode");
}
return new Swoole\Component\Event($config);
