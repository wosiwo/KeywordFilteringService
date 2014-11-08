<?php
function smarty_modifier_spacefill($string,$length=0,$fill_str=' ')
{
	if($length===0) return $string;
	$len = mb_strlen($string);
    if($len<$length) return $string.str_repeat($fill_str,$length-$len);
}
?>