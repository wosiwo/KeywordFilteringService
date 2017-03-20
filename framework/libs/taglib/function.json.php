<?php
function smarty_function_json($params, &$smarty)
{
	return json_encode($params['var']);
}
