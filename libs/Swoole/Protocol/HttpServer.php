<?php
namespace Swoole\Protocol;

use Swoole;

/**
 * HTTP Server
 * @author Tianfeng.Han
 * @link http://www.swoole.com/
 * @package Swoole
 * @subpackage net.protocol
 */
class HttpServer extends Swoole\Protocol\WebServer implements  Swoole\IFace\Protocol
{
    /**
     * 开启异步服务，启用后onRequest将不再直接发送response到浏览器
     * @var bool
     */
    public $async = false;

    protected $swoole_server;
    protected $buffer_header = array();
    protected $buffer_maxlen = 65535; //最大POST尺寸，超过将写文件

    const DATE_FORMAT_HTTP = 'D, d-M-Y H:i:s T';

    const HTTP_EOF = "\r\n\r\n";
    const HTTP_HEAD_MAXLEN = 2048; //http头最大长度不得超过2k

    const ST_FINISH = 1; //完成，进入处理流程
    const ST_WAIT   = 2; //等待数据
    const ST_ERROR  = 3; //错误，丢弃此包

    function __construct($config = array())
    {
        parent::__construct($config);
        $mimes = require(LIBPATH . '/data/mimes.php');
        $this->mime_types = array_flip($mimes);
        $this->config = $config;
        $this->parser = new Swoole\Http\Parser;
    }

    function onStart($serv, $worker_id = 0)
    {
        if (!defined('WEBROOT'))
        {
            define('WEBROOT', $this->config['server']['webroot']);
        }

        if (isset($this->config['server']['user']))
        {
            Swoole\Console::changeUser($this->config['server']['user']);
        }

        if ($this->server instanceof Swoole\Network\Server and isset($this->config['server']['process_rename']))
        {
            global $argv;
            if ($worker_id >= $serv->setting['worker_num'])
            {
                Swoole\Console::setProcessName('php '.$argv[0].': task');
            }
            else
            {
                Swoole\Console::setProcessName('php '.$argv[0].': worker');
            }
        }

        Swoole\Error::$echo_html = true;
        $this->swoole_server = $serv;
        Swoole::$php->server = $this;
        $this->log(self::SOFTWARE . "[#{$worker_id}]. running. on {$this->server->host}:{$this->server->port}");
        register_shutdown_function(array($this, 'onError'));
    }

    /**
     * @return \swoole_server
     */
    function getSwooleServer()
    {
        return $this->swoole_server;
    }

    function onShutdown($serv)
    {
        $this->log(self::SOFTWARE . " shutdown");
    }

    function onConnect($serv, $client_id, $from_id)
    {
        $this->log("client[#$client_id@$from_id] connect");
    }


    function onClose($serv, $client_id, $from_id)
    {
        $this->log("client[#$client_id@$from_id] close");
        unset($this->requests[$client_id], $this->buffer_header[$client_id]);
    }

    function checkHeader($client_id, $http_data)
    {
        //新的连接
        if (!isset($this->requests[$client_id]))
        {
            if (!empty($this->buffer_header[$client_id]))
            {
                $http_data = $this->buffer_header[$client_id].$http_data;
            }
            //HTTP结束符
            $ret = strpos($http_data, self::HTTP_EOF);
            //没有找到EOF，继续等待数据
            if ($ret === false)
            {
                return false;
            }
            else
            {
                $this->buffer_header[$client_id] = '';
                $request = new Swoole\Request;
                //GET没有body
                list($header, $request->body) = explode(self::HTTP_EOF, $http_data, 2);
                $request->head = $this->parser->parseHeader($header);
                //使用head[0]保存额外的信息
                $request->meta = $request->head[0];
                unset($request->head[0]);
                //保存请求
                $this->requests[$client_id] = $request;
                //解析失败
                if ($request->head == false)
                {
                    $this->log("parseHeader fail. header=".$header);
                    return false;
                }
            }
        }
        //POST请求需要合并数据
        else
        {
            $request = $this->requests[$client_id];
            $request->body .= $http_data;
        }
        return $request;
    }

    function checkPost($request)
    {
        if (isset($request->head['Content-Length']))
        {
            //超过最大尺寸
            if (intval($request->head['Content-Length']) > $this->config['access']['post_maxsize'])
            {
                $this->log("checkPost fail. post_data is too long.");
                return self::ST_ERROR;
            }
            //不完整，继续等待数据
            if (intval($request->head['Content-Length']) > strlen($request->body))
            {
                return self::ST_WAIT;
            }
            //长度正确
            else
            {
                return self::ST_FINISH;
            }
        }
        $this->log("checkPost fail. Not have Content-Length.");
        //POST请求没有Content-Length，丢弃此请求
        return self::ST_ERROR;
    }

    function checkData($client_id, $http_data)
    {
        if (isset($this->buffer_header[$client_id]))
        {
            $http_data = $this->buffer_header[$client_id].$http_data;
        }
        //检测头
        $request = $this->checkHeader($client_id, $http_data);
        $this->log("new http request. fd={$client_id}");
        //错误的http头
        if ($request === false)
        {
            $this->buffer_header[$client_id] = $http_data;
            //超过最大HTTP头限制了
            if (strlen($http_data) > self::HTTP_HEAD_MAXLEN)
            {
                $this->log("http header is too long.");
                return self::ST_ERROR;
            }
            else
            {
                $this->log("wait request data. fd={$client_id}");
                return self::ST_WAIT;
            }
        }
        //POST请求需要检测body是否完整
        if ($request->meta['method'] == 'POST')
        {
            return $this->checkPost($request);
        }
        //GET请求直接进入处理流程
        else
        {
            return self::ST_FINISH;
        }
    }

    /**
     * 接收到数据
     * @param $serv \swoole_server
     * @param $client_id
     * @param $from_id
     * @param $data
     * @return null
     */
    function onReceive($serv, $client_id, $from_id, $data)
    {
        //检测request data完整性
        $ret = $this->checkData($client_id, $data);
        switch($ret)
        {
            //错误的请求
            case self::ST_ERROR;
                $this->server->close($client_id);
                return;
            //请求不完整，继续等待
            case self::ST_WAIT:
                return;
            default:
                break;
        }
        //完整的请求
        //开始处理
        $request = $this->requests[$client_id];
	    $info = $serv->connection_info($client_id);
        $request->remote_ip = $info['remote_ip'];
	    $_SERVER['SWOOLE_CONNECTION_INFO'] = $info;

        $this->parseRequest($request);
        $request->fd = $client_id;
        $this->currentRequest = $request;
        if ($this->async)
        {
            $this->onAsyncRequest($request);
        }
        else
        {
            //处理请求，产生response对象
            $response = $this->onRequest($request);
            //发送response
            $this->response($request, $response);
        }
    }

    function onAsyncRequest(Swoole\Request $request)
    {
        throw new \Exception(__METHOD__." is an abstract method.");
    }

    function afterResponse(Swoole\Request $request, Swoole\Response $response)
    {
        if (!$this->keepalive or $response->head['Connection'] == 'close')
        {
            $this->server->close($request->fd);
        }
        $request->unsetGlobal();
        //清空request缓存区
        unset($this->requests[$request->fd]);
        unset($request);
        unset($response);
    }

    /**
     * 解析请求
     * @param $request Swoole\Request
     * @return null
     */
    function parseRequest($request)
    {
        $url_info = parse_url($request->meta['uri']);
        $request->time = time();
        $request->meta['path'] = $url_info['path'];
        if (isset($url_info['fragment'])) $request->meta['fragment'] = $url_info['fragment'];
        if (isset($url_info['query']))
        {
            parse_str($url_info['query'], $request->get);
        }
        //POST请求,有http body
        if ($request->meta['method'] === 'POST')
        {
            $this->parser->parseBody($request);
        }
        //解析Cookies
        if (!empty($request->head['Cookie']))
        {
            $this->parser->parseCookie($request);
        }
    }

    /**
     * 发送响应
     * @param $request Swoole\Request
     * @param $response Swoole\Response
     * @return bool
     */
    function response(Swoole\Request $request, Swoole\Response $response)
    {
        if (!isset($response->head['Date']))
        {
            $response->head['Date'] = gmdate("D, d M Y H:i:s T");
        }
        if (!isset($response->head['Connection']))
        {
            //keepalive
            if ($this->keepalive and (isset($request->head['Connection']) and strtolower($request->head['Connection']) == 'keep-alive'))
            {
                $response->head['KeepAlive'] = 'on';
                $response->head['Connection'] = 'keep-alive';
            }
            else
            {
                $response->head['KeepAlive'] = 'off';
                $response->head['Connection'] = 'close';
            }
        }
        //过期命中
        if ($this->expire and $response->http_status == 304)
        {
            $out = $response->getHeader();
            return $this->server->send($request->fd, $out);
        }
        //压缩
        if ($this->gzip)
        {
            $response->head['Content-Encoding'] = 'deflate';
            $response->body = gzdeflate($response->body, $this->config['server']['gzip_level']);
        }
        $out = $response->getHeader().$response->body;
        $ret = $this->server->send($request->fd, $out);
        $this->afterResponse($request, $response);
        return $ret;
    }

    /**
     * 发生了http错误
     * @param                 $code
     * @param Swoole\Response $response
     * @param string          $content
     */
    function httpError($code, Swoole\Response $response, $content = '')
    {
        $response->send_http_status($code);
        $response->head['Content-Type'] = 'text/html';
        $response->body = Swoole\Error::info(Swoole\Response::$HTTP_HEADERS[$code], "<p>$content</p><hr><address>" . self::SOFTWARE . " at {$this->server->host} Port {$this->server->port}</address>");
    }

    /**
     * 捕获错误
     */
    function onError()
    {
        $error = error_get_last();
        if (!isset($error['type'])) return;
        switch ($error['type'])
        {
            case E_ERROR :
            case E_PARSE :
            case E_DEPRECATED:
            case E_CORE_ERROR :
            case E_COMPILE_ERROR :
                break;
            default:
                return;
        }
        $errorMsg = "{$error['message']} ({$error['file']}:{$error['line']})";
        $message = Swoole\Error::info(self::SOFTWARE." Application Error", $errorMsg);
        if (empty($this->currentResponse))
        {
            $this->currentResponse = new Swoole\Response();
        }
        $this->currentResponse->send_http_status(500);
        $this->currentResponse->body = $message;
        $this->response($this->currentRequest, $this->currentResponse);
    }

    /**
     * 处理请求
     * @param $request
     * @return Swoole\Response
     */
    function onRequest(Swoole\Request $request)
    {
        $response = new Swoole\Response;
        $this->currentResponse = $response;

        //请求路径
        if ($request->meta['path'][strlen($request->meta['path']) - 1] == '/')
        {
            $request->meta['path'] .= $this->config['request']['default_page'];
        }
        if ($this->doStaticRequest($request, $response))
        {
             //pass
        }
        /* 动态脚本 */
        elseif (isset($this->dynamic_ext[$request->ext_name]) or empty($ext_name))
        {
            $this->processDynamic($request, $response);
        }
        else
        {
            $this->httpError(404, $response, "Http Not Found({($request->meta['path']})");
        }
        return $response;
    }

    /**
     * 过滤请求，阻止静止访问的目录，处理静态文件
     */
    function doStaticRequest($request, $response)
    {
        $path = explode('/', trim($request->meta['path'], '/'));
        //扩展名
        $request->ext_name = $ext_name = Swoole\Upload::file_ext($request->meta['path']);
        /* 检测是否拒绝访问 */
        if (isset($this->deny_dir[$path[0]]))
        {
            $this->httpError(403, $response, "服务器拒绝了您的访问({$request->meta['path']})");
            return true;
        }
        /* 是否静态目录 */
        elseif (isset($this->static_dir[$path[0]]) or isset($this->static_ext[$ext_name]))
        {
            return $this->process_static($request, $response);
        }
        return false;
    }

    /**
     * 静态请求
     * @param $request
     * @param $response
     * @return unknown_type
     */
    function process_static($request, Swoole\Response $response)
    {
        $path = $this->document_root . '/' . $request->meta['path'];
        if (is_file($path))
        {
            $read_file = true;
            if($this->expire)
            {
                $expire = intval($this->config['server']['expire_time']);
                $fstat = stat($path);
                //过期控制信息
                if (isset($request->head['If-Modified-Since']))
                {
                    $lastModifiedSince = strtotime($request->head['If-Modified-Since']);
                    if ($lastModifiedSince and $fstat['mtime'] <= $lastModifiedSince)
                    {
                        //不需要读文件了
                        $read_file = false;
                        $response->send_http_status(304);
                    }
                }
                else
                {
                    $response->head['Cache-Control'] = "max-age={$expire}";
                    $response->head['Pragma'] = "max-age={$expire}";
                    $response->head['Last-Modified'] = date(self::DATE_FORMAT_HTTP, $fstat['mtime']);
                    $response->head['Expires'] = "max-age={$expire}";
                }
            }
            $ext_name = Swoole\Upload::file_ext($request->meta['path']);
            if($read_file)
            {
                $response->head['Content-Type'] = $this->mime_types[$ext_name];
                $response->body = file_get_contents($path);
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 动态请求
     * @param $request
     * @param $response
     * @return unknown_type
     */
    function processDynamic(Swoole\Request $request, Swoole\Response $response)
    {
        $path = $this->document_root . '/' . $request->meta['path'];
        if (is_file($path))
        {
            $request->setGlobal();
            $response->head['Content-Type'] = 'text/html';
            ob_start();
            try
            {
                include $path;
            }
            catch (\Exception $e)
            {
                $response->send_http_status(404);
                $response->body = $e->getMessage() . '!<br /><h1>' . self::SOFTWARE . '</h1>';
            }
            $response->body = ob_get_contents();
            ob_end_clean();
        }
        else
        {
            $this->httpError(404, $response, "页面不存在({$request->meta['path']})！");
        }
    }
}
