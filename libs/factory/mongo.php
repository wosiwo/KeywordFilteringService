<?php
global $php;
$config = $php->config['mongo']['master'];
if (empty($config['host']))
{
    $config['host'] = '127.0.0.1';
}
if (empty($config['port']))
{
    $config['port'] = 27017;
}
if (!isset($config['option']))
{
    $config['option'] = array();
}
$url = "mongodb://{$config['host']}:{$config['port']}";
$mongo = new MongoClient($url, $config['option']);
