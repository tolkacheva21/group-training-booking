<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['trainer_id', 'room_id', 'workout_start', 'workout_end'];

    //обрабатывать как объекты дата и время
    protected $casts = [
        'workout_start' => 'datetime',
        'workout_end' => 'datetime',
    ];

    //тренировка принадлежит тренеру, тренер может иметь много тренировок
    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    //тренировка проводится в одном зале, зал может иметь много тренировок
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    //тренировка может иметь много записей
    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
