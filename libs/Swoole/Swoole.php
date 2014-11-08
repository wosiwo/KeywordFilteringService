<?php
//加载核心的文件
require_once __DIR__ . '/Loader.php';
require_once __DIR__ . '/ModelLoader.php';
require_once __DIR__ . '/PluginLoader.php';
/**
 * Swoole系统核心类，外部使用全局变量$php引用
 * Swoole框架系统的核心类，提供一个swoole对象引用树和基础的调用功能
 * @package SwooleSystem
 * @author Tianfeng.Han
 * @subpackage base
 * @property \Swoole\Database $db
 * @property \Swoole\IFace\Cache $cache
 * @property \Swoole\Upload $upload
 * @property \Swoole\Event $event
 * @property \Swoole\Session $session
 * @property \Swoole\Template $tpl
 * @property \redis  $redis
 * @property \MongoClient $mongo
 * @property \Swoole\Config $config
 * @property \Swoole\Http\PWS $http
 * @property \Swoole\Log $log
 * @property \Swoole\Auth $user
 */
class Swoole
{
    //所有全局对象都改为动态延迟加载
    //如果希望启动加载,请使用Swoole::load()函数
    /**
     * @var Swoole\Network\Protocol\HttpServer
     */
    public $server;
    public $protocol;
    public $request;

    /**
     * @var Swoole\Response
     */
    public $response;
    static public $app_path;
    /**
     * 可使用的组件
     */
    static $autoload_libs = array(
		'redis' => true,  //redis
        'mongo' => true,  //mongodb
    	'db' => true,  //数据库
    	'tpl' => true, //模板系统
    	'cache' => true, //缓存
    	'config' => true, //缓存
    	'event' => true, //异步事件
    	'log' => true, //日志
    	'kdb' => true, //key-value数据库
    	'upload' => true, //上传组件
    	'user' => true,   //用户验证组件
        'session' => true, //session
        'http' => true, //http
    );

    static $charset = 'utf-8';
    static $debug = false;

    static $setting = array();
    public $error_call = array();
    /**
     * Swoole类的实例
     * @var Swoole
     */
    static public $php;
    public $pagecache;

    /**
     * 发生错误时的回调函数
     */
    public $error_callback;

    public $load;
    public $model;
    public $plugin;
    public $genv;
    public $env;

    protected $hooks = array();
    protected $router_function;

    const HOOK_INIT  = 1; //初始化
    const HOOK_ROUTE = 2; //URL路由
    const HOOK_CLEAN = 3; //清理

    private function __construct()
    {
        if (!defined('DEBUG')) define('DEBUG', 'on');
        if (DEBUG == 'off') \error_reporting(0);

        $this->env['sapi_name'] = php_sapi_name();
        if ($this->env['sapi_name'] != 'cli')
        {
            Swoole\Error::$echo_html = true;
        }

        if (empty(self::$app_path))
        {
            if (defined('WEBPATH'))
            {
                self::$app_path = WEBPATH.'/apps';
            }
            else
            {
                Swoole\Error::info("core error", __CLASS__.": Swoole::\$app_path and WEBPATH empty.");
            }
        }

        define('APPSPATH', self::$app_path);

        //将此目录作为App命名空间的根目录
        Swoole\Loader::setRootNS('App', self::$app_path . '/classes');

        $this->load = new Swoole\Loader($this);
        $this->model = new Swoole\ModelLoader($this);

        //路由钩子，URLRewrite
        $this->addHook(Swoole::HOOK_ROUTE, 'swoole_urlrouter_rewrite');
        //mvc
        $this->addHook(Swoole::HOOK_ROUTE, 'swoole_urlrouter_mvc');
        //设置路由函数
        $this->router(array($this, 'urlRoute'));
    }

    static function getInstance()
    {
        if (!self::$php)
        {
            self::$php = new Swoole;
        }
        return self::$php;
    }

    /**
     * 获取资源消耗
     * @return unknown_type
     */
    function runtime()
    {
        // 显示运行时间
        $return['time'] = number_format((microtime(true)-$this->env['runtime']['start']),4).'s';

        $startMem =  array_sum(explode(' ',$this->env['runtime']['mem']));
        $endMem   =  array_sum(explode(' ',memory_get_usage()));
        $return['memory'] = number_format(($endMem - $startMem)/1024).'kb';
        return $return;
    }
    /**
     * 压缩内容
     * @return unknown_type
     */
    function gzip()
    {
        //不要在文件中加入UTF-8 BOM头
        //ob_end_clean();
        ob_start("ob_gzhandler");
        #是否开启压缩
        if(function_exists('ob_gzhandler')) ob_start('ob_gzhandler');
        else ob_start();
    }
    /**
     * 初始化环境
     * @return unknown_type
     */
    function __init()
    {
        #DEBUG
        if (defined('DEBUG') and DEBUG == 'on')
        {
            #捕获错误信息
//            set_error_handler('swoole_error_handler');
            #记录运行时间和内存占用情况
            $this->env['runtime']['start'] = microtime(true);
            $this->env['runtime']['mem'] = memory_get_usage();
        }
        $this->callHook(self::HOOK_INIT);
    }

    /**
     * 执行Hook函数列表
     * @param $type
     */
    protected function callHook($type)
    {
        if(isset($this->hooks[$type]))
        {
            foreach($this->hooks[$type] as $f)
            {
                if(!is_callable($f))
                {
                    trigger_error("SwooleFramework: hook function[$f] is not callable.");
                    continue;
                }
                $f();
            }
        }
    }

    /**
     * 清理
     */
    function __clean()
    {
        $this->env['runtime'] = array();
        $this->callHook(self::HOOK_CLEAN);
    }
    /**
     * 加载一个模块，并返回
     * @param $lib
     * @return object $lib
     */
    function load($lib)
    {
    	$this->$lib = $this->load->loadLib($lib);
    	return $this->$lib;
    }

    /**
     * 增加钩子函数
     * @param $type
     * @param $func
     */
    function addHook($type, $func)
    {
        $this->hooks[$type][] = $func;
    }

    /**
     * 自动导入模块
     * @return None
     */
    function autoload()
    {
        //$this->autoload_libs = array_flip(func_get_args());
        //历史遗留
    }

    function __get($lib_name)
    {
    	if(isset(self::$autoload_libs[$lib_name]) and empty($this->$lib_name))
    	{
    		$this->$lib_name = $this->load->loadLib($lib_name);
    	}
    	return $this->$lib_name;
    }

    /**
     * 设置路由器
     * @param $function
     */
    function router($function)
    {
        $this->router_function = $function;
    }

    function urlRoute()
    {
        if (empty($this->hooks[self::HOOK_ROUTE]))
        {
            echo Swoole\Error::info('MVC Error!',"UrlRouter hook is empty");
            return false;
        }
        $uri = parse_url($_SERVER['REQUEST_URI']);
        $mvc = array();

        //URL Router
        foreach($this->hooks[self::HOOK_ROUTE] as $hook)
        {
            if(!is_callable($hook))
            {
                trigger_error("SwooleFramework: hook function[$hook] is not callable.");
                continue;
            }
            $mvc = $hook($uri);
            //命中
            if($mvc !== false)
            {
                break;
            }
        }
        return $mvc;
    }

    function handlerServer(Swoole\Request $request)
    {
        $response = new Swoole\Response();
        $request->setGlobal();

        //处理静态请求
        if (!empty($this->server->config['apps']['do_static']) and $this->server->doStaticRequest($request, $response))
        {
            return $response;
        }

        $php = Swoole::getInstance();

        //将对象赋值到控制器
        $php->request = $request;
        $php->response = $response;

        try
        {
            ob_start();
            /*---------------------处理MVC----------------------*/
            $response->body = $php->runMVC();
            $response->body .= ob_get_contents();
            ob_end_clean();
        }
        catch(\Exception $e)
        {
            if ($request->finish != 1) $this->server->httpError(404, $response, $e->getMessage());
        }
        //重定向
        if (isset($response->head['Location']))
        {
            $response->send_http_status(301);
        }
        return $response;
    }

    /**
     * 运行MVC处理模型
     * @param $url_processor
     * @return None
     */
    function runMVC()
    {
        $mvc = call_user_func($this->router_function);
        if ($mvc === false)
        {
            $this->http->status(404);
            return Swoole\Error::info('MVC Error', "url route fail!");
        }
        //check controller name
        if (!preg_match('/^[a-z0-9_]+$/i', $mvc['controller']))
        {
        	return Swoole\Error::info('MVC Error!',"controller[{$mvc['controller']}] name incorrect.Regx: /^[a-z0-9_]+$/i");
        }
        //check view name
        if (!preg_match('/^[a-z0-9_]+$/i',$mvc['view']))
        {
        	return Swoole\Error::info('MVC Error!',"view[{$mvc['view']}] name incorrect.Regx: /^[a-z0-9_]+$/i");
        }
        //check app name
        if (isset($mvc['app']) and !preg_match('/^[a-z0-9_]+$/i',$mvc['app']))
        {
        	return Swoole\Error::info('MVC Error!',"app[{$mvc['app']}] name incorrect.Regx: /^[a-z0-9_]+$/i");
        }
		$this->env['mvc'] = $mvc;

        //未使用命名空间
        $controller_class_no_namespace = $mvc['controller'];
        $controller_path = self::$app_path."/controllers/{$mvc['controller']}.php";
        if (class_exists($controller_class_no_namespace, false))
        {
            $controller_class = $controller_class_no_namespace;
            goto do_action;
        }
        else
        {
            if (is_file($controller_path))
            {
                require_once $controller_path;
                $controller_class = $controller_class_no_namespace;
                goto do_action;
            }
        }

        //使用命名空间，文件名必须大写
        $controller_class_use_namespace = '\\App\\Controller\\'.ucwords($mvc['controller']);
        $controller_path = self::$app_path."/controllers/".ucwords($mvc['controller']).".php";
        if (class_exists($controller_class_use_namespace, false))
        {
            $controller_class = $controller_class_use_namespace;
            goto do_action;
        }
        else
        {
            if (is_file($controller_path))
            {
                require_once $controller_path;
                $controller_class = $controller_class_use_namespace;
                goto do_action;
            }
        }

        //file not found
        $this->http->status(404);
        return Swoole\Error::info('MVC Error', "Controller <b>{$mvc['controller']}</b>[{$controller_path}] not exist!");

        do_action:

        //服务器模式下，尝试重载入代码
        if (defined('SWOOLE_SERVER'))
        {
            $this->reloadController($mvc, $controller_path);
        }
        $controller = new $controller_class($this);
        if (!is_callable(array($controller, $mvc['view'])))
        {
            $this->http->status(404);
            return Swoole\Error::info('MVC Error!'.$mvc['view'],"View <b>{$mvc['controller']}->{$mvc['view']}</b> Not Found!");
        }
        if(empty($mvc['param'])) $param = null;
        else $param = $mvc['param'];

        $method = $mvc['view'];

        //设置头
        if ($controller->is_ajax)
        {
            $this->http->header('Cache-Control', 'no-cache, must-revalidate');
            $this->http->header('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
            $this->http->header('Content-Type', 'application/json');
        }

        //doAction
        $return = $controller->$method($param);

        //保存Session
        if (defined('SWOOLE_SERVER') and $this->session->open and $this->session->readonly === false)
        {
            $this->session->save();
        }

        //响应请求
        if ($controller->is_ajax)
        {
            $return = json_encode($return);
        }
        if (defined('SWOOLE_SERVER')) return $return;
        else echo $return;
    }

    function reloadController($mvc, $controller_file)
    {
        if (extension_loaded('runkit') and $this->server->config['apps']['auto_reload'])
        {
            clearstatcache();
            $fstat = stat($controller_file);
            //修改时间大于加载时的时间
            if($fstat['mtime'] > $this->env['controllers'][$mvc['controller']]['time'])
            {
                runkit_import($controller_file, RUNKIT_IMPORT_CLASS_METHODS|RUNKIT_IMPORT_OVERRIDE);
                $this->env['controllers'][$mvc['controller']]['time'] = time();
            }
        }
    }

    function runAjax()
    {
        if(!preg_match('/^[a-z0-9_]+$/i',$_GET['method'])) return false;
        $method = 'ajax_'.$_GET['method'];

        if(!function_exists($method))
        {
            echo 'Error: Function not found!';
            exit;
        }
        header('Cache-Control: no-cache, must-revalidate');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Content-type: application/json');

        $data = call_user_func($method);
        if(DBCHARSET!='utf8')
        {
            $data = Swoole_tools::array_iconv(DBCHARSET , 'utf-8' , $data);
        }
        echo json_encode($data);
    }

    function runView($pagecache=false)
    {
        if($pagecache)
        {
            //echo '启用缓存';
            $cache = new Swoole_pageCache(3600);
            if($cache->isCached())
            {
                //echo '调用缓存';
                $cache->load();
            }
            else
            {
                //echo '没有缓存，正在建立缓存';
                $view = isset($_GET['view'])?$_GET['view']:'index';
                if(!preg_match('/^[a-z0-9_]+$/i',$view)) return false;
                foreach($_GET as $key=>$param)
                $this->tpl->assign($key,$param);
                $cache->create($this->tpl->fetch($view.'.html'));
                $this->tpl->display($view.'.html');
            }
        }
        else
        {
            //echo '不启用缓存';
            $view = isset($_GET['view'])?$_GET['view']:'index';
            foreach($_GET as $key=>$param)
            $this->tpl->assign($key,$param);
            $this->tpl->display($view.'.html');
        }
    }
}

function swoole_urlrouter_rewrite(&$uri)
{
    $rewrite = Swoole::$php->config['rewrite'];
    if(empty($rewrite) or !is_array($rewrite)) return false;
    $match = array();
    foreach($rewrite as $rule)
    {
        if(preg_match('#'.$rule['regx'].'#', $uri['path'], $match))
        {
            //合并到GET中
            if(isset($rule['get']))
            {
                $p = explode(',', $rule['get']);
                foreach($p as $k=>$v)
                {
                    $_GET[$v] = $match[$k+1];
                }
            }
            return $rule['mvc'];
        }
    }
    return false;
}

function swoole_urlrouter_mvc(&$uri)
{
    $array = array('controller'=>'page', 'view'=>'index');
    if(!empty($_GET["c"])) $array['controller'] = $_GET["c"];
    if(!empty($_GET["v"])) $array['view'] = $_GET["v"];

    if(empty($uri['path']) or $uri['path']=='/' or substr($uri['path'], -9) == 'index.php')
    {
        return $array;
    }
    $request = explode('/', trim($uri['path'], '/'), 3);
    if(count($request) < 2)
    {
        return $array;
    }
    $array['controller']=$request[0];
    $array['view']=$request[1];
    if(isset($request[2]))
    {
        $request[2] = trim($request[2], '/');
        $_id = str_replace('.html', '', $request[2]);
        if(is_numeric($_id))
        {
            $_GET['id'] = $_id;
        }
        else
        {
            Swoole\Tool::$url_key_join = '-';
            Swoole\Tool::$url_param_join = '-';
            Swoole\Tool::$url_add_end = '.html';
            Swoole\Tool::$url_prefix = WEBROOT."/{$request[0]}/$request[1]/";
            Swoole\Tool::url_parse_into($request[2], $_GET);
        }
    }
    return $array;
}
