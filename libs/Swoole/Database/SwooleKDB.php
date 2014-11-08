<?php
/**
 * Swoole Ket Database系统，用户读多，写少的应用
 * 基于Cache和Database，提供了set,get,delete3个接口
 * @package Swoole
 * @subpackage database
 * @author Tianfeng.Han
 * @link http://www.siaocms.com/
 */

/**
 * Swoole Key Database，Swoole键值数据库
 * 基于Cache和DB，实现了键值对应的自动缓存数据库，可用户，大量读，少量写的程序部分
 * @author Tianfeng.Han
 *
 */
class SwooleKDB
{
    public $_data = array();
    public $cache;
    public $db_prefix;
    public $roots;
    public $default_expires = 600;

    public $db;
    public $table_name = 'swoolekdb';
    /**
     * 构造函数，参数为数据库对象，cache的url
     * @param $db
     * @param $cache_url
     * @return unknown_type
     */
    function __construct($db,$cache_url)
    {
        $this->db = $db;
        $this->cache = new Cache($cache_url);
    }
    /**
     * 设置缓存的值，并写入到数据库，如果复制与当前缓存中的值相同，则不写入数据库
     * 如果数据库中没有此条记录，则insert，如果有则update
     * @param $key
     * @param $value
     * @param $expires
     * @return None
     */
    function set($key,$value,$expires=0)
    {
        if($expires===0) $expires=$this->default_expires;
        if($value===$this->cache->get($key)) return false;
        $this->cache->set($key,$value,$expires);
        $keys = explode('_',$key,2);
        $root = $keys[0];
        if(strpos($this->roots,$root)!==false)
            $table = $this->db_prefix.'_'.$this->table_name.'_'.$root;
        else
            return true;
        $db_key = $keys[1];
        $count = $this->db->query("select count(keyname) as cc from {$table} where keyname='{$db_key}'")->fetch();
        if($count['cc']!=0)
            $this->db->query("update {$table} set keyvalue='{$value}' where keyname='{$db_key}'");
        else
            $this->db->query("insert into {$table} (keyname,keyvalue) values('{$db_key}','{$value}')");
        return true;
    }
    /**
     * 获取键值，如果缓存中存在则直接输出，如果不存在，进入数据库查询
     * @param $key
     * @return $value
     */
    function get($key)
    {
        $value = $this->cache->get($key);
        if(empty($value))
        {
            $keys = explode('_',$key,2);
            $root = $keys[0];
            if(strpos($this->roots,$root)!==false)
                $table = $this->db_prefix.'_'.$this->table_name.'_'.$root;
            else
                Error::info('SwooleKDB Error!','Key "'.$root.'" not in kdb_roots!');
            $db_key = $keys[1];
            $res = $this->db->query("select  keyvalue from {$table} where keyname='{$db_key}'")->fetch();
            if(empty($res)) return false;
            $this->cache->set($key,$res['keyvalue'],$this->default_expires);
            return $res['keyvalue'];
        }
        return $value;
    }
    /**
     * 删除键值，并删除数据中的记录
     * @param $key
     * @return true
     */
    function delete($key)
    {
         $keys = explode('_',$key,2);
            $root = $keys[0];
            if(strpos($this->roots,$root)!==false)
                $table = $this->db_prefix.'_'.$this->table_name.'_'.$root;
            else
                Error::info('SwooleKDB Error!','Key "'.$root.'" not in kdb_roots!');
            $db_key = $keys[1];
            $this->cache->delete($key);
            $this->db->delete($db_key,$table,'keyname');
            return true;
    }
    
    private function getTable()
    {
        
    }

    function createTable($rootname,$db_prefix,$engine,$charset)
    {
        $sql = <<<HTML
     CREATE TABLE `{$db_prefix}_{$this->table_name}_{$rootname}` (
      `keyname` varchar(64) NOT NULL,
      `keyvalue` mediumtext NOT NULL,
      `keytype` varchar(48) NOT NULL,
      PRIMARY KEY  (`keyname`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset};
HTML;
        $this->db->query($sql);
    }
}
?>