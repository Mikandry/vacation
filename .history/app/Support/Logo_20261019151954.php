<?php
namespace App\Support;

class Logo
{
    /** Chemin du logo (public/images/logo-madagascar.png), sinon drapeau dessiné. */
    public static function file(): string
    {
        $p = public_path('images/logo-madagascar.png');
        return is_file($p) ? $p : Flag::file();
    }

    public static function uri(): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents(self::file()));
    }
}