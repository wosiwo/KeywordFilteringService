<?php
namespace Swoole\Client;
use Swoole\Protocol;

class SOA
{
    protected $servers = array();

    protected $wait_list = array();
    protected $timeout = 0.5;
    protected $packet_maxlen = 2465792;

    const OK = 0;
    const TYPE_ASYNC = 1;
    const TYPE_SYNC  = 2;
    public $re_connect = true; //重新connect

    /**
     * 发送请求
     * @param $type
     * @param $send
     * @param $retObj
     */
    protected function request($type, $send, $retObj)
    {
        $socket = new \Swoole\Client\TCP;
        $retObj->socket = $socket;
        $retObj->type = $type;
        $retObj->send = $send;

        $svr = $this->getServer();
        //异步connect
        $ret = $socket->connect($svr['host'], $svr['port'], $this->timeout);
        //使用SOCKET的编号作为ID
        $retObj->id = (int)$socket->get_socket();
        if($ret === false)
        {
            $retObj->code = SOAClient_Result::ERR_CONNECT;
            unset($retObj->socket);
            return false;
        }
        //发送失败了
        if($retObj->socket->send(self::packData($retObj->send)) === false)
        {
            $retObj->code = SOAClient_Result::ERR_SEND;
            unset($retObj->socket);
            return false;
        }
        //加入wait_list
        if($type != self::TYPE_ASYNC)
        {
            $this->wait_list[$retObj->id] = $retObj;
        }
        return true;
    }
    /**
     * 完成请求
     * @param $retData
     * @param $retObj
     */
    protected function finish($retData, $retObj)
    {
        $retObj->data = $retData;
        if(!empty($retData) and isset($retData['errno']))
        {
            if($retData['errno'] === self::OK)
            {
                $retObj->code = self::OK;
            }
            else
            {
                $retObj->code = SOAClient_Result::ERR_SERVER;
            }
        }
        else
        {
            $retObj->code = SOAClient_Result::ERR_UNPACK;
        }
        if($retObj->type != self::TYPE_ASYNC)
        {
            unset($this->wait_list[$retObj->id]);
        }
    }

    function addServers(array $servers)
    {
        $this->servers = array_merge($this->servers, $servers);
    }

    function getServer()
    {
        if(empty($this->servers))
        {
            throw new \Exception("servers config empty.");
        }
        $_svr = $this->servers[array_rand($this->servers)];
        $svr = array('host'=>'', 'port'=>0);
        list($svr['host'], $svr['port']) = explode(':', $_svr, 2);
        return $svr;
    }

    /**
     * 打包数据
     * @param $data
     * @return string
     */
    static function packData($data)
    {
        return pack('n', Protocol\SOAServer::STX).serialize($data).pack('n', Protocol\SOAServer::ETX);
    }

    /**
     * 解包
     * @param $recv
     * @param bool $unseralize
     * @return string
     */
    static function unpackData($recv, $unseralize = true)
    {
        $data = substr($recv, 2, strlen($recv)-4);
        return unserialize($data);
    }
    /**
     * RPC调用
     * @param $function
     * @param $params
     * @return SOAClient_Result
     */
    function task($function, $params)
    {
        $retObj = new SOAClient_Result();
        $send = array('call' => $function, 'params' => $params);
        $this->request(self::TYPE_SYNC, $send, $retObj);
        return $retObj;
    }
    /**
     * 异步任务
     * @param $function
     * @param $params
     * @return SOAClient_Result
     */
    function async($function, $params)
    {
        $retObj = new SOAClient_Result();
        $send = array('call' => $function, 'params' => $params);
        $this->request(self::TYPE_ASYNC, $send, $retObj);
        if($retObj->socket != null)
        {
            $recv = $retObj->socket->recv();
            if($recv==false)
            {
                $retObj->code = SOAClient_Result::ERR_TIMEOUT;
                return $retObj;
            }
            $this->finish(self::unpackData($recv), $retObj);
        }
        return $retObj;
    }

    /**
     * 并发请求
     * @param float $timeout
     * @return int
     */
    function wait($timeout = 0.5)
    {
        $st = microtime(true);
        $t_sec = (int)$timeout;
        $t_usec = (int)(($timeout - $t_sec) * 1000 * 1000);
        $buffer = array();
        $success_num = 0;

        while(true)
        {
            $write = $error = $read = array();
            if(empty($this->wait_list))
            {
                break;
            }
            foreach($this->wait_list as $obj)
            {
                if($obj->socket !== null)
                {
                    $read[] = $obj->socket->get_socket();
                }
            }
            if(empty($read))
            {
                break;
            }
            $n = socket_select($read, $write, $error, $t_sec, $t_usec);
            if($n > 0)
            {
                //可读
                foreach($read as $sock)
                {
                    $id = (int)$sock;
                    $retObj = $this->wait_list[$id];
                    $data = $retObj->socket->recv();
                    //socket被关闭了
                    if(empty($data))
                    {
                        $retObj->code = SOAClient_Result::ERR_CLOSED;
                        unset($this->wait_list[$id], $retObj->socket);
                        continue;
                    }
                    if(!isset($buffer[$id]))
                    {
                        $_stx = unpack('nstx', substr($data, 0, 2));
                        //错误的起始符
                        if($_stx == false or $_stx['stx'] != Protocol\SOAServer::STX)
                        {
                            $retObj->code = SOAClient_Result::ERR_STX;
                            unset($this->wait_list[$id]);
                            continue;
                        }
                        $buffer[$id] = '';
                    }
                    $buffer[$id] .= $data;
                    $_etx = unpack('netx', substr($buffer[$id], -2, 2));
                    //收到结束符
                    if($_etx!=false and $_etx['etx'] === Protocol\SOAServer::ETX)
                    {
                        //成功处理
                        $this->finish(self::unpackData($buffer[$id]), $retObj);
                        $success_num++;
                    }
                    //超过最大长度将丢弃
                    elseif(strlen($data) > $this->packet_maxlen)
                    {
                        $retObj->code = SOAClient_Result::ERR_TOOBIG;
                        unset($this->wait_list[$id]);
                        continue;
                    }
                    //继续等待数据
                }
            }
            //发生超时
            if((microtime(true) - $st) > $timeout)
            {
                foreach($this->wait_list as $obj)
                {
                    $obj->code = ($obj->socket->connected)?SOAClient_Result::ERR_TIMEOUT:SOAClient_Result::ERR_CONNECT;
                }
                //清空当前列表
                $this->wait_list = array();
                return $success_num;
            }
        }
        //未发生任何超时
        $this->wait_list = array();
        return $success_num;
    }

}

class SOAClient_Result
{
    public $id;
    public $code = self::ERR_NO_READY;
    public $msg;
    public $data = null;
    public $send;  //要发送的数据
    public $type;

    /**
     * @var \Swoole\Client\TCP
     */
    public $socket = null;

    const ERR_NO_READY   = 8001; //未就绪
    const ERR_CONNECT    = 8002; //连接服务器失败
    const ERR_TIMEOUT    = 8003; //服务器端超时
    const ERR_SEND       = 8004; //发送失败
    const ERR_SERVER     = 8005; //server返回了错误码
    const ERR_UNPACK     = 8006; //解包失败了
    const ERR_STX        = 8007; //错误的起始符
    const ERR_TOOBIG     = 8008; //超过最大允许的长度
    const ERR_CLOSED     = 8009;
}