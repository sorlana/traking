<?php
/**
 * Монохромная badge-иконка 96x96 для push-уведомлений (статус-бар Android).
 * Должна быть белой на прозрачном фоне — Android сам применяет цвет.
 */
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

$size = 96;
$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);

// Прозрачный фон
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

// Белый цвет для силуэта
$white = imagecolorallocate($img, 255, 255, 255);

$s = $size / 256;

// Карточка 1
imagefilledrectangle($img, (int)(36*$s), (int)(40*$s), (int)(220*$s), (int)(88*$s), $white);
// Карточка 2
imagefilledrectangle($img, (int)(36*$s), (int)(104*$s), (int)(181*$s), (int)(152*$s), $white);
// Карточка 3
imagefilledrectangle($img, (int)(36*$s), (int)(168*$s), (int)(141*$s), (int)(216*$s), $white);

imagepng($img);
imagedestroy($img);
