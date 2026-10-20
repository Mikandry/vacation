<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConflictWaiver extends Model
{
    public $timestamps = false;
    protected $fillable = ['agent_id', 'activity_a_id', 'activity_b_id'];
    public function agent() { return $this->belongsTo(Agent::class); }
}