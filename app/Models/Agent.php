<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = ['name', 'im', 'locality', 'type', 'available', 'corps', 'grade', 'indice', 'function', 'history_amount', 'draw_order', 'notes'];
    protected $casts = ['available' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Agent $a) {
            if (!$a->draw_order) $a->draw_order = random_int(1, 100000);
            if (!$a->type) $a->type = 'DIRECTION';
        });
    }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function exceptions() { return $this->hasMany(AgentException::class); }
    public function activities() { return $this->belongsToMany(Activity::class, 'assignments')->withPivot('forced'); }
    public function isDirection(): bool { return $this->type === 'DIRECTION'; }
}
