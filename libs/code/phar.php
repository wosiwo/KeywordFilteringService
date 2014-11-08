<?php
$phar = new Phar('swoole.phar');
$phar->buildFromDirectory(__DIR__.'/../', '/\.php$/');
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();
$phar->setStub($phar->createDefaultStub('lib_config.php'));