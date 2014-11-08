<?php
function smarty_block_category($params, $body, &$smarty)
{
	if (empty($body)) return;

	global $php;
	$category_ins = $php->createModel('Category');
	
	if(array_key_exists('app',$params))
	{
		$app = $php->createModel('App');
		$app_config = $app->getConfig($params['app']);
		$params['fid'] = $app_config['category'];
		unset($params['app']);
	}

	if(array_key_exists('func',$params))
	{
		$func=$params['func'];
		unset($params['func']);
	}
	else $func = 'getChild';
	
	$stpl = new SwooleTemplate($php->db);
	$data = call_user_func_array(array($category_ins,$func),$params);
	return $stpl->parse_loop($data,$body,$fields);
}
?>
