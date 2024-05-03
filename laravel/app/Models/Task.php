<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'user_id',
        'estimated_time',
        'used_time',
        'created_at',
        'completed_at',
    ];

    /**
     * Get the user that is associated to the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}