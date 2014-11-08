<?php
if(!defined('EVENT_MODE') or EVENT_MODE=='sync')
{
	$event = new SwooleEvent('sync');
}
else
{
	$queue_url = '';
	$queue_type = 'CacheQueue';

	if(defined('EVENT_QUEUE')) $queue_url = EVENT_QUEUE;
	if(defined('EVENT_QUEUE_TYPE')) $queue_type = EVENT_QUEUE_TYPE;
	$event = new SwooleEvent('async',$queue_url,$queue_type);
}
if(defined('EVENT_HANDLE'))
{
	require EVENT_HANDLE;
	if(empty($handle)) Swoole\Error::info('SwooleEvent Error','Event handles not be empty!');
	$event->set_listens($handle);
}