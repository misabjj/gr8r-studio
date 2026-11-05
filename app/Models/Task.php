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

    protected $fillable = ['project_id', 'title', 'description', 'status', 'priority', 'due_date', 'completed_at'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
}
