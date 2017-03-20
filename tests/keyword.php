<?php
require dirname(__DIR__) . '/config.php';


//Swoole\Loader::addNameSpace('Topic', dirname(dirname(__DIR__)) . '/CheLun/Topic/apps/models');

\Swoole\Loader::addNameSpace('Configs', WEBPATH . '/apps/configs');


//载入字典
//启动时加载字典数据
$file = array('bin'=>realpath(ROOTPATH.'/dict/dict_all.dat'),'source'=>realpath(ROOTPATH.'/dict/dict_all.dat'));
Swoole::$php->trie = new App\Trie($file);
Swoole::$php->trie->nodes = Swoole::$php->trie->getBinaryDict($file['bin']);

//print_r(Swoole::$php->trie->nodes);

//\Swoole::$php->db->debug = 1;


$word = 'wosiwo';
$ret = Keyword\KeywordApi::search($word);


var_dump($ret);