<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivitySubgroup extends Model
{
    public $timestamps = false;
    protected $fillable = ['activity_id', 'name', 'planned', 'position'];
    public function activity() { return $this->belongsTo(Activity::class); }
}
