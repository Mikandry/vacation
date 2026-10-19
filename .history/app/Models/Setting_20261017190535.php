<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'direction' => 'DEXAMC',
        'session' => '2026',
        'ministry' => "MINISTERE DE L’EDUCATION NATIONALE",
        'decision_number' => '          -2026/MEN',
        'place' => 'Antananarivo',
        'decision_date' => '',
        'authority' => "LE MINISTRE DE L’EDUCATION NATIONALE,",
        'signatory' => "Le Ministre de l’Education Nationale",
        'ref_arrete' => "l’Arrêté Interministériel N°16166/2025 du 19 juin 2025",
        'budget_mission' => '812',
        'budget_indicator' => '314',
        'budget_section' => '00.81.0. E43.00000',
        'budget_account' => '6031',
        'budget_ordsec' => '00.812.2.00000',
        'auto' => 'OUI',          // répartition automatique
        'use_history' => 'OUI',   // tenir compte de l'historique
        'tolerance' => '20',      // % autour de la moyenne
        'threshold_ok' => '90',   // % d'agents dans la tolérance => ÉQUILIBRÉ
        'threshold_acc' => '70',  // => ACCEPTABLE
        'band_step' => '250000',
        'services' => "SDC/DEXAMC|Service des Diplômes et de la Certification\nBAG/DEXAMC|Bureau des Affaires Générales\nSEE/DEXAMC|Service de l’Evaluation des Epreuves\nSOE/DEXAMC|Service de l’Organisation des Examens",
    ];

    public static function all_(): array
    {
        return array_merge(self::DEFAULTS, static::query()->pluck('value', 'key')->map(fn($v) => (string)$v)->all());
    }
    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            if (array_key_exists($k, self::DEFAULTS)) static::updateOrCreate(['key' => $k], ['value' => (string)$v]);
        }
    }
}
