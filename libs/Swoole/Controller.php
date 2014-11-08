<?php
namespace Swoole;
/**
 * Controller的基类，控制器基类
 * @package Swoole\System
 * @subpackage MVC
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
class Controller
{
    public $swoole;
    public $is_ajax = false;
    public $if_filter = true;

    protected $tpl_var = array();
    protected $template_dir;
    protected $trace = array();
    protected $model;
    protected $config;

    function __construct(\Swoole $swoole)
    {
        $this->swoole = $swoole;
        $this->model = $swoole->model;
        $this->config = $swoole->config;
        $this->template_dir = \Swoole::$app_path.'/templates/';
        if (!defined('TPL_PATH'))
        {
            define('TPL_PATH', $this->template_dir);
        }
        if ($this->if_filter) Filter::request();
        $swoole->__init();
    }

    /**
     * 跟踪信息
     * @param $title
     * @param $value
     */
    protected function trace($title,$value='')
    {
        if(is_array($title))
        {
            $this->trace = array_merge($this->trace,$title);
        }
        else
        {
            $this->trace[$title] = $value;
        }
    }
    function fetch($tpl_file ='')
    {
        ob_start();
        $this->display($tpl_file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    function message($code = 0, $msg = 'success')
    {
        $ret = array('code' => $code, 'msg' => $msg);
        return $this->is_ajax ? $ret : json_encode($ret);
    }

    function assign($key, $value)
    {
        $this->tpl_var[$key] = $value;
    }

    /**
     * render template file, then display it.
     * @param string $tpl_file
     */
    function display($tpl_file ='')
    {
        if (empty($tpl_file))
        {
            $tpl_file = $this->swoole->env['mvc']['controller'].'/'.$this->swoole->env['mvc']['view'].'.php';
        }
        if (!is_file($this->template_dir.$tpl_file))
        {
            Error::info('template error', "template file[".$this->template_dir.$tpl_file."] not found");
        }
        extract($this->tpl_var);
        include $this->template_dir.$tpl_file;
    }
    /**
     * 显示运行时间和内存占用
     * @return unknown_type
     */
    protected function showTime()
    {
        $runtime = $this->swoole->runtime();
        // 显示运行时间
        $showTime = '执行时间: '.$runtime['time'];
        // 显示内存占用
        $showTime.= ' | 内存占用:'.$runtime['memory'];
        return $showTime;
    }
    /**
     * 显示跟踪信息
     * @param $detail
     * @return unknown_type
     */
    public function showTrace($detail=false)
    {
        $_trace =   array();
        $included_files = get_included_files();

        // 系统默认显示信息
        $_trace['请求脚本'] = $_SERVER['SCRIPT_NAME'];
        $_trace['请求方法'] = $_SERVER['REQUEST_METHOD'];
        $_trace['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        $_trace['HTTP版本'] = $_SERVER['SERVER_PROTOCOL'];
        $_trace['请求时间'] = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);

        if(isset($_SESSION)) $_trace['SESSION_ID'] = session_id();
        $_trace['读取数据库'] = $this->swoole->db->read_times.'次';
        $_trace['写入数据库'] = $this->swoole->db->write_times.'次';

        $_trace['加载文件数目'] = count($included_files);
        $_trace['PHP执行占用'] = $this->showTime();
        $_trace = array_merge($this->trace,$_trace);

        // 调用Trace页面模板
        $html = <<<HTMLS
<style type="text/css">
#swoole_trace_content  {
font-family:		Consolas, Courier New, Courier, monospace;
font-size:			14px;
background-color:	#fff;
margin:				40px;
color:				#000;
border:				#999 1px solid;
padding:			20px 20px 12px 20px;
}
</style>
	<div id="content">
		<fieldset id="querybox" style="margin:5px;">
		<div style="overflow:auto;height:300px;text-align:left;">
HTMLS;
        foreach ($_trace as $key=>$info)
        {
            $html .= $key.' : '.$info.BL;
        }
        if($detail)
        {
            //输出包含的文件
             $html .= '加载的文件'.BL;
            foreach ($included_files as $file)
            {
                 $html .= 'require '.$file.BL;
            }
        }
         $html .= "</div></fieldset></div>";
         return $html;
    }

    function __get($key)
    {
        return $this->swoole->$key;
    }

    function __destruct()
    {
        $this->swoole->__clean();
    }
}
