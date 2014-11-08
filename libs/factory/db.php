<?php
global $php;
if(empty($php->config['db']['master']) and defined('DBHOST'))
{
	$php->config['db']['master'] = array(
			'type'    => Swoole\Database::TYPE_MYSQL, //Database Driver，可以选择PdoDB , MySQL, MySQL2(MySQLi) , AdoDb(需要安装adodb插件)
			'host'    => DBHOST,
			'port'    => DBPORT,
			'dbms'    => DBMS,
			'engine'  => DBENGINE,
			'user'    => DBUSER,
			'passwd'  => DBPASSWORD,
			'name'    => DBNAME,
			'charset' => DBCHARSET,
			'setname' => true,
	);
	if(defined('DBPERSISTENT')) $php->config['db']['persistent'] = DBPERSISTENT;
	if(defined('DBSETNAME')) $php->config['db']['ifsetname'] = DBSETNAME;
	else $php->config['db']['ifsetname'] = false;
}

$db = new Swoole\Database($php->config['db']['master']);
$db->connect();
