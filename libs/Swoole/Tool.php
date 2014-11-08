<?php
namespace Swoole;
/**
 * 附加工具集合
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage tools
 */
class Tool
{
    static public $url_key_join = '=';
    static public $url_param_join = '&';
    static public $url_prefix = '';
    static public $url_add_end = '';
    const DATE_FORMAT_HTTP   = 'D, d-M-Y H:i:s T';

    static $number = array('〇','一','二','三','四','五','六','七','八','九');

    /**
     * 数字转为汉字
     * @param $num_str
     * @return mixed
     */
    static function num2han($num_str)
    {
        return str_replace(range(0,9),self::$number,$num_str);
    }

    static function scandir($dir)
    {
        if (function_exists('scandir'))
        {

        }
        else
        {
            $dh  = opendir($dir);
            while (false !== ($filename = readdir($dh)))
            {
                if ($filename == '.' or $filename == '..')
                {
                    continue;
                }
                $files[] = $filename;
            }

            sort($files);
        }
        $dirs = scandir($dir);
    }

    /**
     * 解析URI
     * @param $url
     * @return unknown_type
     */
    static public function uri($url)
    {
        $res = parse_url($url);
        $return['protocol'] = $res['scheme'];
        $return['host'] = $res['host'];
        $return['port'] = $res['port'];
        $return['user'] = $res['user'];
        $return['pass'] = $res['pass'];
        $return['path'] = $res['path'];
        $return['id'] = $res['fragment'];
        parse_str($res['query'], $return['params']);
        return $return;
    }

    static function httpExpire($lastModifyTime, $expire = 1800)
    {
        $expire = intval($expire);
        $responseTime = $requestTime = $_SERVER['REQUEST_TIME'];
        $result = true;

       if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
        {
            $lastModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($lastModifiedSince and $requestTime <= ($lastModifiedSince + $expire))
            {
                header('HTTP/1.1 304 Not Modified');
            }
            $result = false;
        }

        $headers = array(
            'Cache-Control' => "max-age={$expire}", // HTTP 1.1
            'Pragma' => "max-age={$expire}", // HTTP 1.0
            'Last-Modified' => date(self::DATE_FORMAT_HTTP, $lastModifyTime),
            'Expires' => date(self::DATE_FORMAT_HTTP, $responseTime + $expire),
        );

        foreach ($headers as $key => $value)
        {
            header("{$key}: {$value}");
        }
        return $result;
    }
    /**
     * 多久之前
     * @param $datetime
     * @return unknown_type
     */
    static function howLongAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $seconds = time();

        $time = date('Y', $seconds) - date('Y', $timestamp);
        if ($time > 0) {
            if ($time == 1) return '去年';
            else return $time . '年前';
        }

        $time = date('m', $seconds) - date('m', $timestamp);
        if ($time > 0) {
            if ($time == 1) return '上月';
            else return $time . '个月前';
        }
        $time = date('d', $seconds) - date('d', $timestamp);
        if ($time > 0) {
            if ($time == 1) return '昨天';
            elseif ($time == 2) return '前天'; else return $time . '天前';
        }

        $time = date('H', $seconds) - date('H', $timestamp);
        if ($time >= 1) return $time . '小时前';

        $time = date('i', $seconds) - date('i', $timestamp);
        if ($time >= 1) return $time . '分钟前';

        $time = date('s', $seconds) - date('s', $timestamp);
        return $time . '秒前';
    }

    /**
     * 合并URL字串，parse_query的反向函数
     * @param $urls
     * @return unknown_type
     */
    static function combine_query($urls)
    {
        foreach ($urls as $k => $v) {
            if (!empty($k)) $url[] = $k . self::$url_key_join . urlencode($v);
        }
        return implode(self::$url_param_join, $url);
    }

    /**
     * URL合并
     * @param $key
     * @param $value
     * @param $ignore
     * @return unknown_type
     */
    static function url_merge($key, $value, $ignore = null, $urls = null)
    {
        $url = array();
        if ($urls === null) $urls = $_GET;

        $urls = array_merge($urls, array_combine(explode(',', $key), explode(',', $value)));
        if ($ignore !== null) {
            $ignores = explode(',', $ignore);
            foreach ($ignores as $ig) unset($urls[$ig]);
        }
        if (self::$url_prefix == '')
        {
            $qm = strpos($_SERVER['REQUEST_URI'], '?');
            if($qm !== false) {
                $prefix = substr($_SERVER['REQUEST_URI'], 0, $qm+1);
            } else {
                $prefix = $_SERVER['REQUEST_URI'] . '?';
            }
        }
        else $prefix = self::$url_prefix;
        return $prefix . self::combine_query($urls) . self::$url_add_end;
    }

    /**
     * URL解析到REQUEST
     * @param $url
     * @param $request
     * @return unknown_type
     */
    static function url_parse_into($url, &$request)
    {
        $url = str_replace(self::$url_add_end, '', $url);
        if (self::$url_key_join == self::$url_param_join)
        {
            $urls = explode(self::$url_param_join, $url);
            $c = intval(count($urls) / 2);
            for ($i = 0; $i < $c; $i++)
            {
                $request[$urls[$i * 2]] = $urls[$i * 2 + 1];
            }
        }
        else
        {
            $urls = explode(self::$url_param_join, $url);
            foreach ($urls as $u)
            {
                $us = explode(self::$url_key_join, $u);
                $request[$us[0]] = $us[1];
            }
        }
    }

    /**
     * 数组编码转换
     * @param $in_charset
     * @param $out_charset
     * @param $data
     * @return $data
     */
    static function array_iconv($in_charset, $out_charset, $data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) $value = self::array_iconv($in_charset, $out_charset, $value);
                else $value = iconv($in_charset, $out_charset, $value);
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * 数组饱满度
     * @param $array
     * @return unknown_type
     */
    static function array_fullness($array)
    {
        $nulls = 0;
        foreach ($array as $v) if (empty($v) or intval($v) < 0) $nulls++;
        return 100 - intval($nulls / count($array) * 100);
    }

    /**
     * 根据生日中的月份和日期来计算所属星座*
     * @param int $birth_month
     * @param int $birth_date
     * @return string
     */
    static function get_constellation($birth_month, $birth_date)
    {
        //判断的时候，为避免出现1和true的疑惑，或是判断语句始终为真的问题，这里统一处理成字符串形式
        $birth_month = strval($birth_month);
        $constellation_name = array('水瓶座', '双鱼座', '白羊座', '金牛座', '双子座', '巨蟹座', '狮子座', '处女座', '天秤座', '天蝎座', '射手座', '摩羯座');
        if ($birth_date <= 22) {
            if ('1' !== $birth_month) {
                $constellation = $constellation_name[$birth_month - 2];
            } else {
                $constellation = $constellation_name[11];
            }
        } else {
            $constellation = $constellation_name[$birth_month - 1];
        }
        return $constellation;
    }

    /**
     * 根据生日中的年份来计算所属生肖
     *
     * @param int $birth_year
     * @return string
     */
    static function get_animal($birth_year, $format = '1')
    {
        //1900年是子鼠年
        if ($format == '2') $animal = array('子鼠', '丑牛', '寅虎', '卯兔', '辰龙', '巳蛇', '午马', '未羊', '申猴', '酉鸡', '戌狗', '亥猪');
        elseif ($format == '1') $animal = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
        $my_animal = ($birth_year - 1900) % 12;
        return $animal[$my_animal];
    }

    /**
     * 根据生日来计算年龄
     *
     * 用Unix时间戳计算是最准确的，但不太好处理1970年之前出生的情况
     * 而且还要考虑闰年的问题，所以就暂时放弃这种方式的开发，保留思想
     *
     * @param int $birth_year
     * @param int $birth_month
     * @param int $birth_date
     * @return int
     */
    static function get_age($birth_year, $birth_month, $birth_date)
    {
        $now_age = 1; //实际年龄，以出生时为1岁计
        $full_age = 0; //周岁，该变量放着，根据具体情况可以随时修改
        $now_year = date('Y', time());
        $now_date_num = date('z', time()); //该年份中的第几天
        $birth_date_num = date('z', mktime(0, 0, 0, $birth_month, $birth_date, $birth_year));
        $difference = $now_date_num - $birth_date_num;

        if ($difference > 0) {
            $full_age = $now_year - $birth_year;
        } else {
            $full_age = $now_year - $birth_year - 1;
        }
        $now_age = $full_age + 1;
        return $now_age;
    }

    /**
     * 发送一个UDP包
     * @return unknown_type
     */
    static function sendUDP($server_ip, $server_port, $data, $timeout = 30)
    {
        $client = stream_socket_client("udp://$server_ip:$server_port", $errno, $errstr, $timeout);
        if (!$client) {
            echo "ERROR: $errno - $errstr<br />\n";
        } else {
            fwrite($client, $data);
            fclose($client);
        }
    }

    /**
     * 复制目录
     * @param $fdir源目录名(不带/)
     * @param $tdir目标目录名(不带/)
     * @return
     */
    static function dir_copy($fdir, $tdir)
    {
        if (is_dir($fdir)) {
            if (!is_dir($tdir)) {
                mkdir($tdir);
            }
            $handle = opendir($fdir);
            while (false !== ($filename = readdir($handle))) {
                if ($filename != "." && $filename != "..") self::dir_copy($fdir . "/" . $filename, $tdir . "/" . $filename);
            }
            closedir($handle);
            return true;
        } else {
            copy($fdir, $tdir);
            return true;
        }
    }
}

?>