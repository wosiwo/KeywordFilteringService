<?php
global $php;
$config = $php->config['url'][$php->factory_key];
return new Swoole\URL($config);

