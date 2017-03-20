<?php
/**
 * 根据字典字符串生成ASCII码目录树
 */

require 'filter.class.php';
use My\Filter as F;


	$file = array('bin'=>realpath(__DIR__.'/dict_all.dat'),'source'=>realpath(__DIR__.'/dict_all.txt'));
	$trie = new  F\Trie($file);

	//读取原生字典字符串
	$words = $trie->getDict($file['source']);
	//工具字典字符串构建字典和权重数组
	$nodes = $trie->getTree($words);
	//字典数组序列化后保存到文件
	$status = $trie->putBinaryDict($file['bin'],$nodes);


	var_dump($status);



