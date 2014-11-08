<?php
namespace Swoole\Html;

class ViewToDB extends \SwooleObject
{
	public $templates_dir;
	
	public $in_content='';
	public $out_content='';
	public $c_content='';
		
	public $pattern;
	
	public $db;
	
	function __construct($db)
	{
		$this->db = $db;
	}
	
	function parse($tpl_name)
	{
		
		$filename = WEBPATH.$this->templates_dir.$tpl_name;
		$this->c_content = $this->in_content = file_get_contents($filename);
		
		$tags=array();
		$this->pattern = "#".preg_quote('{{','~')."[^}]+".preg_quote('}}','~')."#i";
        preg_match_all($this->pattern,$this->in_content,$tags,PREG_PATTERN_ORDER);		
        $tags=$tags[0];
        
		foreach($tags as $tag)
		{
			$tagname=self::trim($tag);
			$exp=explode(":",$tagname);
			if(count($exp)>2) die("错误的标签:".$tag);
			if(count($exp)==1) continue;
			$this->parse_static($tag);
			call_user_func_array(array($this,'proc_'.$exp[0]),array(trim($exp[1]),$tag));
		}
		$this->end();
		echo $this->out_content;
	}

	function assign($key,$value)
	{
		$this->$key=$value;
	}
	
	function proc_var($tag,$tag_head)
	{
		$this->c_content = str_replace($tag_head,$this->$tag,$this->c_content);

//		$params=explode(".",$varname);
//		$varnum=count($params);
//		if($varnum==1)
//		{
//			return $$varname;
//		}
//		elseif($varnum==2)
//		{
//			$arrayname=$params[0];
//			$array=$$arrayname;
//			return $array[$params[1]];
//		}
	}
	
	public static function parse_param($tagbody)
	{
		$rs = array();
		$list= explode(" ",$tagbody);
		foreach($list as $val)
		{
			$arr=explode("=",$val,2);
			$rs[$arr[0]]=$arr[1];
		}
		return $rs;
	}
	
	function proc_records($tag,$tag_head)
	{
		$param = self::parse_param($tag);
		$select = new SelectDB($this->db);
		$select->put($param);
		$body = $this->get_body('records');
		$list = $select->getall();
		
		$content = '';
		
		foreach($list as $record)
		{
			$content.=$this->parse_record_body($body,$record);
		}
		$this->out_content.=$content;
		$this->clear('records');
	}
	
	function proc_record($tag,$tag_head)
	{
		$param = self::parse_param($tag);
		$record_name = $param['_name'];
		$record_field = $param['_field'];
		if($this->$record_name!=false) $record=$this->$record_name;
		else
		{
			$select = new SelectDB($this->db);
			$select->put($param);
			$record = $select->getone();
			$this->$record_name = $record;
		}
		$this->out_content.=$record[$record_field];
		$this->c_content = str_replace($tag_head,'',$this->c_content);
	}
	
	function parse_record_body($line,$record)
	{
		$vals = array();
		preg_match_all($this->pattern,$line,$vals);
		$vals=$vals[0];
		foreach($vals as $val) $line=str_replace($val,$record[self::trim($val)],$line);
		return $line;
	}
	
	function clear($tagname)
	{
		$end_tag = '{{/'.$tagname.'}}';
		$pos_e = mb_strpos($this->c_content,$end_tag);		
		$this->c_content = substr($this->c_content,$pos_e+strlen($end_tag),-1);
	}
	
	function parse_static($tag)
	{
		$pos = strpos($this->c_content,$tag);
		$this->out_content.= substr($this->c_content,0,$pos);
		$this->c_content = substr($this->c_content,$pos,strlen($this->c_content)-$pos);
	}
	
	function end()
	{
		$this->out_content.=$this->c_content;
	}
	
	function get_body($tagname)
	{
		$end_tag = '{{/'.$tagname.'}}';
		$pos_s = mb_strpos($this->c_content,'}}')+2;
		$pos_e = mb_strpos($this->c_content,$end_tag);
		return mb_substr($this->c_content,$pos_s,$pos_e-$pos_s);
	}
	
	public static function trim($string)
	{
		return str_replace('}}',"",str_replace('{{',"",$string));
	}
}
?>