<?php
require_once LIBPATH . '/function/cli.php';
require_once LIBPATH.'/system/Stdio.php';
require_once LIBPATH.'/class/swoole/cli/Console.class.php';
if(!function_exists('url_process_mvc'))
{
    function url_process_mvc()
    {
        $array = array('controller'=>'page','view'=>'index');
        if(!empty($_GET["c"])) $array['controller']=$_GET["c"];
        if(!empty($_GET["v"])) $array['view']=$_GET["v"];
        if(!empty($_GET['mvc']))
        {
            $request = explode('/',$_GET['mvc'],3);
            if(count($request)!==3) Error::info('URL Error',"HTTP 404!Page Not Found!<P>Error request:<B>{$_SERVER['REQUEST_URI']}</B>");
            $array['controller']=$request[1];
            $array['view']=$request[2];
        }
        return $array;
    }
}

while(1)
{
    $cmd = Stdio::input('php>');
    if($cmd{0}==="\\")
    {
        $cmd = substr($cmd,1);
        $args = Console::getOpt($cmd);

        switch($args['args'][0])
        {
            case 'q':
            case 'quit':
                echo 'exit console',NL;
                break 2;
            case 'r':
            case 'reload':
                require_ext('runkit');
                //runkit_import();
            case 'e':
            case 'exec':
                $_GET['mvc'] = "/{$args['args'][1]}/{$args['args'][2]}";
                global $php;
                $php->runMVC('mvc');
                break;
        }
    }
    else
    {
        eval($cmd);
        echo NL;
    }
}