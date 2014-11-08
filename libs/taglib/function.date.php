<?php
function smarty_function_date($params)
{
    if(isset($params['fmt'])) return date($params['fmt']);
	return date("Y-m-d");
}