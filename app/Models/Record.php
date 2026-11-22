<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['workout_id', 'user_id', 'status_id'];

    // запись принадлежит одной тренировке
    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    // запись принаджелит одному пользователю
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // запись имеет один статус: не подтверждена, подтверждена или завершена
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function hasStatus(string $name): bool
    {
        return $this->status?->name === $name;
    }
}
