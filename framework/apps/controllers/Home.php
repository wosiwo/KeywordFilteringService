<?php
namespace App\Controller;
use Swoole;

class Home extends Swoole\Controller
{
    function __construct($swoole)
    {
        parent::__construct($swoole);
        Swoole::$php->session->start();
        Swoole\Auth::loginRequire();
    }

    function index()
    {
        echo __METHOD__;
    }
}