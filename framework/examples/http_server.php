<?php
define('DEBUG', 'on');
define("WEBPATH", realpath(__DIR__ . '/../'));
require dirname(__DIR__) . '/libs/lib_config.php';

Swoole\Config::$debug = false;

//设置PID文件的存储路径
Swoole\Network\Server::setPidFile(__DIR__ . '/http_server.pid');
/**
 * 显示Usage界面
 * php app_server.php start|stop|reload
 */
Swoole\Network\Server::start(function ()
{
    $AppSvr = new Swoole\Protocol\HttpServer();
    $AppSvr->loadSetting(__DIR__.'/swoole.ini'); //加载配置文件
    $AppSvr->setDocumentRoot(__DIR__.'/webroot');
    $AppSvr->setLogger(new Swoole\Log\EchoLog(true)); //Logger

    Swoole\Error::$echo_html = false;

    $server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);
    $server->setProtocol($AppSvr);
    //$server->daemonize(); //作为守护进程
    $server->run(array('worker_num' => 0, 'max_request' => 5000, 'log_file' => '/tmp/swoole.log'));
});
