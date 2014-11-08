<?php
function smarty_function_askey($params)
{
	$data = $params['data'];
	$key = $params['key'];
	if(isset($params['key2']))
	{
		$echo = $data[$key][$params['key2']];
	}
	else
	{
		$echo = $data[$key];
	}
	if(isset($params['default']) and empty($echo))
	{
		return $params['default'];
	}
	return $echo;
}