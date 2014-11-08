<?php
if (empty(\Swoole::$php->config['cache']['master']))
{
    Swoole::$php->config['cache']['master'] = array('type' => 'FileCache', 'cache_dir' => WEBPATH . '/cache/filecache');
}
$cache = Swoole\Factory::getCache('master');