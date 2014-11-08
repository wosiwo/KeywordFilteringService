<?php
namespace Swoole\Protocol;
use Swoole;

abstract class CometServer extends WebSocket
{
    /**
     * 将Web服务器设置为异步模式，不再回调onRequest，而是回调onAsyncRequest
     * @var bool
     */
    public $async = true;

    /**
     * 某个请求超过最大时间后，务必要返回内容
     * @var int
     */
    protected $request_timeout = 50;

    /**
     * @param $serv \swoole_server
     */
    function onStart($serv)
    {
        $serv->addTimer(1000);
    }

    /**
     * 异步请求回调
     * @param Swoole\Request $request
     */
    function onAsyncRequest(Swoole\Request $request)
    {
        $this->onMessage($request->fd, $request->post);
    }

    /**
     * 向浏览器发送数据
     * @param int    $client_id
     * @param string $data
     * @return bool
     */
    function send($client_id, $data)
    {
        /**
         * @var $request Swoole\Request
         */
        $request = $this->requests[$client_id];

        if ($request->isWebSocket())
        {
            return parent::send($client_id, $data);
        }
        else
        {
            $response = new Swoole\Response;
            $response->send_head('Access-Control-Allow-Origin', 'http://127.0.0.1');
            $response->body = json_encode(array('success' => 1, 'text' => $data));
            return $this->response($request, $response);
        }
    }

    /**
     * 定时器，检查某些连接是否已超过最大时间
     * @param $serv
     * @param $interval
     */
    function onTimer($serv, $interval)
    {
        $now = time();
        //echo "timer $interval\n";
        foreach($this->requests as $request)
        {
            if ($request->time < $now - $this->request_timeout)
            {
                $response = new Swoole\Response;
                $response->send_head('Access-Control-Allow-Origin', 'http://127.0.0.1');
                $response->body = json_encode(array('success' => 0, 'text' => 'timeout'));
                $this->response($request, $response);
            }
        }
    }
}