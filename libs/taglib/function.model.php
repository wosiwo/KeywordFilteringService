<?php
function smarty_function_model($params, &$smarty)
{
    if(!isset($params['_from']))
	{
		echo 'No model name!';
		return false;
	}
    if(!isset($params['_name']))
	{
		echo 'No record variable name!';
		return false;
	}
	$smarty->_tpl_vars[$params['_name']] = model($params['_from'])->gets($params, $pager);
    if(isset($params['page']))
	{
		$smarty->assign("pager",array('total'=>$pager->total,'render'=> $pager->render()));
	}
}
