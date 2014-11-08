<?php
if(!isset($_GET['page']))
{
	$page=1;
}
else
{
	$page=$_GET['page'];
}
$offset=($page-1)*$pagesize;
if($num%$pagesize>0) $pages=intval($num/$pagesize)+1;
else{$pages=$num/$pagesize;}
$start=10*intval($page/10);
if($pages>10 and $page<$start) $php->tpl->assign("more",1);
$php->tpl->assign("start",$start);
$php->tpl->assign("end",$pages-$start);
$php->tpl->assign("pages",$pages);
$php->tpl->assign("page",$page);
$php->tpl->assign("pagesize",$pagesize);
$php->tpl->assign("num",$num);
?>