<?php
function file_upload($name,$up_dir=null,$access='',$filename=null)
{
	if(empty($up_dir)) $up_dir = UPLOAD_DIR."/".date('Y').date("m")."/".date("d");

	$path=WEBPATH.$up_dir;
	if(!file_exists($path))
	{
		mkdir($path,0777,true);
	}

	$mime=$_FILES[$name]['type'];
	$filetype= file_gettype($mime);

	if($filetype=='bin') $filetype = file_ext($_FILES[$name]['name']);

	if($filetype==false)
	{
		echo "File Type Error!";
		return false;
	}
	elseif(!empty($access))
	{
		$access_type = explode(',',$access);
		if(!in_array($filetype,$access_type))
		{
			echo "File Type '$filetype' not allow upload!";
			return false;
		}
	}
	if($filename==null) $filename=substr(time(),6,-1).rand(100000,999999);
	$filename.=".".$filetype;
	if (move_uploaded_file($_FILES[$name]['tmp_name'],$path."/".$filename))
	{
		return "$up_dir/$filename";
	}
	else
	{
		echo "Error! debug:\n";
		print_r($_FILES[$name]);
		return false;
	}
}
function file_gettype($mime)
{
    require LIBPATH . '/data/mimes.php';
	if(isset($mimes[$mime])) return $mimes[$mime];
	else return false;
}
function file_ext($file)
{
	return strtolower(trim(substr(strrchr($file, '.'), 1)));
}
?>