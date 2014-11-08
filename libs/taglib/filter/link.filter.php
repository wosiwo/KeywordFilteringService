<?php
function swoole_filter_link($value,&$params,&$record)
{
	if(!isset($params[1])) $params[1]='_self';
	return "<a href=\"{$record['url']}\" target=\"{$params[1]}\">$value</a>";
}
?>