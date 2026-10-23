<?php
namespace App\Support;

class Logo
{
    /** Vrai si le vrai logo (public/images/logo-madagascar.png) est présent. */
    public static function isReal(): bool
    {
        return is_file(public_path('images/logo-madagascar.png'));
    }

    /** Chemin du logo, sinon drapeau dessiné. */
    public static function file(): string
    {
        return self::isReal() ? public_path('images/logo-madagascar.png') : Flag::file();
    }

    public static function uri(): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents(self::file()));
    }

    /** Rapport largeur / hauteur de l'image utilisée. */
    public static function ratio(): float
    {
        return self::isReal() ? 268 / 155 : 1.5;
    }
}
