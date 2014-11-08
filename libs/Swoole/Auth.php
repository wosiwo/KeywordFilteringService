<?php
namespace Swoole;

/**
 * 用户验证类
 * @author Han Tianfeng
 * @package SwooleSystem
 * @subpackage Login
 */
class Auth
{
    public $table = '';
    public $select = '*';
    public $db = '';
    public $user;
    public $is_login = true;
    public $dict;

    static $login_url = '/login.php?';
    static $username = 'username';
    static $password = 'password';
    static $lastlogin = 'lastlogin';
    static $lastip = 'lastip';
    static $session_prefix = '';
    static $mk_password = 'username,password';
    static $password_hash = 'sha1';
    static $cookie_life = 2592000;
    static $session_destroy = false;

    function __construct($db,$table='')
    {
        if($table=='') $this->table = TABLE_PREFIX.'_user';
        else $this->table = $table;
        $this->db = $db;
        $_SESSION[self::$session_prefix.'save_key'] = array();
    }
    function saveUserinfo($key='userinfo')
    {
        $_SESSION[self::$session_prefix.$key] = $this->user;
        $_SESSION[self::$session_prefix.'save_key'][] = self::$session_prefix.$key;
    }
    /**
     * 更新用户信息
     * @param $set
     * @return unknown_type
     */
    function updateStatus($set=null)
    {
        if(empty($set)) $set = array(self::$lastlogin=>date('Y-m-d H:i:s'),self::$lastip=>Swoole_client::getIP());
        $this->db->update($this->user['id'],$set,$this->table);
    }
    function setSession($key)
    {
        $_SESSION[$key] = $this->user[$key];
        $_SESSION[self::$session_prefix.'save_key'][] = self::$session_prefix.$key;
    }
    /**
     * 加载用户数组字典（用户注册表）
     * @return unknown_type
     */
    function loadDict($table)
    {
        if(!is_object($this->dict))
        {
            global $php;
            $dbc = new Swoole\Cache\DBCache($table);
            $dbc->shard_id = $this->getUid();
            $this->dict = $dbc;
        }
    }
    /**
     * 获取一个数据列表
     * @param $keys
     * @return unknown_type
     */
    function gets($keys)
    {
        return $this->dict->gets($keys);
    }
    /**
     * 获取一个值
     * @param $key
     * @return unknown_type
     */
    function get($key)
    {
        return $this->dict->get($key);
    }
    /**
     * 获取登录用户的UID
     * @return unknown_type
     */
    static function getUid()
    {
        return $_SESSION[self::$session_prefix.'user_id'];
    }
    /**
     * 获取登录用户的信息
     * @return unknown_type
     */
    static function getUinfo($key='userinfo')
    {
        return $_SESSION[self::$session_prefix.$key];
    }
    /**
     * 登录
     * @param $username
     * @param $password
     * @param $auto
     * @param $save 保存用户登录信息
     * @return unknown_type
     */
    function login($username,$password,$auto,$save=false)
    {
        Cookie::set(self::$session_prefix.'username',$username,time() + self::$cookie_life,'/');
        $this->user = $this->db->query('select '.$this->select.' from '.$this->table." where ".self::$username."='$username'")->fetch();
        if(empty($this->user)) return false;
        elseif($this->user[self::$password]==$password)
        {
            $_SESSION[self::$session_prefix.'isLogin']=true;
            $_SESSION[self::$session_prefix.'user_id']=$this->user['id'];
            if($auto==1) $this->autoLogin();
            return true;
        }
    }
    /**
     * 检查是否登录
     * @return unknown_type
     */
    function isLogin()
    {
        if(isset($_SESSION[self::$session_prefix.'isLogin']) and $_SESSION[self::$session_prefix.'isLogin']==1) return true;
        elseif(isset($_COOKIE[self::$session_prefix.'autologin']) and isset($_COOKIE[self::$session_prefix.'username']) and isset($_COOKIE[self::$session_prefix.'password']))
        {
            return $this->login($_COOKIE[self::$session_prefix.'username'],$_COOKIE[self::$session_prefix.'password'],$auto=1);
        }
        return false;
    }
    /**
     * 自动登录，如果自动登录则在本地记住密码
     * @param $user
     * @return unknown_type
     */
    function autoLogin()
    {
        Cookie::set(self::$session_prefix.'autologin',1,time() + self::$cookie_life,'/');
        Cookie::set(self::$session_prefix.'username',$this->user['username'],time() + self::$cookie_life,'/');
        Cookie::set(self::$session_prefix.'password',$this->user['password'],time() + self::$cookie_life,'/');
    }
    /**
     * 注销登录
     * @return unknown_type
     */
    static function logout()
    {
        /**
         * 如果设置为true，退出登录时，销毁所有Session
         */
        if (self::$session_destroy)
        {
            $_SESSION = array();
            return true;
        }
        unset($_SESSION[self::$session_prefix.'isLogin']);
        unset($_SESSION[self::$session_prefix.'user_id']);

        if(!empty($_SESSION[self::$session_prefix.'save_key'])) foreach($_SESSION[self::$session_prefix.'save_key'] as $sk) unset($_SESSION[$sk]);
        unset($_SESSION[self::$session_prefix.'save_key']);
        if(isset($_COOKIE[self::$session_prefix.'password'])) Cookie::set(self::$session_prefix.'password','',0,'/');

    }
    /**
     * 产生一个密码串，连接用户名和密码，并使用sha1产生散列
     * @param $username
     * @param $password
     * @return $password_string 密码的散列
     */
    public static function mkpasswd($username,$password)
    {
        //sha1 用户名+密码
        if(self::$password_hash=='sha1') return sha1($username.$password);
        //md5 用户名+密码
        elseif(self::$password_hash=='md5') return md5($username.$password);
        elseif(self::$password_hash=='sha1_single') return sha1($password);
        elseif(self::$password_hash=='md5_single') return md5($password);
    }
    /**
     * 验证登录
     * @return unknown_type
     */
    public static function login_require()
    {
        $check = false;
        if(isset($_SESSION[self::$session_prefix.'isLogin']) and $_SESSION[self::$session_prefix.'isLogin']=='1') $check=true;
        if(!$check)
        {
            \Swoole::$php->http->redirect(self::$login_url.'refer='.urlencode($_SERVER["REQUEST_URI"]));
            return false;
        }
        return true;
    }
}

