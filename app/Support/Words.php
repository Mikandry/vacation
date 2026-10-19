<?php
namespace App\Support;

/** Nombres en lettres (français), jusqu'à 999 999 999. */
class Words
{
    private const U = ['zéro','un','deux','trois','quatre','cinq','six','sept','huit','neuf','dix','onze','douze','treize','quatorze','quinze','seize','dix-sept','dix-huit','dix-neuf'];
    private const T = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    private static function b100(int $n, bool $final): string
    {
        if ($n < 20) return self::U[$n];
        [$t, $u] = [intdiv($n, 10), $n % 10];
        if (isset(self::T[$t])) return self::T[$t] . ($u === 0 ? '' : ($u === 1 ? ' et un' : '-' . self::U[$u]));
        if ($t === 7) return $u === 0 ? 'soixante-dix' : ($u === 1 ? 'soixante et onze' : 'soixante-' . self::U[10 + $u]);
        if ($t === 8) return $u === 0 ? 'quatre-vingt' . ($final ? 's' : '') : 'quatre-vingt-' . self::U[$u];
        return 'quatre-vingt-' . self::U[10 + $u];
    }
    private static function b1000(int $n, bool $final): string
    {
        [$h, $r] = [intdiv($n, 100), $n % 100];
        if ($h === 0) return self::b100($r, $final);
        $s = ($h === 1 ? 'cent' : self::b100($h, false) . ' cent') . ($r === 0 && $final && $h > 1 ? 's' : '');
        return $r ? $s . ' ' . self::b100($r, $final) : $s;
    }
    public static function of(int $n): string
    {
        if ($n === 0) return 'zéro';
        $m = intdiv($n, 1000000); $t = intdiv($n % 1000000, 1000); $r = $n % 1000;
        $p = [];
        if ($m) $p[] = self::b1000($m, false) . ' million' . ($m > 1 ? 's' : '');
        if ($t) $p[] = $t === 1 ? 'mille' : self::b1000($t, false) . ' mille';
        if ($r) $p[] = self::b1000($r, true);
        return implode(' ', $p);
    }
}
