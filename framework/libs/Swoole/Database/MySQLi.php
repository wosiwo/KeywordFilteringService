<?php
namespace Swoole\Database;
use Swoole;
/**
 * MySQL数据库封装类
 *
 * @package SwooleExtend
 * @author  Tianfeng.Han
 *
 */
class MySQLi extends \mysqli implements Swoole\IDatabase
{
    const DEFAULT_PORT = 3306;

    public $debug = false;
    public $conn = null;
    public $config;

    function __construct($db_config)
    {
        if (empty($db_config['port']))
        {
            $db_config['port'] = self::DEFAULT_PORT;
        }
        $this->config = $db_config;
    }

    function lastInsertId()
    {
        return $this->insert_id;
    }

    /**
     * 参数为了兼容parent类，代码不会使用传入的参数作为配置
     * @param null $_host
     * @param null $user
     * @param null $password
     * @param null $database
     * @param null $port
     * @param null $socket
     * @return bool
     */
    function connect($_host = null, $user = null, $password = null, $database = null, $port = null, $socket = null)
    {
        $db_config = &$this->config;
        $host = $db_config['host'];
        if (!empty($db_config['persistent']))
        {
            $host = 'p:' . $host;
        }
        if (isset($db_config['passwd']))
        {
            $db_config['password'] = $db_config['passwd'];
        }
        if (isset($db_config['dbname']))
        {
            $db_config['name'] = $db_config['dbname'];
        }
        elseif (isset($db_config['database']))
        {
            $db_config['name'] = $db_config['database'];
        }
        parent::connect(
            $host,
            $db_config['user'],
            $db_config['password'],
            $db_config['name'],
            $db_config['port']
        );
        if ($this->connect_errno)
        {
            trigger_error("mysqli connect to server[$host:{$db_config['port']}] failed: " . mysqli_connect_error(), E_USER_WARNING);
            return false;
        }
        if (!empty($db_config['charset']))
        {
            $this->set_charset($db_config['charset']);
        }
        return true;
    }

    /**
     * 过滤特殊字符
     * @param $value
     * @return string
     */
    function quote($value)
    {
        return $this->tryReconnect(array($this, 'escape_string'), array($value));
    }

    /**
     * SQL错误信息
     * @param $sql
     * @return string
     */
    protected function errorMessage($sql)
    {
        $msg = $this->error . "<hr />$sql<hr />\n";
        $msg .= "Server: {$this->config['host']}:{$this->config['port']}. <br/>\n";
        if ($this->connect_errno)
        {
            $msg .= "ConnectError[{$this->connect_errno}]: {$this->connect_error}<br/>\n";
        }
        $msg .= "Message: {$this->error} <br/>\n";
        $msg .= "Errno: {$this->errno}\n";
        return $msg;
    }

    protected function tryReconnect($call, $params)
    {
        $result = false;
        for ($i = 0; $i < 2; $i++)
        {
            $result = @call_user_func_array($call, $params);
            if ($result === false)
            {
                if ($this->errno == 2013 or $this->errno == 2006)
                {
                    $r = $this->checkConnection();
                    if ($r === true)
                    {
                        continue;
                    }
                }
                else
                {
                    Swoole\Error::info(__CLASS__ . " SQL Error", $this->errorMessage($params[0]));
                    return false;
                }
            }
            break;
        }
        return $result;
    }

    /**
     * 执行一个SQL语句
     * @param string $sql 执行的SQL语句
     * @return MySQLiRecord | false
     */
    function query($sql)
    {
        $result = $this->tryReconnect(array('parent', 'query'), array($sql));
        if (!$result)
        {
            trigger_error(__CLASS__." SQL Error:". $this->errorMessage($sql), E_USER_WARNING);
            return false;
        }
        if (is_bool($result))
        {
            return $result;
        }
        return new MySQLiRecord($result);
    }

    /**
     * 执行多个SQL语句
     * @param string $sql 执行的SQL语句
     * @return MySQLiRecord | false
     */
    function multi_query($sql)
    {
        $result = $this->tryReconnect(array('parent', 'multi_query'), array($sql));
        if (!$result) {
            Swoole\Error::info(__CLASS__ . " SQL Error", $this->errorMessage($sql));
            return false;
        }

        $result = call_user_func_array(array('parent', 'use_result'), array());
        $output = array();
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }
        $result->free();

        while (call_user_func_array(array('parent', 'more_results'), array()) && call_user_func_array(array('parent', 'next_result'), array())) {
            $extraResult = call_user_func_array(array('parent', 'use_result'), array());
            if ($extraResult instanceof \mysqli_result) {
                $extraResult->free();
            }
        }
        return $output;
    }

    /**
     * 异步SQL
     * @param $sql
     * @return bool|\mysqli_result
     */
    function queryAsync($sql)
    {
        $result = $this->tryReconnect(array('parent', 'query'), array($sql, MYSQLI_ASYNC));
        if (!$result)
        {
            Swoole\Error::info(__CLASS__." SQL Error", $this->errorMessage($sql));
            return false;
        }
        return $result;
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     */
    protected function checkConnection()
    {
        if (!@$this->ping())
        {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * 获取错误码
     * @return int
     */
    function errno()
    {
        return $this->errno;
    }

    /**
     * 获取受影响的行数
     * @return int
     */
    function getAffectedRows()
    {
        return $this->affected_rows;
    }

    /**
     * 返回上一个Insert语句的自增主键ID
     * @return int
     */
    function Insert_ID()
    {
        return $this->insert_id;
    }
}

class MySQLiRecord implements Swoole\IDbRecord
{
    /**
     * @var \mysqli_result
     */
    public $result;

    function __construct($result)
    {
        $this->result = $result;
    }

    function fetch()
    {
        return $this->result->fetch_assoc();
    }

    function fetchall()
    {
        $data = array();
        while ($record = $this->result->fetch_assoc())
        {
            $data[] = $record;
        }
        return $data;
    }

    function free()
    {
        $this->result->free_result();
    }

    function __get($key)
    {
        return $this->result->$key;
    }

    function __call($func, $params)
    {
        return call_user_func_array(array($this->result, $func), $params);
    }
}
