<?php
namespace Swoole\Protocol;

use Swoole;
/**
 * Class Server
 * @package Swoole\Network
 */
class SOAServer extends Base implements Swoole\IFace\Protocol
{
    protected $_buffer; //buffer区
    protected $_fdfrom; //保存fd对应的from_id

    protected $errCode;
    protected $errMsg;

    protected $packet_maxlen = 2465792; //2M默认最大长度
    protected $buffer_maxlen = 10240;   //最大待处理区长度,超过后将丢弃最早入队数据
    protected $buffer_clear_num = 100; //超过最大长度后，清理100个数据

    const STX = 0xABAB;
    const ETX = 0xEFEF;

    const ERR_STX         = 9001;
    const ERR_OVER_MAXLEN = 9002;
    const ERR_BUFFER_FULL = 9003;

    const ERR_UNPACK      = 9204; //解包失败
    const ERR_PARAMS      = 9205; //参数错误
    const ERR_NOFUNC      = 9206; //函数不存在
    const ERR_CALL        = 9207; //执行错误

    protected $appNS = array(); //应用程序命名空间
    public $function_map = array(); //接口列表

    function onStart($serv)
    {
        $this->log("Server@{$this->server->host}:{$this->server->port} is running.");
    }
    function onShutdown($serv)
    {
        $this->log("Server is shutdown");
    }
    function onWorkerStart($serv, $worker_id)
    {
        $this->log("Worker[$worker_id] is start");
    }
    function onWorkerStop($serv, $worker_id)
    {
        $this->log("Worker[$worker_id] is stop");
    }
    function onTimer($serv, $interval)
    {
        $this->log("Timer[$interval] call");
    }
    /**
     * 返回false丢弃包并发送错误码，返回true将进行下一步处理，返回0表示继续等待包
     * @param $data
     * @return false or true or 0
     */
    function _packetReform($data)
    {
        $_etx = unpack('netx', substr($data, -2, 2));
        //收到结束符
        if($_etx!=false and $_etx['etx'] === self::ETX)
        {
            return true;
        }
        //超过最大长度将丢弃
        elseif(strlen($data) > $this->packet_maxlen)
        {
            $this->errCode = self::ERR_OVER_MAXLEN;
            $this->log("ERROR: packet too big.data=".$data);
            return false;
        }
        //继续等待数据
        else
        {
            return 0;
        }
    }
    function onReceive($serv, $fd, $from_id, $data)
    {
        if(!isset($this->_buffer[$fd]) or $this->_buffer[$fd]==='')
        {
            //超过buffer区的最大长度了
            if(count($this->_buffer) >= $this->buffer_maxlen)
            {
                $n = 0;
                foreach($this->_buffer as $k=>$v)
                {
                    $this->server->close($k, $this->_fdfrom[$k]);
                    $n++;
                    $this->log("clear buffer");
                    //清理完毕
                    if($n >= $this->buffer_clear_num) break;
                }
            }
            $_stx = unpack('nstx', substr($data, 0, 2));
            //错误的起始符
            if($_stx == false or $_stx['stx'] != self::STX)
            {
                $this->errCode = self::ERR_STX;
                $this->log("ERROR: No stx.data=".$data);
                return false;
            }
            $this->_buffer[$fd] = '';
        }
        $this->_buffer[$fd] .= $data;
        $ret = $this->_packetReform($this->_buffer[$fd]);
        //继续等待数据
        if($ret === 0)
        {
            return true;
        }
        //丢弃此包
        elseif($ret === false)
        {
            $this->log("ERROR: lose data=".$data);
            $this->server->close($fd, $from_id);
            //这里可以加log
        }
        //处理数据
        else
        {
            //这里需要去掉STX和ETX
            $retData = $this->task($fd, substr($this->_buffer[$fd], 2, strlen($this->_buffer[$fd])-4));
            //执行失败
            if($retData === false)
            {
                $this->server->close($fd);
            }
            else
            {
                $this->server->send($fd, pack('n', self::STX).serialize($retData).pack('n', self::ETX));
            }
            //清理缓存
            $this->_buffer[$fd] = '';
        }
    }
    function onConnect($serv, $fd, $from_id)
    {
        $this->_fdfrom[$fd] = $from_id;
    }
    function onClose($serv, $fd, $from_id)
    {
        unset($this->_buffer[$fd], $this->_fdfrom[$fd]);
    }
    function addNameSpace($name, $path)
    {
        if(!is_dir($path))
        {
            throw new \Exception("$path is not real path.");
        }
        Swoole\Loader::setRootNS($name, $path);
    }

    function task($client_id, $data)
    {
        $request = unserialize($data);
        if($request === false)
        {
            return array('errno'=>self::ERR_UNPACK);
        }
        if(empty($request['call']) or empty($request['params']))
        {
            return array('errno'=>self::ERR_PARAMS);
        }
        if(!is_callable($request['call']))
        {
            return array('errno'=>self::ERR_NOFUNC);
        }
        $ret = call_user_func($request['call'], $request['params']);
        if($ret === false)
        {
            return array('errno'=>self::ERR_CALL);
        }
        return array('errno'=>0, 'data' => $ret);
    }
}