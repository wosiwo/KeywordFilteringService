<?php
global $php;
if (empty($php->config['db'][$php->factory_key]))
{
    throw new Swoole\Exception\Factory("codb->{$php->factory_key} is not found.");
}
$codb = new Swoole\Client\CoMySQL($php->factory_key);
return $codb;
