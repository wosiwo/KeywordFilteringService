<?php
define('DEBUG', 'on');
define("WEBPATH", realpath(__DIR__ . '/../'));
require __DIR__ . '/../libs/lib_config.php';
//require __DIR__'/phar://swoole.phar';
Swoole\Config::$debug = false;

class EchoServer extends Swoole\Protocol\Base
{
    function onReceive($server, $client_id, $from_id, $data)
    {
        $this->server->send($client_id, "Swoole: " . $data);
    }
}

//设置PID文件的存储路径
Swoole\Network\Server::setPidFile(__DIR__ . '/echo_server.pid');
Swoole\Network\Server::addOption('c|config:', "要加载的配置文件");

/**
 * 显示Usage界面
 * php app_server.php start|stop|reload
 */
Swoole\Network\Server::start(function ($options)
{
    $AppSvr = new EchoServer();
    $listenHost = empty($options['host']) ? '0.0.0.0' : $options['host'];
    $listenPort = empty($options['port']) ? 9501 : $options['port'];
    $server = Swoole\Network\Server::autoCreate($listenHost, $listenPort);
    $server->setProtocol($AppSvr);
    $server->run(array('worker_num' => 1));
});
