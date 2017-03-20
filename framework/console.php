#!/usr/bin/env php
<?php
define('WEBPATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

Swoole\Loader::vendorInit();
Swoole::getInstance()->runConsole();
