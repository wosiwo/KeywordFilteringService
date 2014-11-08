<?php
function smarty_function_url($params)
{
	if(isset($params['ignore'])) return Swoole_tools::url_merge($params['key'],$params['value'],$params['ignore']);
    else return Swoole_tools::url_merge($params['key'],$params['value']);
}
?>
