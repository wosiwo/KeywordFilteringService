<?php
require('../lib_config.php');
session();
//生成验证码图片
$authnum = '';
header("Content-type: image/PNG");
srand((double)microtime()*1000000);//播下一个生成随机数字的种子，以方便下面随机数生成的使用

//session_start();//将随机数存入session中
$_SESSION['authcode']="";
$im = imagecreate(50,20) or die("Cant's initialize new GD image stream!"); //制定图片背景大小
$black = ImageColorAllocate($im, 0,0,0); //设定三种颜色
$white = ImageColorAllocate($im, 255,255,255);
$gray = ImageColorAllocate($im, 235,235,235);

imagefill($im,0,0,$gray); //采用区域填充法，设定(0,0)

//生成数字和字母混合的验证码方法
$ychar="0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
$list=explode(",",$ychar);
for($i=0;$i<4;$i++){
$randnum=rand(0,35);
$authnum.=$list[$randnum];
}
$authnum = strtoupper($authnum);;
//while(($authnum=rand()%100000)<10000); //生成随机的五们数
//将五位整数验证码绘入图片
$_SESSION['authcode']= $authnum;
imagestring($im, 8, 8, 4, $authnum, $black);
// 用 col 颜色将字符串 s 画到 image 所代表的图像的 x，y座标处（图像的左上角为 0, 0）。
//如果 font 是 1，2，3，4 或 5，则使用内置字体

for($i=0;$i<40;$i++) //加入干扰象素
{
	$randcolor = ImageColorallocate($im,rand(50,100),rand(50,100),rand(50,100));
	imagesetpixel($im, rand()%70 , rand()%30 , $randcolor);
}
ImagePNG($im);
ImageDestroy($im);
?>