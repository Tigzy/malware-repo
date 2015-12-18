<?php
/*
UserCake Version: 2.0.2
http://usercake.com
*/
session_start();
$md5_hash = md5(rand(0,99999)); 
$security_code = substr($md5_hash, 25, 5); 
$enc = md5($security_code);
$_SESSION['captcha'] = $enc;

$width = 150;
$height = 30; 

$image = ImageCreate($width, $height);  
$white = ImageColorAllocate($image, 255, 255, 255);
$black = ImageColorAllocate($image, 0, 0, 0);
$grey = ImageColorAllocate($image, 200, 200, 200);

ImageFill($image, 0, 0, $white); 
ImageString($image, 10, 5, 0, $security_code, $black); 

header("Content-Type: image/png"); 
ImagePng($image);
ImageDestroy($image);

?>
