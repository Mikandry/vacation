<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['code', 'name', 'title', 'days', 'rate', 'quota', 'decree_total', 'start_date', 'position', 'active', 'manual', 'note_number', 'exam_id'];
protected $casts = ['start_date' => 'date', 'active' => 'boolean', 'manual' => 'boolean'];

    public function getAmountAttribute(): int { return (int)$this->days * (int)$this->rate; }

    /** Modèles de sous-groupes proposés dans le formulaire (le bouton remplit la liste, vous la modifiez ensuite). */
    public const TEMPLATES = [
        'Quarantaine' => ['Organisation générale', 'Superviseur', 'Coordonateur', 'Sous pli'],
        'Dispatching' => ['Agent de dispatching', 'Agent de sécurité'],
        'Correction' => ['Correcteur', 'Agent de secrétariat'],
    ];

    public function exam() { return $this->belongsTo(Exam::class); }

    /** Sous-groupes libres de l'activité, dans l'ordre d'impression. */
    public function groups() { return $this->hasMany(ActivitySubgroup::class)->orderBy('position')->orderBy('id'); }

    /** [id du sous-groupe => nom], vide si l'activité n'est pas découpée. */
    public function getSubgroupDefsAttribute(): array
    {
        return $this->exists ? $this->groups->pluck('name', 'id')->all() : [];
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

    /** Effectif prévu par sous-groupe (0 = pas de limite) : [id => n]. */
    public function getSubgroupCountsAttribute(): array
    {
        return $this->exists ? $this->groups->pluck('planned', 'id')->map(fn($n) => (int) $n)->all() : [];
    }

    /** Nom affiché : « Quarantaine (BEPC) » — le code de l'examen est ajouté s'il n'est pas déjà dans le nom. */
    public function getDisplayNameAttribute(): string
    {
        $name = (string) $this->name;
        $code = (string) optional($this->exam)->code;
        if ($code !== '' && !str_contains(mb_strtoupper($name), mb_strtoupper($code))) $name .= ' (' . $code . ')';
        return $name;
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
