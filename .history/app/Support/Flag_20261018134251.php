<?php
namespace App\Support;

class Flag
{
    /** Drapeau de Madagascar (blanc à gauche, rouge au-dessus de vert) en PNG. */
    public static function png(int $w = 300, int $h = 200): string
    {
        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $red = imagecolorallocate($im, 252, 61, 50);
        $green = imagecolorallocate($im, 0, 126, 58);
        $grey = imagecolorallocate($im, 190, 190, 190);
        $x = (int) round($w / 3);
        imagefilledrectangle($im, 0, 0, $w, $h, $white);
        imagefilledrectangle($im, $x, 0, $w, (int) ($h / 2) - 1, $red);
        imagefilledrectangle($im, $x, (int) ($h / 2), $w, $h, $green);
        imagerectangle($im, 0, 0, $w - 1, $h - 1, $grey);
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        return $data;
    }

    public static function dataUri(): string
    {
        return 'data:image/png;base64,' . base64_encode(self::png());
    }

    /** Fichier PNG sur disque (pour Word et Excel). */
    public static function file(): string
    {
        $p = storage_path('app/flag-mg.png');
        if (!is_file($p)) file_put_contents($p, self::png());
        return $p;
    }
}