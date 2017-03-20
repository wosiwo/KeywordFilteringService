<?php
function swoole_filter_image($value,&$params,&$record)
{
	return "<img src=\"{$record[$params[0]]}\" title=\"{$record['title']}\" width=\"{$params[1]}\" height=\"{$params[2]}\" />";
}