<?php
/**
 * 导入所有controller
 * @return unknown_type
 */
function import_all_controller($apps_path)
{
    $d = dir($apps_path.'/controllers');
    if(empty($d))
    {
        return false;
    }
    while($file = $d->read())
    {
        $name = basename($file,'.php');
        //不符合命名规则
        if(!preg_match('/^[a-z0-9_]+$/i',$name)) continue;
        //首字母大写的controller为基类控制器，不直接提供响应
        if(ord($name{0})>64 and ord($name{0})<91) continue;
        $path = $d->path.'/'.$file;
        import_controller($name,$path);
    }
    $d->close();
}
function import_controller($name, $path)
{
    global $php;
    require($path);
    $php->env['controllers'][$name] = array('path'=>$path,'time'=>time());
}
/**
 * 检查是否加载了某个扩展
 * @param $ext_name
 * @return unknown_type
 */
function require_ext($ext_name)
{
    if(extension_loaded($ext_name)) return true;
    else return new Error('require php extension <b>'.$ext_name.'</b>');
}
/**
 * 导入所有model
 * @return unknown_type
 */
function import_all_model()
{
    global $php;
    $d = dir(APPSPATH.'/models');
    while($file=$d->read())
    {
        $name = basename($file,'.model.php');
        //不符合命名规则
        if(!preg_match('/^[a-z0-9_]+$/i',$name)) continue;
        //首字母大写的controller为基类控制器，不直接提供响应
        if(ord($name{0})>64 and ord($name{0})<91) continue;
        $path = $d->path.'/'.$file;
        require($path);
        $php->env['controllers'][$name] = $path;
    }
    $d->close();
}
/**
 * 创建控制器类的文件
 * @param $name
 * @return unknown_type
 */
function create_controllerclass($name,$hello=false)
{
    $content  = "";
    $content .= "<?php\n";
    $content .= "class {$name} extends Controller\n";
    $content .= "{\n";
    $content .= "	function __construct(\$swoole)\n";
    $content .= "	{\n";
    $content .= "	    parent::__construct(\$swoole);\n";
    $content .= "	}\n";
    //添加一个hello vie
    if($hello)
    {
        $content .= "	function index(\$swoole)\n";
        $content .= "	{\n";
        $content .= "	    echo 'hello world.This page build by <a href=http://www.swoole.com/>swoole</a>!';\n";
        $content .= "	}\n";
    }
    $content .= "}";
    file_put_contents(WEBPATH.'/apps/controllers/'.$name.'.php',$content);
}
/**
 * 创建模型类的文件
 * @param $name
 * @return unknown_type
 */
function create_modelclass($name,$table='')
{
    $content  = "";
    $content .= "<?php\n";
    $content .= "class {$name} extends Model\n";
    $content .= "{\n";
    $content .= "	//Here write Database table's name\n";
    $content .= "	var \$table = '{$table}';\n";
    $content .= "}";
    file_put_contents(WEBPATH.'/apps/models/'.$name.'.model.php',$content);
}
/**
 * 创建必需的目录
 * @return unknown_type
 */
function create_require_dir()
{
    /**
     * 建立MVC目录
     */
    if(!is_dir(WEBPATH.'/apps')) mkdir(WEBPATH.'/apps',0755);
    if(!is_dir(WEBPATH.'/apps/controllers')) mkdir(WEBPATH.'/apps/controllers',0755);
    if(!is_dir(WEBPATH.'/apps/models')) mkdir(WEBPATH.'/apps/models',0755);

    /**
     * 建立缓存的目录
     */
    if(!is_dir(WEBPATH.'/cache')) mkdir(WEBPATH.'/cache',0755);
    if(!is_dir(WEBPATH.'/cache/pages_c')) mkdir(WEBPATH.'/cache/pages_c',0777);
    if(!is_dir(WEBPATH.'/cache/templates_c')) mkdir(WEBPATH.'/cache/templates_c',0777);
    if(!is_dir(WEBPATH.'/cache/filecache')) mkdir(WEBPATH.'/cache/filecache',0777);

    /**
     * Smarty的模板目录
     */
    if(!is_dir(WEBPATH.'/templates')) mkdir(WEBPATH.'/templates',0755);

    /**
     * 建立静态文件的目录
     */
    if(!is_dir(WEBPATH.'/static')) mkdir(WEBPATH.'/static',0755);
    if(!is_dir(WEBPATH.'/static/images')) mkdir(WEBPATH.'/static/images',0755);
    if(!is_dir(WEBPATH.'/static/css')) mkdir(WEBPATH.'/static/css',0755);
    if(!is_dir(WEBPATH.'/static/uploads')) mkdir(WEBPATH.'/static/uploads',0755);
    if(!is_dir(WEBPATH.'/static/js')) mkdir(WEBPATH.'/static/js',0755);

    /**
     * 建立外部扩展类目录
     */
    if(!is_dir(WEBPATH.'/class')) mkdir(WEBPATH.'/class',0755);
    /**
     * 建立网站字典目录
     */
    if(!is_dir(WEBPATH.'/dict')) mkdir(WEBPATH.'/dict',0755);
    /**
     * 建立Swoole插件系统目录
     */
    if(!is_dir(WEBPATH.'/swoole_plugin')) mkdir(WEBPATH.'/swoole_plugin',0755);
}
function url_route_default($url_path)
{
    $mvc = array('controller'=>'page','view'=>'index');
    if(!empty($url_path))
    {
        $request = explode('/',$url_path,2);
        $mvc['controller']=$request[0];
        $mvc['view']=$request[0];
    }
    return $mvc;
}
