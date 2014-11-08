<?php
namespace Swoole;
/**
 * 过滤类
 * 用于过滤过外部输入的数据，过滤数组或者变量中的不安全字符，以及HTML标签
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage request_filter
 */
class Filter
{
    static $error_url;
    static $magic_quotes_gpc;
    public $mode;

    function __construct($mode='deny',$error_url=false)
    {
        $this->mode = $mode;
        self::$error_url = $error_url;
    }
    function post($param)
    {
        $this->_check($_POST,$param);
    }
    function get($param)
    {
        $this->_check($_GET,$param);
    }
    function cookie($param)
    {
        $this->_check($_COOKIE,$param);
    }
    /**
     * 根据提供的参数对数据进行检查
     * @param $data
     * @param $param
     * @return unknown_type
     */
    function _check(&$data,$param)
    {
        foreach($param as $k=>$p)
        {
            if(!isset($data[$k]))
            {
                if(isset($p['require']) and $p['require']) self::raise('param require');
                else continue;
            }

            if(isset($p['type']))
            {
                $data[$k] = Validate::$p['type']($data[$k]);
                if($data[$k]===false) self::raise();

                //最小值参数
                if(isset($p['min']) and is_numeric($data[$k]) and $data[$k]<$p['min']) self::raise('num too small');
                //最大值参数
                if(isset($p['max']) and is_numeric($data[$k]) and $data[$k]>$p['max']) self::raise('num too big');

                //最小值参数
                if(isset($p['short']) and is_string($data[$k]) and mb_strlen($data[$k])<$p['short']) self::raise('string too short');
                //最大值参数
                if(isset($p['long']) and is_string($data[$k]) and mb_strlen($data[$k])>$p['long']) self::raise('string too long');

                //自定义的正则表达式
                if($p['type']=='regx' and isset($p['regx']) and preg_match($p['regx'],$data[$k])===false) self::raise();
            }
        }
        //如果为拒绝模式，所有不在过滤参数$param中的键值都将被删除
        if($this->mode=='deny')
        {
            $allow = array_keys($param);
            $have = array_keys($data);
            foreach($have as $ha) if(!in_array($ha,$allow)) unset($data[$ha]);
        }
    }
    static function raise($text=false)
    {
        if(self::$error_url) Swoole_client::redirect(self::$error_url);
        if($text) exit($text);
        else exit('Client input param error!');
    }
    /**
     * 过滤$_GET $_POST $_REQUEST $_COOKIE
     * @return unknown_type
     */
    static function request()
    {
        $_POST = Filter::filter_array($_POST);
        $_GET = Filter::filter_array($_GET);
        $_REQUEST = Filter::filter_array($_REQUEST);
        $_COOKIE = Filter::filter_array($_COOKIE);
    }
    static function safe(&$content)
    {
        $content = stripslashes($content);
        $content = html_entity_decode($content, ENT_QUOTES, \Swoole::$charset);
    }
    public static function filter_var($var,$type)
    {
        switch($type)
        {
            case 'int':
                return intval($var);
            case 'string':
                return htmlspecialchars(strval($var),ENT_QUOTES);
            case 'float':
                return floatval($var);
            default:
                return false;
        }
    }
    /**
     * 过滤数组
     * @param $array
     * @return unknown_type
     */
    public static function filter_array($array)
    {
        if(!is_array($array))
        {
            return false;
        }
        $clean = array();
        foreach($array as $key=>$string)
        {
            if(is_array($string))
            {
                self::filter_array($string);
            }
            else
            {
                if(self::$magic_quotes_gpc and DBCHARSET=='gbk')
                {
                    $string = stripslashes($string);
                }
                else
                {
                    $string = self::escape($string);
                    $key = self::escape($key);
                }
            }
            $clean[$key] = $string;
        }
        return $clean;
    }
    /**
     * 使输入的代码安全
     * @param $string
     * @return unknown_type
     */
    public static function escape($string)
    {
        if(is_numeric($string)) return $string;
        $string = htmlspecialchars($string,ENT_QUOTES,\Swoole::$charset);

        if(\Swoole::$charset=='gbk') self::gbk_addslash($string);
        else self::addslash($string);
        return $string;
    }
    /**
     * 移除HTML中的危险代码，如iframe和script
     * @param $val
     * @return unknown_type
     */
    public static function remove_xss($content,$allow='')
    {
        $danger = 'javascript,vbscript,expression,applet,meta,xml,blink,link,style,script,embed,object,iframe,frame,frameset,ilayer,layer,bgsound,title,base';
        $event = 'onabort|onactivate|onafterprint|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|'.
        'onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onblur|onbounce|oncellchange|onchange|onclick|oncontextmenu|oncontrolselect|'.
        'oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondblclick|ondeactivate|ondrag|ondragend|ondragenter|ondragleave|'.
        'ondragover|ondragstart|ondrop|onerror|onerrorupdate|onfilterchange|onfinish|onfocus|onfocusin|onfocusout|onhelp|onkeydown|onkeypress|'.
        'onkeyup|onlayoutcomplete|onload|onlosecapture|onmousedown|onmouseenter|onmouseleave|onmousemove|onmouseout|onmouseover|onmouseup|'.
        'onmousewheel|onmove|onmoveend|onmovestart|onpaste|onpropertychange|onreadystatechange|onreset|onresize|onresizeend|onresizestart|'.
        'onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onselect|onselectionchange|onselectstart|onstart|onstop|onsubmit|onunload';

        if(!empty($allow))
        {
            $allows = explode(',',$allow);
            $danger = str_replace($allow,'',$danger);
        }
        $danger = str_replace(',','|',$danger);
        //替换所有危险标签
        $content = preg_replace("/<\s*($danger)[^>]*>[^<]*(<\s*\/\s*\\1\s*>)?/is",'',$content);
        //替换所有危险的JS事件
        $content = preg_replace("/<([^>]*)($event)\s*\=([^>]*)>/is","<\\1 \\3>",$content);
        return $content;
    }
    /**
     * 过滤危险字符
     * @param $string
     * @return unknown_type
     */
    public static function addslash(&$string)
    {
        $string = addslashes($string);
    }
    /**
     * 过滤危险字符，解决GBK漏洞
     * @param $string
     * @return unknown_type
     */
    public static function gbk_addslash(&$string)
    {
        while(true)
        {
            $i = mb_strpos($text, chr(92),0,"GBK");
            if ($i === false) break;
            $T = mb_substr($text, 0, $i, "GBK") . chr(92) . chr(92);
            $text = substr($text, strlen($T) - 1);
            $OK .= $T;
        }
        $text = $OK . $text;
        $text = str_replace(chr(39), chr(92) . chr(39), $text);
        $text = str_replace(chr(34), chr(92) . chr(34), $text);
        $string = $text;
    }
    /**
     * 移除反斜杠过滤
     * @param $string
     * @return unknown_type
     */
    public static function deslash(&$string)
    {
        $string = stripslashes($string);
    }
}

Filter::$magic_quotes_gpc = get_magic_quotes_gpc();