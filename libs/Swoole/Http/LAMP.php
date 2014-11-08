<?php
namespace Swoole\Http;

class LAMP implements \Swoole\IFace\Http
{
    function header($k,$v)
    {
        header($k.': '.$v);
    }

    function status($code)
    {
        header('HTTP/1.1 '.\Swoole\Response::$HTTP_HEADERS[$code]);
    }

    function response($content)
    {
        exit($content);
    }

    function redirect($url, $mode=301)
    {
        header("HTTP/1.1 ".\Swoole\Response::$HTTP_HEADERS[$mode]);
        header("Location: ".$url);
    }

    function finish($content = null)
    {
        exit($content);
    }
}