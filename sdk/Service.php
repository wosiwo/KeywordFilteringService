<?php
if (!class_exists('Swoole', false))
{
    require_once '/data/www/public/framework/libs/Swoole/Loader.php';
    Swoole\Loader::addNameSpace('Swoole', '/data/www/public/framework/libs/Swoole');
    spl_autoload_register('\\Swoole\\Loader::autoload', true, true);
}

class Service extends Swoole\Client\SOA
{
    protected $service_name;
    protected $namespace;
    protected $config;

    /**
     * 模调上报的ID
     * @var int
     */
    protected $moduleId = 1000321;

    const ERR_NO_CONF = 7001;

    /**
     * 构造函数
     * @param $service
     * @throws ServiceException
     */
    function __construct($service = 'Keyword')
    {
        if (empty($service))
        {
            $service = 'sandbox';
        }

        $this->service_name = strtolower($service);

        $env = get_cfg_var('env.name');
        $env = $env ?: 'product';

//        $conf = CloudConfig::get('service:'.$service, $env);

        $conf = array(
            "namespace" => "Keyword",
            "id" => "Keyword",
            "servers"=> [
                [
                    "host"=> "127.0.0.1",
                    "port"=> 9100,
                    "weight"=> 100,
                    "status"=> "online"
                ],
                [
                    "host"=> "127.0.0.1",
                    "port"=> 9100,
                    "weight"=> 100,
                    "status"=> "online"
                ],
            ]
        );
        if (empty($conf))
        {
            throw new ServiceException("get config [{$this->service_name}] failed.", self::ERR_NO_CONF);
        }

        //新版本支持权重和offline
        if (defined('Swoole\Client\SOA::VERSION') and constant('Swoole\Client\SOA::VERSION') > 1000)
        {
            $this->addServers($conf['servers']);
        }
        else
        {
            $iplist = array();
            foreach($conf['servers'] as $k => $svr)
            {
                if ($svr['status'] == 'online')
                {
                    $iplist[] = $svr['host'].':'.$svr['port'];
                }
            }
            $servers = $iplist;
            $this->addServers($servers);
        }

        //模调系统的ID
        if (!empty($conf['module_id']))
        {
            $this->moduleId = intval($conf['module_id']);
        }

        $this->config = $conf;
        $this->namespace = $conf['namespace'];

        parent::__construct($service);
    }

    /**
     * @param $obj Swoole\Client\SOA_Result
     */
    protected function beforeRequest($obj)
    {

    }

    /**
     * @param $obj Swoole\Client\SOA_Result
     */
    protected function afterRequest($obj)
    {

    }

    function call()
    {
        $args = func_get_args();
        return $this->task($this->namespace . '\\' . $args[0], array_slice($args, 1));
    }
}

class ServiceException extends Exception
{

}