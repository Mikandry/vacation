<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AgentException extends Model
{
    protected $table = 'exceptions';
    public $timestamps = false;
    protected $fillable = ['agent_id', 'activity_id', 'mode', 'reason'];
    public function agent() { return $this->belongsTo(Agent::class); }
    public function activity() { return $this->belongsTo(Activity::class); }
}
