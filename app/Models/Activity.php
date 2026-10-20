<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['code', 'name', 'title', 'days', 'rate', 'quota', 'decree_total', 'start_date', 'position', 'active', 'manual', 'note_number'];
protected $casts = ['start_date' => 'date', 'active' => 'boolean', 'manual' => 'boolean'];

    public function getAmountAttribute(): int { return (int)$this->days * (int)$this->rate; }
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
