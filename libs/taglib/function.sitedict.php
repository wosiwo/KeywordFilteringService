<?php
function smarty_function_sitedict($params, &$smarty)
{
	$var = SiteDict::get($params['from']);
	$smarty->assign($params['_name'],$var);
}
?>
