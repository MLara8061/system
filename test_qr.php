<?php
ob_clean();
header('Content-Type: image/png');

$gd = imagecreatetruecolor(100, 100);
$white = imagecolorallocate($gd, 255, 255, 255);
$black = imagecolorallocate($gd, 0, 0, 0);
imagefilledrectangle($gd, 0, 0, 100, 100, $white);
imagestring($gd, 5, 10, 40, 'TEST', $black);

imagepng($gd);
imagedestroy($gd);
exit;
?>