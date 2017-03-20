<?php
namespace Swoole\Command;

class MakeApplication
{
    static function init($dir)
    {
        mkdir($dir.'/controllers');
        mkdir($dir.'/configs');
        mkdir($dir.'/models');
        mkdir($dir.'/classes');
        mkdir($dir.'/events');
        mkdir($dir.'/templates');
        mkdir($dir.'/factory');
    }
}