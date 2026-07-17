<?php
/**
 * Динамическая PNG-иконка 192x192 для push-уведомлений.
 * URL: /traking/icons/push-icon.php
 */
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

$size = 192;
$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);

$white = imagecolorallocate($img, 255, 255, 255);
$navy = imagecolorallocate($img, 14, 27, 52);
$blue = imagecolorallocate($img, 79, 107, 237);
$gray = imagecolorallocate($img, 191, 197, 207);

imagefill($img, 0, 0, $white);

$s = 0.75;

// Карточка 1 (тёмная)
imagefilledrectangle($img, (int)(36*$s), (int)(40*$s), (int)(220*$s), (int)(88*$s), $navy);
// Карточка 2 (синяя)
imagefilledrectangle($img, (int)(36*$s), (int)(104*$s), (int)(181*$s), (int)(152*$s), $blue);
// Карточка 3 (серая)
imagefilledrectangle($img, (int)(36*$s), (int)(168*$s), (int)(141*$s), (int)(216*$s), $gray);

// Круги (белые)
imagefilledellipse($img, (int)(64*$s), (int)(64*$s), (int)(22*$s), (int)(22*$s), $white);
imagefilledellipse($img, (int)(64*$s), (int)(128*$s), (int)(22*$s), (int)(22*$s), $white);
imagefilledellipse($img, (int)(64*$s), (int)(192*$s), (int)(22*$s), (int)(22*$s), $white);

// Линии (белые)
imagefilledrectangle($img, (int)(96*$s), (int)(61*$s), (int)(192*$s), (int)(67*$s), $white);
imagefilledrectangle($img, (int)(96*$s), (int)(126*$s), (int)(156*$s), (int)(132*$s), $white);
imagefilledrectangle($img, (int)(96*$s), (int)(189*$s), (int)(141*$s), (int)(195*$s), $white);

imagepng($img);
imagedestroy($img);
