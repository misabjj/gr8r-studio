<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'assignee_id', 'title', 'description', 'status', 'priority', 'is_favorite', 'start_date', 'due_date', 'completed_at'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_date' => 'date',
            'completed_at' => 'datetime',
            'is_favorite' => 'boolean',
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', TaskStatus::Done);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Done);
    }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->whereBelongsTo($owner, 'owner'));
    }

    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user, 'assignee');
    }

    public function scopeFavorited(Builder $query): Builder
    {
        return $query->where('is_favorite', true);
    }
}
