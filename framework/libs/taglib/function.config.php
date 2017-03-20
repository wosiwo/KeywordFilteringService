<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_function_config($params, &$smarty)
{
	global $php;
	if(!is_object($php->model->Config)) $php->createModel('Config');
	return $php->model->Config[$params['name']];
}
?>