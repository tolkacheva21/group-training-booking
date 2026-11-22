<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['trainer_id', 'room_id', 'inventory_id', 'inventory_quantity', 'workout_start', 'workout_end'];

    // обрабатывать как объекты дата и время
    protected $casts = [
        'workout_start' => 'datetime',
        'workout_end' => 'datetime',
    ];

    // тренировка принадлежит тренеру, тренер может иметь много тренировок
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    // тренировка проводится в одном зале, зал может иметь много тренировок
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // тренировка может использовать инвентарь
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    // тренировка может иметь много записей
    public function records()
    {
        return $this->hasMany(Record::class);
    }

    // места в зале занимают подтверждённые и завершённые записи
    public function occupyingRecords()
    {
        return $this->records()->whereHas(
            'status',
            fn ($query) => $query->whereIn('name', [Status::Confirmed, Status::Completed])
        );
    }
}
