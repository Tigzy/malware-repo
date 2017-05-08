<?php
require_once(__DIR__.'/lib/ico/floIcon.php');

function ResizeImage($img, $width, $height)
{
    if (empty($img)) return "";
    
    $decoded    = base64_decode($img);
    $image      = imagecreatefromstring($decoded);   
    if ($image == false) {
        return "";
    }
    
    $image_n    = imagecreatetruecolor($width, $height);
    if ($image_n == false) {
        return "";
    }
    
    imagealphablending( $image_n, false );
    imagesavealpha( $image_n, true );
    
    if (imagecopyresampled($image_n, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image)) == false) {
        return "";
    }
    
    ob_start();
    imagepng($image_n, null, 0);
    $stream = ob_get_clean();
    imagedestroy($image_n);
    return base64_encode($stream);
}

function ConvertIcon($icon)
{
	$converted = base64_decode($icon);
	if(empty($converted)) return "";
	
	// Write ICO to temp file
	$temp_file = tempnam(sys_get_temp_dir(), "mrf");
	$fp = fopen($temp_file, 'w');
	if (!$fp) return "";
	
	fwrite($fp, $converted);
	fclose($fp);
	
	// Convert to PNG
	$ico = new floIcon();
	$ico->readICO($temp_file);
	$image_n = $ico->getBestImage();
	if (empty($image_n)) {
		unlink($temp_file);
		return "";
	}
	
	ob_start();
	imagepng($image_n, null);
	$stream = ob_get_clean();
	
	// Cleanup
	imagedestroy($image_n);
	unlink($temp_file);
	
	return base64_encode($stream);
}