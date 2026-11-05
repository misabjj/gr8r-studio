<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'name', 'key', 'description', 'color', 'status', 'is_favorite', 'start_date', 'due_date'];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'is_favorite' => 'boolean',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Active->value);
    }

    public function scopeFavorited(Builder $query): Builder
    {
        return $query->where('is_favorite', true);
    }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->whereBelongsTo($owner, 'owner');
    }
}
