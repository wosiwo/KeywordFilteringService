<?php
namespace App\Controller;
use Swoole;

class Http extends Swoole\Controller
{
    function stop()
    {
        //请使用此函数代替exit
        $this->http->finish("<h1>exit</h1>");
    }

    function except()
    {
        throw new \Exception("except");
    }

    function goto_baidu()
    {
        $this->request->redirect("http://www.baidu.com/");
    }

    function header()
    {
        //发送Http状态码，如500, 404等等
        $this->http->status(302);
        //使用此函数代替PHP的header函数
        $this->http->header('Location', 'http://www.baidu.com/');
    }

    function cookie()
    {
        $this->http->setcookie("swoole", "framework", time() + 3600, '/', 'framework.com');
    }
}