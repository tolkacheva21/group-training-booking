<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    public const Pending = 'pending';

    public const Confirmed = 'confirmed';

    public const Completed = 'completed';

    public const Rejected = 'rejected';

    public $timestamps = false;

    protected $fillable = ['name', 'title'];

    public static function idOf(string $name): int
    {
        return static::where('name', $name)->firstOrFail()->id;
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }
}
