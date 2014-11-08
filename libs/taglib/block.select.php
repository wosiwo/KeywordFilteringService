<?php
function smarty_block_select($params, $body, &$smarty)
{
	if (is_null($body)) {
		return;
	}
	global $php;
	$select = new Swoole\SelectDB($php->db);
	$select->put($params);
	return SwooleTemplate::parse_loop($select->getall(),$body);
}
?>
