<?php
$conf = Swoole::$php->config['log'];

if (empty($conf['type']))
{
    $conf['type'] = 'EchoLog';
}
$class = "Swoole\\Log\\{$conf['type']}";
$log = new $class($conf);