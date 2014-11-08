<?php
namespace Swoole\Database;

/**
 * MySQL数据库封装类
 * @package SwooleExtend
 * @author Tianfeng.Han
 *
 */
class MySQLi extends \mysqli implements \Swoole\IDatabase
{
    public $debug = false;
    public $conn = null;
    public $config;

    function __construct($db_config)
    {
        $this->config = $db_config;
    }

    function lastInsertId()
    {
        return $this->insert_id;
    }

    function connect($host = NULL, $user = NULL, $password = NULL, $database = NULL, $port = NULL, $socket = NULL)
    {
        $db_config = &$this->config;
        parent::connect($db_config['host'], $db_config['user'], $db_config['passwd'], $db_config['name']);
        if (mysqli_connect_errno())
        {
            trigger_error("Mysqli connect failed: %s\n".mysqli_connect_error());
            return false;
        }
        if (!empty($db_config['charset']))
        {
			$this->set_charset($db_config['charset']);
		}        
        return true;
    }
    function quote($value)
    {
        return $this->escape_string($value);
    }
    /**
     * 执行一个SQL语句
     * @param string $sql 执行的SQL语句
     * @return MySQLiRecord | false
     */
    function query($sql)
    {
        $result = false;
        for ($i = 0; $i < 2; $i++)
        {
            $result = parent::query($sql);
            if ($result === false)
            {
                if ($this->errno == 2013 or $this->errno == 2006)
                {
                    $r = $this->checkConnection();
                    if ($r === true) continue;
                }
                else
                {
                    echo \Swoole\Error::info("SQL Error", $this->error."<hr />$sql");
                    return false;
                }
            }
            break;
        }
        if ($result === false)
        {
            echo \Swoole\Error::info("SQL Error", $this->error."<hr />$sql");
            return false;
        }
        return new MySQLiRecord($result);
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
     * 返回上一个Insert语句的自增主键ID
     * @return $ID
     */
    function Insert_ID()
    {
        return $this->insert_id;
    }
}
class MySQLiRecord implements \Swoole\IDbRecord
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
        while($record = $this->result->fetch_assoc())
        {
            $data[] = $record;
        }
        return $data;
    }
    function free()
    {
        $this->result->free_result();
    }
}
