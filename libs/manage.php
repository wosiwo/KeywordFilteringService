<?php
require __DIR__ . '/lib_config.php';
require __DIR__ . '/function/cli.php';
require __DIR__ . '/Swoole/Form.php';
require __DIR__ . '/Swoole/Swoole_js.php';
require __DIR__ . '/Swoole/Tool.php';

if(is_file('../config.php')) require '../config.php';
else define('WEBPATH',realpath(LIBPATH.'/../'));
$GLOBALS['menu'] = '<a href="manage.php">初始化目录</a> <a href="?act=cm">创建模型</a> <a href="?act=cc">创建控制器</a>';

error_reporting(E_ALL);
//如果是命令行执行
if(!empty($argv[0]))
{
    if(!empty($argv[1])) $param['cmd'] = $argv[1];
    else exit("No command!\n");
    if(!empty($argv[2])) $param['name'] = $argv[2];
    main($param);
}
else
{
    if(empty($_GET['act'])) manage_check_env();
    elseif($_GET['act']=='cc') manage_create_controller();
    elseif($_GET['act']=='cm') manage_create_model();
    //4种指令都一样
    elseif($_GET['act']=='init' or $_GET['act']=='install' or
        $_GET['act']=='create' or $_GET['act']=='setup' )
    {
        if(file_exists(WEBPATH.'/config.php')) exit('此目录下已安装Swoole框架!');
        else
        {
            copy(LIBPATH.'/code/index.php',WEBPATH.'/index.php');
            $config = file_get_contents(LIBPATH.'/code/config.php');
            if(empty($_POST['dbms'])) $_POST['dbms']='mysql';
            if(empty($_POST['dbtype'])) $_POST['dbms']='MySQL';
            if(empty($_POST['dbhost'])) $_POST['dbhost']='localhost';

            $config = str_replace('{WEBPATH}',"'".WEBPATH."'",$config);
            $config = str_replace('{DBTYPE}',trim($_POST['dbtype']),$config);
            $config = str_replace('{DBMS}',trim($_POST['dbtype']),$config);
            $config = str_replace('{DBHOST}',trim($_POST['dbhost']),$config);
            $config = str_replace('{DBUSER}',trim($_POST['dbuser']),$config);
            $config = str_replace('{DBPASSWORD}',trim($_POST['dbpassword']),$config);
            $config = str_replace('{DBNAME}',trim($_POST['dbname']),$config);
            create_require_dir();
            file_put_contents(WEBPATH.'/config.php',$config);
            file_put_contents(LIBPATH.'/.htaccess',"deny from all");
            create_controllerclass('page',true);
            echo "创建完成，<a href=/>hello world</a>";
        }
    }
}
/**
 * 主函数
 * @param $cmd
 * @return unknown_type
 */
function main($param)
{
    $cmd = $param['cmd'];
    switch($cmd)
    {
        case 'install':
        case 'setup':
            $project = $param['name'];
            $strlen = strlen($project);
            $end_char = $project{$strlen-1};
            if($end_char=="\\" or $end_char=='/') $project=substr($project,0,$strlen-1);

            if(!is_dir($project)) mkdir($project,0755,true);
            Swoole_tools::dir_copy(dirname(__FILE__),$project.'/libs');
            copy('../index.php',WEBPATH.'/index.php');
            copy('../config.php',WEBPATH.'/config.php');
            echo "Install Swoole to $project!\n";
            echo $project;
            break;
            //初始化项目
        case 'create':
        case 'init':
            create_require_dir();
            echo "craete require directory success!\n";
            break;
        case 'addc':
            create_controllerclass($param['name']);
            echo "create a new controller {$param['name']}!\n";
            break;
        case 'addm':
            create_modelclass($param['name'],$argv[2]);
            echo "create a new model {$param['name']}!\n";
            break;
        case 'debug':
            require LIBPATH . '/code/console.php';
            break;
        default:
            break;
    }
}
/**
 * 检测环境
 * @return unknown_type
 */
function manage_check_env()
{
    $title = "Swoole安装环境检测";
    echo <<<HTMLS
    <head>
    <title>$title</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
HTMLS;
    echo file_get_contents(LIBPATH.'/data/media/swoole.css');
    echo <<<HTMLS
</style>
</head>
<body>
	<div id="content">
	  <h1>$title</h1>
	  <p>{$GLOBALS['menu']}</p>
	  <form id="form1" name="form1" method="post" action="?act=init">
		<table border="1" cellpadding="0" cellspacing="0" width="450">
HTMLS;
    $phpver = explode('.',phpversion());
    echo "<tr><td>PHP版本</td>";
    if($phpver[0]<5) echo "<td class='red'>".phpversion()."，不支持此版本</td></tr>";
    elseif($phpver[1]<2) echo "<td class='green'>".phpversion()."，<span class='red'>此版本需JSON类库</span></td></tr>";
    else echo "<td class='green'>".phpversion()."</td></tr>";
    echo "<tr><td>系统信息</td><td class='green'>".php_uname('a')."</td></tr>";
    echo "<tr><td>PHP配置文件</td><td class='green'>".php_ini_loaded_file()."</td></tr>";
    echo "<tr><td>Web服务器</td><td class='green'>".$_SERVER['SERVER_SOFTWARE']."</td></tr>";

    //MySQL支持
    if(extension_loaded('mysql'))
    {
        $dbtype[] = 'MySQL';
        echo "<tr><td>MySQL</td><td class='green'>支持</td></tr>";
    }
    else echo "<tr><td>MySQL</td><td class='red'>不支持</td></tr>";
    //MySQLi支持
    if(extension_loaded('mysqli'))
    {
        $dbtype[] = 'MySQL2';
        echo "<tr><td>MySQLi</td><td class='green'>支持</td></tr>";
    }
    else echo "<tr><td>MySQLi</td><td class='red'>不支持</td></tr>";
    //PDO支持
    if(extension_loaded('pdo'))
    {
        $dbtype[] = 'PdoDB';
        echo "<tr><td>PDO</td><td class='green'>支持</td></tr>";
    }
    else echo "<tr><td>PDO</td><td class='red'>不支持</td></tr>";

    //zlib支持
    if(extension_loaded('zlib'))
    {
        echo "<tr><td>Zlib压缩</td><td class='green'>支持</td></tr>";
    }
    else echo "<tr><td>Zlib压缩</td><td class='red'>不支持，无法启用内容压缩功能</td></tr>";

    //memcache
    if(extension_loaded('memcache') or extension_loaded('memcached'))
    {
        echo "<tr><td>memcache</td><td class='green'>支持</td></tr>";
    }
    else echo "<tr><td>memcache</td><td class='red'>不支持memcache缓存</td></tr>";

    //mbstring
    if(extension_loaded('mbstring'))
    {
        echo "<tr><td>mbstring</td><td class='green'>支持</td></tr>";
    }
    else echo "<tr><td>mbstring</td><td class='red'>不支持宽字符处理函数</td></tr>";

    $dbtype_check = Form::select('dbtype',$dbtype,null,true);
    echo "<tr><td>数据库驱动类型</td><td>{$dbtype_check}</td></tr>";

    $dbms = Form::select('dbms',array('MySQL','PostgreSQL','Oracle','SQLServer','SQLite'),null,true);
    echo "<tr><td>数据库类型</td><td>{$dbms}</td></tr>";
    echo '<tr><td>数据库主机</td><td>'.Form::input('dbhost').' (默认为localhost)</td></tr>';
    echo '<tr><td>数据库名称</td><td>'.Form::input('dbname').'</td></tr>';
    echo '<tr><td>数据库用户名</td><td>'.Form::input('dbuser').'</td></tr>';
    echo '<tr><td>数据库密码</td><td>'.Form::input('dbpassword').'</td></tr>';
    echo '<tr><td colspan=2><input type="submit" name="button" id="button" value="确认并开始安装" /></td></tr>';
    //结束
    echo '</table></form></div></body></html>';
}

function manage_create_controller()
{
    if($_POST)
    {
        $name = trim($_POST['name']);
        if(empty($name)) return false;
        if(is_file(WEBPATH.'/apps/controllers/'.$name.'.php'))
        {
            Swoole_js::js_back('已存在此控制器');
            return false;
        }
        else
        {
            create_controllerclass($name);
            Swoole_js::js_back('创建成功');
            return true;
        }
    }
    $title = "Swoole创建控制器";
    echo <<<HTMLS
    <head>
    <title>$title</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
HTMLS;
    echo file_get_contents(LIBPATH.'/data/media/swoole.css');
    echo <<<HTMLS
</style>
</head>
<body>
	<div id="content">
	  <h1>$title</h1>
	  <p>{$GLOBALS['menu']}</p>
	  <form id="form1" name="form1" method="post" action="">
		<table border="1" cellpadding="0" cellspacing="0" width="450">
HTMLS;

    echo '<tr><td>控制器名称</td><td>'.Form::input('name').'</td></tr>';
    echo '<tr><td colspan=2><input type="submit" name="button" id="button" value="确认并创建控制器" /></td></tr>';
    //结束
    echo '</table></form></div></body></html>';
}

function manage_create_model()
{
    if($_POST)
    {
        $name = trim($_POST['name']);
        $table = trim($_POST['table']);
        if(empty($name) or empty($table)) return false;
        if(is_file(WEBPATH.'/apps/models/'.$name.'.model.php'))
        {
            Swoole_js::js_back('已存在此模型');
            return false;
        }
        else
        {
            create_modelclass($name,$table);
            Swoole_js::js_back('创建成功');
            return true;
        }
    }
    $title = "Swoole创建模型";
    echo <<<HTMLS
    <head>
    <title>$title</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
HTMLS;
    echo file_get_contents(LIBPATH.'/data/media/swoole.css');
    echo <<<HTMLS
</style>
</head>
<body>
	<div id="content">
	  <h1>$title</h1>
	  <p>{$GLOBALS['menu']}</p>
	  <form id="form1" name="form1" method="post" action="">
		<table border="1" cellpadding="0" cellspacing="0" width="450">
HTMLS;
    echo '<tr><td>模型名称</td><td>'.Form::input('name').'</td></tr>';
    echo '<tr><td>数据库表名</td><td>'.Form::input('table').'</td></tr>';
    echo '<tr><td colspan=2><input type="submit" name="button" id="button" value="确认并创建" /></td></tr>';
    //结束
    echo '</table></form></div></body></html>';
}
