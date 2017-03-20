<?php
global $php;
$user = new Swoole\Auth($php->config['user']);
return $user;
