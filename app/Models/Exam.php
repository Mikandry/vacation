<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['code', 'name', 'session', 'ref_arrete', 'decision_number', 'position'];
    public function activities() { return $this->hasMany(Activity::class); }
}
