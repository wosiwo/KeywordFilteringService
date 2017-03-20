<?php
namespace App\Model;
use Swoole;

class User extends Swoole\Model
{
    /**
     * 表名
     * @var string
     */
    public $table = 'user_login';

    function test()
    {
        $a = model('Test');
        $key = '1234';
        $this->swoole->cache->delete($key);
        $this->db->getAffectedRows();
    }
}