<?php
global $php;
$user = new Swoole\Auth($php->db, LOGIN_TABLE);
