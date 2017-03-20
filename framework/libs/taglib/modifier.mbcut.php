<?php
function smarty_modifier_mbcut($string,$length=12,$more='')
{
	if(function_exists("mb_strlen"))
	{
		if(mb_strlen($string)>$length)
		{
			return mb_substr($string,0,$length).$more;
		}
		else
		{
			return $string;
		}
	}
	else
	{
		$length = $length*2;
		if(strlen($string)>$length)
		{
			return substr($string,0,$length).$more;
		}
		else
		{
			return $string;
		}
	}
}