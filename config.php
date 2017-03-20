<?php
define('SERVICE', true);
define('DEBUG', 'on');
define('ROOTPATH' ,__DIR__);
define('WEBPATH' ,__DIR__.'/KeywordFilteringService');
//define('APPSPATH',__DIR__.'/KeywordFilteringService/apps' );
define('SWOOLE_SERVER', true);

$env = get_cfg_var('env.name');

if (empty($env))
{
    $env = 'product';
}
define('ENV_NAME', $env);

if (is_dir('/data/www/public/framework1'))
{
    require_once '/data/www/public/framework/libs/lib_config.php';
    require_once '/data/www/public/sdk/Service.php';
}
else
{
    require_once __DIR__ . '/framework/libs/lib_config.php';
    require_once __DIR__ . '/sdk/Service.php';
}



Swoole\Loader::addNameSpace('Keyword', __DIR__ . '/KeywordFilteringService');
Swoole\Loader::addNameSpace('KeywordModel', __DIR__ . '/KeywordFilteringService/apps/models');

Swoole::$php->config->setPath(__DIR__ . '/configs/' . ENV_NAME);//共有配置
