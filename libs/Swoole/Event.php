<?php
namespace Swoole;

class Event
{
	private $_queue;
	private $_handles = array();
	public $mode;

	function __construct($mode,$queue_url='',$queue_type='')
	{
		$this->mode = $mode;
		if($queue_url and $mode=='async')
		{
			$this->_queue = new Queue(array('server_url'=>$queue_url,'name'=>'swoole_event'),$queue_type);
		}
	}
	/**
	 * 引发一个事件
	 * @param $event_type 事件类型
	 * @return NULL
	 */
	function raise()
	{
		$params = func_get_args();
		/**
		 * 同步，直接在引发事件时处理
		 */
        if($this->mode=='sync')
        {
        	if(!isset($this->_handles[$params[0]]) or !function_exists($this->_handles[$params[0]]))
        	{
        		if(empty($handle)) Error::info('SwooleEvent Error','Event handle not found!');
        	}
        	return call_user_func_array($this->_handles[$params[0]],array_slice($params,1));
        }
        /**
         * 异步，将事件压入队列
         */
        else
        {
            $this->_queue->put($params);
        }
	}
    /**
     * 增加对一种事件的监听
     * @param $event_type 事件类型
     * @param $call_back  发生时间后的回调程序
     * @return NULL
     */
	function addListener($event_type,$call_back)
	{
		$this->_handles[$event_type] = $call_back;
	}

	function run_server($time=1,$log_file=null)
	{
		$filelog = new FileLog($log_file);
		while(true)
		{
		    $event = $this->_queue->get();
			if($event and !isset($event['HTTPSQS_GET_END']))
			{
			    if(!isset($this->_handles[$event[0]]))
			    {
			        $filelog->info('SwooleEvent Error: empty event!');
			    }
			    $func = $this->_handles[$event[0]];

			    if(!function_exists($func))
			    {
			        $filelog->info('SwooleEvent Error: event handle function not exists!');
			    }
	            else
	            {
	                $parmas = array_slice($event,1);
	            	call_user_func_array($func,$parmas);
                    $filelog->info('SwooleEvent Info: process success!event type '.$func.',params('.implode(',',$parmas).')');
	            }
			}
		    else
		    {
		    	usleep($time*1000);
		    	//echo 'sleep',NL;
		    }
		}
	}
    /**
     * 设置监听列表
     * @param $listens
     * @return unknown_type
     */
	function set_listens($listens)
	{
		$this->_handles = array_merge($this->_handles,$listens);
	}
}
