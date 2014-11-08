<?php
require('../lib_config.php');
session();
$authnum = '';
srand((double)microtime()*1000000);
$_SESSION['authcode']="";

/* imagick对象 */
$Imagick = new Imagick();

/* 背景对象 */
$bg = new ImagickPixel();

/* Set the pixel color to white */
$bg->setColor('rgb(235,235,235)');

/* 画刷 */
$ImagickDraw = new ImagickDraw();

/* Set font and font size. You can also specify /path/to/font.ttf */
$ImagickDraw->setFont(LIBPATH.'/../static/fonts/CONSOLA.TTF');
$ImagickDraw->setFontSize( 24 );
$ImagickDraw->setFillColor('black');

//生成数字和字母混合的验证码方法
$ychar="0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
$list=explode(",",$ychar);
for($i=0;$i<4;$i++)
{
	$randnum=rand(0,35);
	$authnum.=$list[$randnum];
}
$authnum = strtoupper($authnum);;
$_SESSION['authcode'] = $authnum;

/* Create new empty image */
$Imagick->newImage( 60, 24, $bg ); 

/* Write the text on the image */
$Imagick->annotateImage( $ImagickDraw, 4, 20, 0, $authnum );

/* 变形 */
//$Imagick->swirlImage( 10 );

/* 随即线条 */
/*$ImagickDraw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$ImagickDraw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$ImagickDraw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$ImagickDraw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );
$ImagickDraw->line( rand( 0, 70 ), rand( 0, 30 ), rand( 0, 70 ), rand( 0, 30 ) );*/

/* Draw the ImagickDraw object contents to the image. */
$Imagick->drawImage( $ImagickDraw );

/* Give the image a format */
$Imagick->setImageFormat( 'png' );

/* Send headers and output the image */
//header( "Content-Type: image/{$Imagick->getImageFormat()}" );
echo $Imagick->getImageBlob( );
?>