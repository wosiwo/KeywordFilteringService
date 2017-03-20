<?php
namespace Swoole;

class StringObject
{
    protected $string;

    function __construct($string)
    {
        $this->string = $string;
    }

    function __toString()
    {
        return $this->string;
    }

    function pos($find_str)
    {
        return strpos($this->string, $find_str);
    }

    function rpos($find_str)
    {
        return strrpos($this->string, $find_str);
    }

    function ipos($find_str)
    {
        return stripos($this->string, $find_str);
    }

    function lower()
    {
        return new StringObject(strtolower($this->string));
    }

    function upper()
    {
        return new StringObject(strtoupper($this->string));
    }

    function len()
    {
        return strlen($this->string);
    }

    function substr($offset, $length = null)
    {
        return new StringObject(substr($this->string, $offset, $length));
    }

    function replace($search, $replace, &$count = null)
    {
        return new StringObject(str_replace($search, $replace, $this->string, $count));
    }

    function  startWith($needle)
    {
        return strpos($this->string, $needle) === 0;
    }

    function endWith($needle)
    {
        $length = strlen($needle);
        if ($length == 0)
        {
            return true;
        }
        return (substr($this->string, -$length) === $needle);
    }

    function split($sp, $limit = null)
    {
        return new ArrayObject(explode($sp, $limit));
    }

    function toArray($splitLength = 1)
    {
        return new ArrayObject(str_split($this->string, $splitLength));
    }

    /**
     * 比较2个版本号，如1.0.1
     * @param $version1
     * @param $version2
     * @return int
     * @throws \Exception
     */
    static function versionCompare($version1, $version2)
    {
        if (!Validate::isVersion($version1) or !Validate::isVersion($version2))
        {
            throw new \Exception("[$version1] or [$version2] is not a version string.");
        }
        $v1 = explode('.', $version1);
        $v2 = explode('.', $version2);

        for($i = 0; $i < 3; $i++)
        {
            $_v1 = intval($v1[$i]);
            $_v2 = intval($v2[$i]);
            //版本1高
            if ($_v1 > $_v2)
            {
                return 1;
            }
            //版本2高
            elseif ($_v1 < $_v2)
            {
                return -1;
            }
            //版本相同，继续向下比较
            else
            {
                continue;
            }
        }
        //如果3个版本全部一致，返回0
        return 0;
    }
}
