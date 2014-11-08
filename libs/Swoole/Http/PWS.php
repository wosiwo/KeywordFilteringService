<?php
namespace Swoole\Http;

/**
 * Class Http_LAMP
 * @package Swoole
 */
class PWS implements \Swoole\IFace\Http
{
    function header($k, $v)
    {
        $k = ucwords($k);
        \Swoole::$php->response->send_head($k, $v);
    }

    function status($code)
    {
        \Swoole::$php->response->send_http_status($code);
    }

    function response($content)
    {
        $this->finish($content);
    }

    function redirect($url, $mode = 301)
    {
        \Swoole::$php->response->send_http_status($mode);
        \Swoole::$php->response->send_head('Location', $url);
    }

    function finish($content = null)
    {
        \Swoole::$php->request->finish = 1;
        if($content) \Swoole::$php->response->body = $content;
        throw new \Exception;
    }
}
