<?php
define('DEBUG', 'on');
define("WEBPATH", realpath(__DIR__.'/../'));
require dirname(__DIR__) . '/libs/lib_config.php';

Swoole\Config::$debug = false;

require 'filter.class.php';

use My\Filter as F;

class HttpServer extends Swoole\Protocol\HttpServer
{

	function onStart($serv, $worker_id = 0)
	{
		parent::onStart($serv, $worker_id);
		$file = array('bin'=>realpath(__DIR__.'/dict_all.dat'),'source'=>realpath(__DIR__.'/dict_all.dat'));
		$this->trie = new F\Trie($file);
		$this->trie->nodes = $this->trie->getBinaryDict($file['bin']);
	}


   /**
     * 处理请求
     * @param $request
     * @return Swoole\Response
     */
    function onRequest(Swoole\Request $request)
    {
        $response = new Swoole\Response;
        $this->currentResponse = $response;

        $uri = $request->meta['uri'];
        $uri = parse_url($uri);
        // print_r($param);

        if (isset($uri['query'])) {
        	parse_str($uri['query'],$param);
        	// print_r($param);
        	$word  = self::getItem($param,'word');

       		$result = $this->search($word);
       		// $result = self::changeCharset($result,'gbk','utf-8');
       		$result = json_encode($result);
       		$this->httpSend(200, $response, $result);
        	return $response;
        }else{
        	$this->httpSend(200, $response, "wosiwo");
        	return $response;
        }
       


        
    }

    public function search($word='')
    {
    	// $word = self::changeCharset($word);
    	$result = $this->trie->search($word);
    	return $result;
    }

    /**
     * 直接输出http内容
     * @param                 $code
     * @param Swoole\Response $response
     * @param string          $content
     */
    function httpSend($code, Swoole\Response $response, $content = '')
    {
        $response->send_http_status($code);
        $response->head['Content-Type'] = 'text/html';
        $response->body = $content;
    }

    public static function getItem($array,$key='',$default=0)
    {
    	return isset($array[$key])?$array[$key]:$default;
    }
    //编码转换utf->gbk
	public static function changeCharset($value, $from = 'utf-8', $to = 'gbk//ignore')
	{
		$result = array();
		if(is_array($value)){
			foreach($value as $k=> $v){
				$k = self::changeCharset($k, $from, $to);
				$result[$k] = self::changeCharset($v, $from, $to);
			}
		}else{
			$value = (is_numeric($value) && floatval((int) $value) === floatval($value)) ? (int) $value : $value;
			$result = is_string($value) ? iconv($from, $to, $value) : $value;
		}
		return $result;
	}

}

$AppSvr = new HttpServer();
$AppSvr->loadSetting(__DIR__.'/swoole.ini'); //加载配置文件
$AppSvr->setDocumentRoot(__DIR__.'/webroot');
$AppSvr->setLogger(new Swoole\Log\EchoLog(true)); //Logger

Swoole\Error::$echo_html = false;

$server = Swoole\Network\Server::autoCreate('0.0.0.0', 8888);
$server->setProtocol($AppSvr);
//$server->daemonize(); //作为守护进程
$server->run(array('worker_num' => 0, 'max_request' => 5000, 'log_file' => '/tmp/swoole.log'));
