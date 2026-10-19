<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['code', 'name', 'title', 'days', 'rate', 'quota', 'decree_total', 'start_date', 'position', 'active'];
    protected $casts = ['start_date' => 'date', 'active' => 'boolean'];

    public function getAmountAttribute(): int { return (int)$this->days * (int)$this->rate; }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function exceptions() { return $this->hasMany(AgentException::class); }
    public function agents() { return $this->belongsToMany(Agent::class, 'assignments')->withPivot('forced'); }
}
