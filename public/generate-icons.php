<?php
/**
 * Генерация PNG-иконок для PWA из фавиконки
 * Дизайн: синий квадрат с закруглёнными углами + белая стилизованная "A"
 *
 * Запустите один раз: https://unique-style.ru/traking/generate-icons.php
 * После генерации файл можно удалить.
 */

if (!extension_loaded('gd')) {
    die('Расширение GD не установлено на сервере');
}

$sizes = [192, 512];
$outputDir = __DIR__ . '/icons/';

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    imagealphablending($img, true);

    // Цвета
    $blue = imagecolorallocate($img, 79, 107, 237);   // #4F6BED
    $white = imagecolorallocate($img, 255, 255, 255);

    // Заполняем синим фоном
    imagefill($img, 0, 0, $blue);

    // Масштабный коэффициент (SVG viewBox 32x32 → $size x $size)
    $scale = $size / 32;

    // Рисуем белую букву "A" (треугольник: M16,4 → L8,28 → L24,28)
    // Координаты из SVG: d="M16 4L8 28h3.5l4.5-12 4.5 12H24L16 4z"
    $points_a = [
        16 * $scale, 4 * $scale,    // верх
        8 * $scale, 28 * $scale,    // лево низ
        11.5 * $scale, 28 * $scale, // лево низ внутренний
        16 * $scale, 16 * $scale,   // центр
        20.5 * $scale, 28 * $scale, // право низ внутренний
        24 * $scale, 28 * $scale,   // право низ
    ];
    imagefilledpolygon($img, $points_a, 6, $white);

    // Внутренняя вырезка (синяя полоса по центру): d="M16 4L14.5 28h3L16 4z"
    $points_cut = [
        16 * $scale, 4 * $scale,     // верх
        14.5 * $scale, 28 * $scale,  // лево низ
        17.5 * $scale, 28 * $scale,  // право низ
    ];
    imagefilledpolygon($img, $points_cut, 3, $blue);

    // Сохраняем PNG
    $filename = $outputDir . "icon-{$size}x{$size}.png";
    imagepng($img, $filename, 9);
    imagedestroy($img);

    echo "Создан: icon-{$size}x{$size}.png<br>";
}

echo "<br><strong>Готово!</strong> Иконки сгенерированы. Удалите этот файл.";
