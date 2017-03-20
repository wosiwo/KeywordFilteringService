<?php
namespace App\Handler;

use Swoole;

class Hello2 implements Swoole\IFace\EventHandler
{
    function trigger($type, $data)
    {
        echo "Handler2: ";
        var_dump($type);
        var_dump($data);
    }
}