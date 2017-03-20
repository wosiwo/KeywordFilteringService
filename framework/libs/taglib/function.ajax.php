<?php
function smarty_function_ajax($params, &$smarty)
{
	$js_lib = $params['lib'];
	if($js_lib=='ext')
	{
		$js = "<link rel='stylesheet' type='text/css' href='/libs/code/ext/resources/css/ext-all.css' />\n";
		$js .="<script type='text/javascript' src='/libs/code/ext/adapter/ext/ext-base.js'></script>\n";
		$js .="<script type='text/javascript' src='/libs/code/ext/ext-all-debug.js'></script>";
	}
	else
	{
		$js = "<script type='text/javascript' src='/libs/code/js/jquery.js'></script>\n";
		$js .= "<script type='text/javascript' src='/libs/code/js/ajax.js'></script>";
	}
	return $js;
}
