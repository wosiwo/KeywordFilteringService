<?php
namespace App\Controller;
use Swoole;

class Session extends Swoole\Controller
{
    function write()
    {
        //使用此函数代替PHP的session_start
        $this->session->start();
        $_SESSION['test'] = 1;
        echo "ok";
    }

    function read()
    {
        //使用此函数代替PHP的session_start
        $this->session->start();
        var_dump($_SESSION);
    }
}
