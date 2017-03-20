<?php
namespace App\Observer;

use Swoole\Record;

class ModelUpdate implements \SplObserver
{
    function update(\SplSubject $o)
    {
        var_dump($o->get());exit;
    }
}