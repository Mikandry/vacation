<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AgentSubgroup extends Model
{
    public $timestamps = false;
    protected $fillable = ['agent_id', 'activity_id', 'subgroup'];
    public function agent() { return $this->belongsTo(Agent::class); }
    public function activity() { return $this->belongsTo(Activity::class); }
}
