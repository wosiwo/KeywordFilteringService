<?php
global $php;

$config = $php->config['redis'][$php->factory_key];
if (empty($config) or empty($config['host']))
{
    throw new Exception("require redis[$php->factory_key] config.");
}

if (empty($config['port']))
{
    $config['port'] = 6379;
}

if (empty($config["pconnect"]))
{
    $config["pconnect"] = false;
}

if (empty($config['timeout']))
{
    $config['timeout'] = 0.5;
}

$redis = new Swoole\Component\Redis($config);
return $redis;