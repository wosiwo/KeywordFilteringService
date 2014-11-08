<?php
namespace Swoole;
/**
 * 数据库基类
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage database
 *
 */

/**
 * Database Driver接口
 * 数据库驱动类的接口
 * @author Tianfeng.Han
 *
 */
interface IDatabase
{
	function query($sql);
	function connect();
	function close();
	function lastInsertId();
}
/**
 * Database Driver接口
 * 数据库结果集的接口，提供2种接口
 * fetch 获取单条数据
 * fetch 获取全部数据到数组
 * @author Tianfeng.Han
 */
interface IDbRecord
{
	function fetch();
	function fetchall();
}
/**
 * Database类，处理数据库连接和基本的SQL组合
 * 提供4种接口，query  insert update delete
 * @author Administrator
 *
 */
class Database
{
	public $debug = false;
	public $read_times = 0;
	public $write_times = 0;
	public $_db = null;
    /**
     * @var \Swoole\SelectDB
     */
    public $db_apt = null;

	const TYPE_MYSQL   = 1;
	const TYPE_MYSQLi  = 2;
	const TYPE_PDO     = 3;
	const TYPE_AdoDB   = 4;

	function __construct($db_config)
	{
		switch($db_config['type'])
		{
			case self::TYPE_MYSQL:
				$this->_db = new Database\MySQL($db_config);
				break;
			case self::TYPE_MYSQLi:
				$this->_db = new Database\MySQLi($db_config);
				break;
			default:
				$this->_db = new Database\PdoDB($db_config);
				break;
		}
		$this->db_apt = new \Swoole\SelectDB($this);
	}
	/**
	 * 初始化参数
	 * @return unknown_type
	 */
	function __init()
	{
		$this->check_status();
		$this->db_apt->init();
		$this->read_times = 0;
		$this->write_times = 0;
	}
	/**
	 * 检查连接状态，如果连接断开，则重新连接
	 * @return unknown_type
	 */
	function check_status()
	{
		if(!$this->_db->ping())
		{
			$this->_db->close();
			$this->_db->connect();
		}
	}
	/**
	 * 启动事务处理
	 * @return unknown_type
	 */
	function start()
	{
		$this->_db->query('START TRANSACTION');
	}
	/**
	 * 提交事务处理
	 * @return unknown_type
	 */
	function commit()
	{
		$this->_db->query('COMMIT');
	}
	/**
	 * 事务回滚
	 * @return unknown_type
	 */
	function rollback()
	{
		$this->_db->query('ROLLBACK');
	}
	/**
	 * 执行一条SQL语句
	 * @param $sql
     * @return \Swoole\Database\MySQLiRecord
	 */
	public function query($sql)
	{
		if($this->debug) echo "$sql<br />\n<hr />";
		$this->read_times +=1;
		return $this->_db->query($sql);
	}
	/**
	 * 插入$data数据库的表$table，$data必须是键值对应的，$key是数据库的字段，$value是对应的值
	 * @param $data
	 * @param $table
	 * @return unknown_type
	 */
	public function insert($data,$table)
	{
		$this->db_apt->init();
		$this->db_apt->from($table);
		$this->write_times +=1;
		return $this->db_apt->insert($data);
	}
	/**
	 * 从$table删除一条$where为$id的记录
	 * @param $id
	 * @param $table
	 * @param $where
	 * @return unknown_type
	 */
	public function delete($id,$table,$where='id')
	{
		if(func_num_args()<2) Error::info('SelectDB param error','Delete must have 2 paramers ($id,$table) !');
		$this->db_apt->init();
		$this->db_apt->from($table);
		$this->write_times +=1;
		return $this->query("delete from $table where $where='$id'");
	}
	/**
	 * 执行数据库更新操作，参数为主键ID，值$data，必须是键值对应的
	 * @param $id     主键ID
	 * @param $data   数据
	 * @param $table  表名
	 * @param $where  其他字段
	 * @return $n     SQL语句的返回值
	 */
	public function update($id,$data,$table,$where='id')
	{
		if(func_num_args()<3)
        {
            echo Error::info('SelectDB param error','Update must have 3 paramers ($id,$data,$table) !');
            return false;
        }
		$this->db_apt->init();
		$this->db_apt->from($table);
		$this->db_apt->where("$where='$id'");
		$this->write_times +=1;
		return $this->db_apt->update($data);
	}
	/**
	 * 根据主键获取单条数据
	 * @param $id
	 * @param $table
	 * @param $primary
	 * @return unknown_type
	 */
	public function get($id,$table,$primary='id')
	{
		$this->db_apt->init();
		$this->db_apt->from($table);
		$this->db_apt->where("$primary='$id'");
		return $this->db_apt->getone();
	}
	/**
	 * 调用$driver的自带方法
	 * @param $method
	 * @return unknown_type
	 */
	function __call($method, $args=array())
	{
		return call_user_func_array(array($this->_db, $method), $args);
	}
}
