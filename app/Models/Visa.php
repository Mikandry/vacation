<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Visa extends Model
{
    public $timestamps = false;
    protected $fillable = ['text', 'included', 'position'];
    protected $casts = ['included' => 'boolean'];
}
