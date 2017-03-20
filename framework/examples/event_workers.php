<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/../'));
require dirname(__DIR__) . '/libs/lib_config.php';

Swoole::$php->event->runWorker(2);