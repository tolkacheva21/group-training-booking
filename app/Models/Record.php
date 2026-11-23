<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Record extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'workout_id',
        'user_id',
        'status_id',
        'payment_status',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'payment_status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected $attributes = [
        'payment_status' => 'unpaid',
    ];

    // запись принадлежит одной тренировке
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    // запись принаджелит одному пользователю
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // запись имеет один статус: не подтверждена, подтверждена или завершена
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function hasStatus(string $name): bool
    {
        return $this->status?->name === $name;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isPaymentCancelled(): bool
    {
        return $this->payment_status === PaymentStatus::Cancelled;
    }

    /**
     * Статус оплаты для отображения в интерфейсе.
     */
    public function paymentLabel(): string
    {
        return $this->payment_status?->label() ?? PaymentStatus::Unpaid->label();
    }
}
