<?php
global $php;
$config = $php->config['redis']['master'];
if (empty($config["host"]))
{
    $config["host"] = '127.0.0.1';
}
if (empty($config["port"]))
{
    $config["port"] = 6379;
}
$redis = new Redis();
$redis->connect($config["host"], $config["port"]);

if (!empty($config['database']))
{
    $redis->select($config['database']);
}
