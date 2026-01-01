<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_draft',
        'published_at',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_draft', false)
            ->where('published_at', '<=', now());
    }

    public function scopeScheduled(Builder $query): void
    {
        $query->where('is_draft', false)
            ->where('published_at', '>', now());
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('is_draft', true);
    }

    public function isActive(): bool
    {
        return ! $this->is_draft && $this->published_at && $this->published_at <= now();
    }

    public function isScheduled(): bool
    {
        return ! $this->is_draft && $this->published_at && $this->published_at > now();
    }

    public function isDraft(): bool
    {
        return $this->is_draft;
    }
}
