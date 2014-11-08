<?php
namespace Swoole\IFace;

interface Http
{
    function header($k, $v);
    function status($code);
    function response($content);
    function redirect($url, $mode = 301);
    function finish($content = null);
}