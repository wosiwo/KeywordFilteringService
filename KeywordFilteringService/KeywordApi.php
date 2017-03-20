<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/5/3
 * Time: 14:49
 */
namespace Keyword;

use CheLun\Configs\RedisKey;
use Topic;
use Community;
use Swoole;
use Topic\TopicModel;

require_once __DIR__ . '/_init.php';

class KeywordApi
{

    public static function search($word='')
    {

        // $word = self::changeCharset($word);
        $ifIgnoreCase = true;       //是否忽略大小写
        $result = Swoole::$php->trie->search($word,$ifIgnoreCase);

        return $result;
    }

}

