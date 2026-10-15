<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use HasFactory;
    protected $timestamps = false;
    protected $fillable = ['trainer_id', 'room_id', 'workout_start', 'workout_end'];
}
