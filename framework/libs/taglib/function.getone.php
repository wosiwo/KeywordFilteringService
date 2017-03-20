<?php
function smarty_function_getone($params, &$smarty)
{
	$record_name = $params['_name'];
	if(!array_key_exists($record_name,$smarty->_tpl_vars) or array_key_exists('_force',$params)):
		global $php;
		$select = new Swoole\SelectDB($php->db);
		$select->call_by = 'func';
		$select->put($params);
		$record = $select->getone();
		$smarty->_tpl_vars[$record_name] = $record;
	endif;
	//if(array_key_exists('_field',$params)) return $smarty->_tpl_vars[$record_name][$params['_field']];
}
