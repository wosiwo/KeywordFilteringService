<?php
class Csv
{
	function __construct()
	{
		
	}
	static function output($file)
	{
		$f = fopen($file,'r');
		while($data = fgetcsv($f,1024,','))
		{
			$list[] = $data;
		}
		fclose($f);
		return $list;
	}
}
?>