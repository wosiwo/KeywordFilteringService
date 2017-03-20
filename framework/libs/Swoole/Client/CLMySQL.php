<?php
/**
 * Created by PhpStorm.
 * User: yanchunhao
 * Date: 2015/12/2
 * Time: 15:44
 */

namespace Swoole\Client;

use Swoole\CLPack;

class CLMySQL {
	private static $result = array(), $result_id = 1;
	private $host, $port;
	private static $conns = array(), $conn_id = 0, $conninfo = array();

	const CONNINFO_F_dbname = 0, CONNINFO_F_conn = 1, CONNINFO_F_errno = 2, CONNINFO_F_erro_msg = 3, CONNINFO_F_insert_id = 4, CONNINFO_F_affected_rows = 5;

	/*function __construct($host, $port, $pconnect = true) {
		$this->host = $host;
		$this->port = $port;
		#$this->dbname = $dbname;
		$key = $host . ':' . $port;
		if (!isset(self::$conns[$key])) {
			self::$conns[$key] = new \swoole_client($pconnect ? (SWOOLE_SOCK_TCP | SWOOLE_KEEP) : SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC, 'clmysql');
			self::$conns[$key]->set(array(
				'open_length_check' => 1,
				'package_length_type' => 'N',
				'package_length_offset' => 0,
				//第N个字节是包长度的值
				'package_body_offset' => 8,
				//第几个字节开始计算长度
				'package_max_length' => CLPack::MAX_LEN,
				//协议最大长度
			));
		}
		$this->conn = self::$conns[$key];
		#$this->connect();
	}*/

	static function select_db($dbname, $conn_id) {
		self::$conninfo[$conn_id][self::CONNINFO_F_dbname] = $dbname;
		return true;
	}

	static function connect($host, $port, $pconnect = false) {
		$key = $host . ':' . $port;
		if (!isset(self::$conns[$key])) {
			self::$conns[$key] = new \swoole_client($pconnect ? (SWOOLE_SOCK_TCP | SWOOLE_KEEP) : SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC, 'clmysql');
			self::$conns[$key]->set(array(
				'open_length_check' => 1,
				'package_length_type' => 'N',
				'package_length_offset' => 0,
				//第N个字节是包长度的值
				'package_body_offset' => 8,
				//第几个字节开始计算长度
				'buffer_output_size' => CLPack::MAX_LEN,
				'package_max_length' => CLPack::MAX_LEN,
				//协议最大长度
			));
			if (!self::$conns[$key]->connect($host, intval($port), 60)) {
				unset(self::$conns[$key]);
			}
		}
		if (isset(self::$conns[$key])) {
			self::$conn_id++;
			self::$conninfo[self::$conn_id][self::CONNINFO_F_conn] = self::$conns[$key];
			return self::$conn_id;
		}
		return false;
	}

	static function pconnect($host, $port) {
		return self::connect($host, $port, true);
	}

	private static function getPack($sign, $conn_id) {
		while (1) {
			$data = @self::$conninfo[$conn_id][self::CONNINFO_F_conn]->recv();
			if ($data == false) {
				#throw new \Exception('连接Mysql网络中断');
				self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 2006;
				self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = 'Mysql proxy中断(接收失败)';
				return false;
			}
			$r = CLPack::unpack($data);
			if (!$r) {
				self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 2006;
				self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = 'Mysql proxy中断(解包失败:' . $data . ')';
				return false;
			}
			if ($r[0] !== $sign) {
				self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 2006;
				self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = 'Mysql proxy中断(签名验证失败:"' . $sign . '"!="' . $r[0] . '")';
				return false;
			}
			if (!is_array($r[1])) {
				self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 2006;
				self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = '返回非数组结果:' . $data;
				return false;
			}
			return $r[1];
		}
	}

	static function query($sql, $conn_id) {
		if (!isset(self::$conninfo[$conn_id])) {
			/*$this->last_errno = 2006;
			$this->last_erro_msg = 'Mysql proxy中断(无连接)';*/
			self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 1256;
			self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = '连接不存在 $conn_id=' . $conn_id;
			return false;
		}
		$is_multi = true;
		$sign = mt_rand();
		if (!is_array($sql)) {
			$is_multi = false;
			$sql = array(self::$conninfo[$conn_id][self::CONNINFO_F_dbname] => $sql);
		}
		$pack = CLPack::pack($sql, $sign);
		if (false === $pack) {
			self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 1256;
			self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = '发送的sql语句大小超过限制';
			return false;
		}
		if (false === self::$conninfo[$conn_id][self::CONNINFO_F_conn]->send($pack)) {
			self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 2006;
			self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = 'Mysql proxy中断(发送失败)';
			return false;
		}
		$r = self::getPack($sign, $conn_id);
		if ($r === false) {
			return false;
		}
		/*if (!is_array($r)) {
			self::$conninfo[$conn_id][self::CONNINFO_F_errno] = 1256;
			self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = '返回非数组结果' . print_r($r, 1);
			return false;
		}*/
		foreach ($r as $k => $v) {
			if ($v[0] != 0) {
				self::$conninfo[$conn_id][self::CONNINFO_F_errno] = $v[0];
				self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg] = $v[1];
				return false;
			} else {
				self::$conninfo[$conn_id][self::CONNINFO_F_insert_id] = isset($v[2]) ? $v[2] : 0;
				self::$conninfo[$conn_id][self::CONNINFO_F_affected_rows] = isset($v[3]) ? $v[3] : 0;
			}
		}

		self::$result_id++;
		self::$result[self::$result_id] = $r;
		return self::$result_id;
	}

	static function fetch($result_id, $dbname = '') {
		if (isset(self::$result[$result_id])) {
			if (!$dbname) {
				reset(self::$result[$result_id]);
				$dbname = key(self::$result[$result_id]);
			}
			if (self::$result[$result_id][$dbname][0] == 0) {
				return self::$result[$result_id][$dbname][1];
			}
		}
		return false;
	}

	static function fetch_row($result_id, $seek, $dbname = '') {
		if (isset(self::$result[$result_id])) {
			if (!$dbname) {
				reset(self::$result[$result_id]);
				$dbname = key(self::$result[$result_id]);
			}
			if (self::$result[$result_id][$dbname][0] == 0 && isset(self::$result[$result_id][$dbname][1][$seek])) {
				return self::$result[$result_id][$dbname][1][$seek];
			}
		}
		return false;
	}

	static function num_rows($result_id, $dbname = '') {
		if (isset(self::$result[$result_id])) {
			if (!$dbname) {
				reset(self::$result[$result_id]);
				$dbname = key(self::$result[$result_id]);
			}
			if (self::$result[$result_id][$dbname][0] == 0) {
				return count(self::$result[$result_id][$dbname][1]);
			}
		}
		return 0;
	}

	static function free_result($result_id) {
		if (isset(self::$result[$result_id])) {
			unset(self::$result[$result_id]);
		}
	}

	static function insert_id($conn_id) {
		return self::$conninfo[$conn_id][self::CONNINFO_F_insert_id];
	}

	static function affected_rows($conn_id) {
		return self::$conninfo[$conn_id][self::CONNINFO_F_affected_rows];
	}

	static function get_last_errno($conn_id) {
		if (!isset(self::$conninfo[$conn_id][self::CONNINFO_F_errno])) {
			return 0;
		}
		return self::$conninfo[$conn_id][self::CONNINFO_F_errno];
	}

	static function get_last_erro_msg($conn_id) {
		if (!isset(self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg])) {
			return "";
		}
		return self::$conninfo[$conn_id][self::CONNINFO_F_erro_msg];
	}

	static function close($conn_id) {
		unset(self::$conninfo[$conn_id]);
	}

	/*function onClose(\swoole_client $client) {
		//连接中断
		#throw new \Exception("clmysql连接被中断");
	}*/
}