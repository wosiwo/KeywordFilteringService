<?php
namespace Swoole\Log;
/**
 * 使用PHP的error_log记录日志
 * @author Tianfeng.Han
 *
 */
class PHPLog extends \Swoole\Log implements \Swoole\IFace\Log
{
    protected $logput;
    protected $type;
    protected $put_type = array('file'=>3, 'sys'=>0, 'email'=>1);

    function __construct($params)
    {
        if(isset($params['logput'])) $this->logput = $params['logput'];
        if(isset($params['type'])) $this->type = $this->put_type[$params['type']];
    }

    function put($msg, $level = self::INFO)
    {
        $msg = $this->format($msg, $level);
        if ($msg) error_log($msg, $this->type, $this->logput);
    }
}