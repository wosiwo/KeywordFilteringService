<?php
namespace Swoole;
/**
 * 会话控制类
 * 通过SwooleCache系统实现会话控制，可支持FileCache,DBCache,Memcache以及更多
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @package Login
 */
class Session
{
    // 类成员属性定义
    static $cache_prefix = "phpsess_";
    static $cache_life = 86400;

    public $sessID;
    public $readonly; //是否为只读，只读不需要保存
    public $open;
    protected $cache;

    /**
     * 使用PHP内建的SESSION
     * @var bool
     */
    public $use_php_session =  true;

    static $sess_size = 32;
    static $sess_name = 'SESSID';
    static $cookie_key = 'PHPSESSID';
    static $sess_domain;

    /**
     * 构造函数
     * @param $cache \Swoole\Cache
     * @return NULL
     */
    public function __construct($cache = null)
    {
        $this->cache = $cache;
    }

    public function start($readonly = false)
    {
        if ($this->use_php_session)
        {
            session_start();
        }
        else
        {
            $this->readonly = $readonly;
            $this->open = true;
            $sessid = Cookie::get(self::$cookie_key);
            if(empty($sessid))
            {
                $sessid = RandomKey::randmd5(40);
                Cookie::set(self::$cookie_key, $sessid, self::$cache_life);
            }
            $_SESSION = $this->load($sessid);
        }
    }

    public function load($sessId)
    {
        $this->sessID = $sessId;
        $data = $this->get($sessId);
        if($data) return unserialize($data);
        else return array();
    }

    public function save()
    {
        return $this->set($this->sessID, serialize($_SESSION));
    }
    /**
     * 打开Session
     * @param   String  $pSavePath
     * @param   String  $pSessName
     * @return  Bool    TRUE/FALSE
     */
    public function open($save_path='',$sess_name='')
    {
        self::$cache_prefix = $save_path.'_'.$sess_name;
        return true;
    }
    /**
     * 关闭Session
     * @param   NULL
     * @return  Bool    TRUE/FALSE
     */
    public function close()
    {
        return true;
    }
    /**
     * 读取Session
     * @param   String  $sessId
     * @return  Bool    TRUE/FALSE
     */
    public function get($sessId)
    {
        $session = $this->cache->get(self::$cache_prefix.$sessId);
        //先读数据，如果没有，就初始化一个
        if(!empty($session)) return $session;
        else return array();
    }
    /**
     * 设置Session的值
     * @param   String  $wSessId
     * @param   String  $wData
     * @return  Bool    true/FALSE
     */
    public function set($sessId, $session='')
    {
        $key = self::$cache_prefix.$sessId;
        $ret = $this->cache->set($key, $session, self::$cache_life);
        return $ret;
    }
    /**
     * 销毁Session
     * @param   String  $wSessId
     * @return  Bool    true/FALSE
     */
    public function delete($sessId = '')
    {
        return $this->cache->delete(self::$cache_prefix.$sessId);
    }
    /**
     * 内存回收
     * @param   NULL
     * @return  Bool    true/FALSE
     */
    public function gc()
    {
        return true;
    }
    /**
     * 初始化Session，配置Session
     * @param   NULL
     * @return  Bool  true/FALSE
     */
    function initSess()
    {
        //不使用 GET/POST 变量方式
        ini_set('session.use_trans_sid',0);
        //设置垃圾回收最大生存时间
        ini_set('session.gc_maxlifetime',self::$cache_life);
        //使用 COOKIE 保存 SESSION ID 的方式
        ini_set('session.use_cookies',1);
        ini_set('session.cookie_path','/');
        //多主机共享保存 SESSION ID 的 COOKIE
        ini_set('session.cookie_domain', self::$sess_domain);
        //将 session.save_handler 设置为 user，而不是默认的 files
        session_module_name('user');
        //定义 SESSION 各项操作所对应的方法名
        session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'get'),
                array($this, 'set'),
                array($this, 'delete'),
                array($this, 'gc'));
        session_start();
        return true;
    }
}
