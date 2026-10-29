<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['code', 'name', 'title', 'days', 'rate', 'quota', 'decree_total', 'start_date', 'position', 'active', 'manual', 'note_number', 'subgroups', 'group_type'];
protected $casts = ['start_date' => 'date', 'active' => 'boolean', 'manual' => 'boolean', 'subgroups' => 'array'];

    public function getAmountAttribute(): int { return (int)$this->days * (int)$this->rate; }

    /** Types d'activité découpés en sous-groupes : clé => [clé du sous-groupe => libellé], dans l'ordre d'impression. */
    public const KINDS = [
        'QUARANTAINE' => ['OG' => 'Organisation générale', 'SUP' => 'Superviseur', 'COORD' => 'Coordonateur', 'SP' => 'Sous pli'],
        'DISPATCHING' => ['DISP' => 'Agent de dispatching', 'SECU' => 'Agent de sécurité'],
        'CORRECTION' => ['CORR' => 'Correcteur', 'SECR' => 'Agent de secrétariat'],
    ];
    public const KIND_LABELS = [
        'QUARANTAINE' => 'Quarantaine (OG, Superviseur, Coordonateur, Sous pli)',
        'DISPATCHING' => 'Dispatching (Agent de dispatching, Agent de sécurité)',
        'CORRECTION' => 'Correction (Correcteur, Agent de secrétariat)',
    ];

    /** Type de sous-groupes de l'activité (choisi dans le formulaire, sinon deviné d'après le nom), ou null. */
    public function getGroupKindAttribute(): ?string
    {
        $gt = (string) ($this->group_type ?? '');
        if ($gt === 'NONE') return null;
        if (isset(self::KINDS[$gt])) return $gt;
        $name = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim((string) $this->name)));
        $code = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim((string) $this->code)));
        if (str_contains($name . ' ' . $code, 'quarantaine')) return 'QUARANTAINE';
        if (str_contains($name . ' ' . $code, 'dispatching')) return 'DISPATCHING';
        if (str_starts_with($name, 'correction') || str_starts_with($code, 'correction')) return 'CORRECTION';
        return null;
    }

    /** Sous-groupes de cette activité : [clé => libellé], vide si l'activité n'est pas découpée. */
    public function getSubgroupDefsAttribute(): array
    {
        return self::KINDS[$this->group_kind] ?? [];
    }

    public function getHasSubgroupsAttribute(): bool
    {
        return count($this->subgroup_defs) > 0;
    }

    /** Ancien nom, conservé : vrai si l'activité est découpée en sous-groupes. */
    public function getIsQuarantineAttribute(): bool
    {
        return $this->has_subgroups;
    }

    /** Effectif prévu par sous-groupe (0 = pas de limite) : ['OG' => n, …]. */
    public function getSubgroupCountsAttribute(): array
    {
        $raw = (array) ($this->subgroups ?? []);
        $out = [];
        foreach (array_keys($this->subgroup_defs) as $k) $out[$k] = max(0, (int) ($raw[$k] ?? 0));
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
