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

    protected $fillable = ['source_key', 'project_id', 'assignee_id', 'title', 'description', 'status', 'priority', 'is_favorite', 'is_archived', 'archived_at', 'start_date', 'due_date', 'completed_at', 'labels', 'subtasks', 'attachments', 'dependencies', 'estimate', 'sort_order', 'recurrence'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_date' => 'date',
            'completed_at' => 'datetime',
            'is_favorite' => 'boolean',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'labels' => 'array',
            'subtasks' => 'array',
            'attachments' => 'array',
            'dependencies' => 'array',
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

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }
}
