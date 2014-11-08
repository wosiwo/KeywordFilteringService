<?php
function smarty_block_app($params, $body, &$smarty)
{
	if (empty($body)) return;

	global $php;
	$app = $php->createModel('App');
	if(array_key_exists('name',$params))
	{
		$app_config = $app->getConfig($params['name']);
		$app_instance = $app->getInstance();
		unset($params['name']);
	}
	elseif(array_key_exists('typeid',$params))
	{
		$cate = $php->createModel('Category');
		$category = $cate->get($params['typeid']);
		$app_config = $app->getConfig($category['modelname']);
		$app_instance = $app->getInstance();
	}
	else return;
	
	if(array_key_exists('func',$params))
	{
		$func=$params['func'];
		unset($params['func']);
	}
	else $func = 'getList';
	
	$stpl = new SwooleTemplate($php->db);
	
	$fields = implode(',',$stpl->get_fields($body));
	if(empty($fields)) return;
	
	if(array_key_exists('titlelen',$params))
	{		
		$titlelen = $params['titlelen'];
		unset($params['titlelen']);
		$app_instance->select = str_replace('title',"substring( title, 1, $titlelen ) AS title,title as title_full",$fields);
	}

	if(strpos($fields,'url')===false) $fields.=',url';
	$app_instance->select = $fields;
	
	$data = call_user_func(array($app_instance,$func),$params);
	return $stpl->parse_loop($data,$body,$fields);
}
?>
