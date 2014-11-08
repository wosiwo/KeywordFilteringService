<?php
function smarty_modifier_iconv($string,$s,$d)
{
	return iconv($s,$d,$string);
}
?>
