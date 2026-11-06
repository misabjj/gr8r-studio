<?php

namespace App\Data\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;

class UpdateTaskData
{
    public function __construct(
        public readonly bool $updatesTitle = false,
        public readonly ?string $title = null,
        public readonly bool $updatesDescription = false,
        public readonly ?string $description = null,
        public readonly bool $updatesProjectId = false,
        public readonly ?int $projectId = null,
        public readonly bool $updatesStatus = false,
        public readonly ?TaskStatus $status = null,
        public readonly bool $updatesPriority = false,
        public readonly ?TaskPriority $priority = null,
        public readonly bool $updatesDueDate = false,
        public readonly ?string $dueDate = null,
        public readonly bool $updatesAssignee = false,
        public readonly ?int $assigneeId = null,
        public readonly bool $updatesStartDate = false,
        public readonly ?string $startDate = null,
        public readonly bool $updatesLabels = false,
        public readonly ?array $labels = null,
        public readonly bool $updatesRecurrence = false,
        public readonly ?string $recurrence = null,
        public readonly bool $updatesEstimate = false,
        public readonly ?string $estimate = null,
        public readonly bool $updatesDependencies = false,
        public readonly ?array $dependencies = null,
        public readonly bool $updatesSubtasks = false,
        public readonly ?array $subtasks = null,
    ) {}
}
