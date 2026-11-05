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
    ) {}
}
