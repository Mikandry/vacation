<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['code', 'name', 'title', 'days', 'rate', 'quota', 'decree_total', 'start_date', 'position', 'active', 'manual', 'note_number', 'subgroups'];
protected $casts = ['start_date' => 'date', 'active' => 'boolean', 'manual' => 'boolean', 'subgroups' => 'array'];

    public function getAmountAttribute(): int { return (int)$this->days * (int)$this->rate; }

    /** Sous-groupes de la quarantaine, dans l'ordre d'impression. */
    public const SUBGROUPS = [
        'OG' => 'Organisation générale',
        'SUP' => 'Superviseur',
        'COORD' => 'Coordonateur',
        'SP' => 'Sous pli',
    ];

    /** Vrai si l'activité est la quarantaine (détectée sur le code ou le nom). */
    public function getIsQuarantineAttribute(): bool
    {
        $t = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($this->code . ' ' . $this->name));
        return str_contains($t, 'quarantaine');
    }

    /** Effectif prévu par sous-groupe : ['OG'=>n, 'SUP'=>n, 'COORD'=>n, 'SP'=>n]. */
    public function getSubgroupCountsAttribute(): array
    {
        $raw = (array) ($this->subgroups ?? []);
        $out = [];
        foreach (array_keys(self::SUBGROUPS) as $k) $out[$k] = max(0, (int) ($raw[$k] ?? 0));
        return $out;
    }

    /** Nombre total d'agents de l'activité : plafond de la somme des sous-groupes. */
    public function getAgentCapAttribute(): int
    {
        return (int) ($this->decree_total > 0 ? $this->decree_total : $this->quota);
    }

    public function agentSubgroups() { return $this->hasMany(AgentSubgroup::class); }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function exceptions() { return $this->hasMany(AgentException::class); }
    public function agents() { return $this->belongsToMany(Agent::class, 'assignments')->withPivot('forced'); }
        /** Dernier jour de l'activité (début + jours - 1), ou null si pas de date de début. */
    public function getEndDateAttribute()
    {
        return $this->start_date ? $this->start_date->copy()->addDays(max(0, (int) $this->days - 1)) : null;
    }

    /** Période commune avec une autre activité : [du, au] ou null. */
    public function overlap(Activity $o): ?array
    {
        if (!$this->start_date || !$o->start_date) return null;
        $from = $this->start_date->copy()->max($o->start_date);
        $to = $this->end_date->copy()->min($o->end_date);
        return $from->lte($to) ? [$from, $to] : null;
    }
}
