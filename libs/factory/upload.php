<?php
if (!empty(Swoole::$php->config['upload']['base_dir']))
{
    $basedir = Swoole::$php->config['upload']['base_dir'];
}
elseif (defined('UPLOAD_DIR'))
{
    $basedir = UPLOAD_DIR;
}
else
{
    throw new Exception("require upload base_dir");
}
$upload = new Swoole\Upload($basedir);
