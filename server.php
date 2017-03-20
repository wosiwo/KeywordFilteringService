<?php
require_once __DIR__ . '/config.php';



function swoole_exit($status = 0) {
	trigger_error(json_encode(debug_backtrace()));
	exit($status);
}

Swoole\Network\Server::setPidFile(__DIR__ . '/logs/server.pid');

//stop前将Service节点下线
Swoole\Network\Server::beforeStop(function ()
{

});

//载入字典
//启动时加载字典数据
$file = array('bin'=>realpath(__DIR__.'/dict/dict_all.dat'),'source'=>realpath(__DIR__.'/dict/dict_all.dat'));
Swoole::$php->trie = new App\Trie($file);
Swoole::$php->trie->nodes = Swoole::$php->trie->getBinaryDict($file['bin']);


Swoole\Network\Server::start(function ()
{

    //$logger = new Swoole\Log\FileLog(['file' => __DIR__ . '/logs/server.log']);
    $logger = new Swoole\Log\EchoLog(true);

    $AppSvr = new Swoole\Protocol\SOAServer;
    $AppSvr->setLogger($logger);
    $AppSvr->addNameSpace('KeyWord', __DIR__ . '/KeywordFilteringService');

    $setting = array(
        //TODO： 实际使用中必须调大进程数
        'worker_num' => 4,
        'max_request' => 1000,
        'dispatch_mode' => 3,
        'daemonize' => true,
        'log_file' => __DIR__ . '/logs/swoole.log',
        'open_length_check' => 1,
        'package_max_length' => $AppSvr->packet_maxlen,
        'package_length_type' => 'N',
        'package_body_offset' => \Swoole\Protocol\SOAServer::HEADER_SIZE,
        'package_length_offset' => 0,
        'watch_path' => __DIR__ . '/KeywordFilteringService',
    );

    if (ENV_NAME == 'product')
    {
        $setting['worker_num'] = 64;
        //重定向PHP错误日志
        ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    }
    else
    {
        //重定向PHP错误日志到logs目录
        ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    }

    //设置为512M
    ini_set('memory_limit', '512M');

    $listenHost = '0.0.0.0';
    if (ENV_NAME == 'product')
    {
        $iplist = swoole_get_local_ip();
        //监听局域网IP
        foreach ($iplist as $k => $v)
        {
            if (substr($v, 0, 7) == '192.168')
            {
                $listenHost = $v;
            }
        }
    } elseif (ENV_NAME == 'test')
    {
        $listenHost = '0.0.0.0';
    }

	$env_port = getenv('PORT');
    $server = Swoole\Network\Server::autoCreate($listenHost, $env_port ? intval($env_port) : 9100);
    $server->setProtocol($AppSvr);
    $server->setProcessName("KeyWordServer");
    $server->run($setting);
});
