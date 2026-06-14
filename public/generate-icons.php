<?php
/**
 * Генерация PNG-иконок для PWA из SVG
 * Запустите один раз: https://unique-style.ru/traking/generate-icons.php
 * После генерации файл можно удалить.
 */

// Проверяем наличие GD
if (!extension_loaded('gd')) {
    die('Расширение GD не установлено на сервере');
}

$sizes = [192, 512];
$outputDir = __DIR__ . '/icons/';

foreach ($sizes as $size) {
    // Создаём изображение
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // Фон — синий цвет (#5B7FE8) с закруглением (рисуем как прямоугольник, закругление в manifest)
    $bg = imagecolorallocate($img, 91, 127, 232);
    $white = imagecolorallocate($img, 255, 255, 255);

    // Заполняем фон
    imagefill($img, 0, 0, $bg);

    // Рисуем букву "T" по центру
    $fontSize = (int) ($size * 0.55);
    $fontFile = null;

    // Попытка найти системный шрифт
    $fontPaths = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
        'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    ];

    foreach ($fontPaths as $path) {
        if (file_exists($path)) {
            $fontFile = $path;
            break;
        }
    }

    if ($fontFile) {
        // С TTF-шрифтом
        $bbox = imagettfbbox($fontSize, 0, $fontFile, 'T');
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $x = (int) (($size - $textWidth) / 2) - $bbox[0];
        $y = (int) (($size - $textHeight) / 2) + $textHeight - $bbox[1];
        imagettftext($img, $fontSize, 0, $x, $y, $white, $fontFile, 'T');
    } else {
        // Без TTF — рисуем простую "T" линиями
        $thick = (int) ($size * 0.12);
        $margin = (int) ($size * 0.2);

        // Горизонтальная часть T
        imagefilledrectangle($img, $margin, $margin, $size - $margin, $margin + $thick, $white);
        // Вертикальная часть T
        $cx = (int) ($size / 2);
        imagefilledrectangle($img, $cx - (int)($thick/2), $margin, $cx + (int)($thick/2), $size - $margin, $white);
    }

    // Сохраняем
    $filename = $outputDir . "icon-{$size}x{$size}.png";
    imagepng($img, $filename);
    imagedestroy($img);

    echo "Создан: {$filename} ({$size}x{$size})<br>";
}

echo "<br>Готово! Теперь удалите этот файл: generate-icons.php";
