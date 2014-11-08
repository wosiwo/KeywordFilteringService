<?php
namespace Swoole\IFace;

interface Log
{
    /**
     * 写入日志
     * @param $type string 类型
     * @param $msg  string 内容
     */
    function put($msg, $type = "INFO");
}