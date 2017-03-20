<?php
function smarty_block_app($params, $body, &$smarty)
{
	if (empty($body)) return;
	
	if(!array_key_exists('name',$params))
	{
		Error::info('Tag param error:','app标签必须有参数name!');
		return;
	}
			
	if(array_key_exists('func',$params))
	{
		$func=$params['func'];
		unset($params['func']);
	}
	else $func = 'getList';
	
	$fields = implode(',',SwooleTemplate::get_fields($body));
	global $php;
	$php->createModel($model);
	
	if(array_key_exists('titlelen',$params))
	{		
		$titlelen = $params['titlelen'];
		unset($params['titlelen']);
		$php->model->$model->select = str_replace('title',"substring( title, 1, $titlelen ) AS title",$fields);
	}
	else
	{
		$php->model->$model->select = $fields;
	}
	$data = call_user_func(array($php->model->$model,$func),$params);
	return SwooleTemplate::parse_loop($data,$body,$fields);
}
