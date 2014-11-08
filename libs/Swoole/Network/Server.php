<?php
namespace Swoole\Network;
use Swoole;

/**
 * Class Server
 * @package Swoole\Network
 */
class Server extends Swoole\Server implements Swoole\Server\Driver
{
    static $sw_mode = SWOOLE_PROCESS;
    /**
     * @var \swoole_server
     */
    protected $sw;
    protected $swooleSetting;
    protected $pid_file;

    /**
     * 自动推断扩展支持
     * 默认使用swoole扩展,其次是libevent,最后是select(支持windows)
     * @param      $host
     * @param      $port
     * @param bool $ssl
     * @return EventTCP|SelectTCP|Server
     */
    static function autoCreate($host, $port, $ssl = false)
    {
        if (class_exists('\\swoole_server', false))
        {
            return new self($host, $port, $ssl);
        }
        elseif (function_exists('event_base_new'))
        {
            return new EventTCP($host, $port, $ssl);
        }
        else
        {
            return new SelectTCP($host, $port, $ssl);
        }
    }

    function __construct($host, $port, $ssl = false)
    {
        $flag = $ssl ? (SWOOLE_SOCK_TCP | SWOOLE_SSL) : SWOOLE_SOCK_TCP;
        $this->sw = new \swoole_server($host, $port, self::$sw_mode, $flag);
        $this->host = $host;
        $this->port = $port;
        Swoole\Error::$stop = false;
        Swoole\JS::$return = true;
        $this->swooleSetting = array(
            //'reactor_num' => 4,      //reactor thread num
            //'worker_num' => 4,       //worker process num
            'backlog' => 128,        //listen backlog
            //'open_cpu_affinity' => 1,
            //'open_tcp_nodelay' => 1,
            //'log_file' => '/tmp/swoole.log',
        );
    }
    function daemonize()
    {
        $this->swooleSetting['daemonize'] = 1;
    }

    function onMasterStart($serv)
    {
        global $argv;
        Swoole\Console::setProcessName('php ' . $argv[0] . ': master -host=' . $this->host . ' -port=' . $this->port);
        if (!empty($this->swooleSetting['pid_file']))
        {
            file_put_contents($this->pid_file,$serv->master_pid);
        }
    }
    function onManagerStop()
    {
        if (!empty($this->swooleSetting['pid_file']))
        {
            unlink($this->pid_file);
        }
    }

    function run($setting = array())
    {
        $this->swooleSetting = array_merge($this->swooleSetting, $setting);
        if (!empty($this->swooleSetting['pid_file']))
        {
            $this->pid_file = $this->swooleSetting['pid_file'];
        }
        $this->sw->set($this->swooleSetting);
        $version = explode('.', SWOOLE_VERSION);
        //1.7.0
        if ($version[1] >= 7)
        {
            $this->sw->on('ManagerStart', function($serv) {
                global $argv;
                Swoole\Console::setProcessName('php '.$argv[0].': manager');
            });
        }
        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('ManagerStop', array($this, 'onManagerStop'));
        $this->sw->on('WorkerStart', array($this->protocol, 'onStart'));
        $this->sw->on('Connect', array($this->protocol, 'onConnect'));
        $this->sw->on('Receive', array($this->protocol, 'onReceive'));
        $this->sw->on('Close', array($this->protocol, 'onClose'));
        $this->sw->on('WorkerStop', array($this->protocol, 'onShutdown'));
        if (is_callable(array($this->protocol, 'onTimer')))
        {
            $this->sw->on('Timer', array($this->protocol, 'onTimer'));
        }
        if (is_callable(array($this->protocol, 'onTask')))
        {
            $this->sw->on('Task', array($this->protocol, 'onTask'));
            $this->sw->on('Finish', array($this->protocol, 'onFinish'));
        }
        $this->sw->start();
    }

    function shutdown()
    {
        return $this->sw->shutdown();
    }

    function close($client_id)
    {
        return $this->sw->close($client_id);
    }

    function addListener($host, $port, $type)
    {
        return $this->sw->addlistener($host, $port, $type);
    }

    function send($client_id, $data)
    {
        return $this->sw->send($client_id, $data);
    }
}
