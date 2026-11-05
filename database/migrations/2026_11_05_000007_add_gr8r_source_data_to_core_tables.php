<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('source_key')->nullable()->unique();
            $table->string('role')->nullable();
            $table->string('team')->nullable();
            $table->string('title')->nullable();
            $table->string('color', 16)->nullable();
            $table->string('status')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->string('timezone')->nullable();
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->string('source_key')->nullable()->unique();
            $table->string('icon')->nullable();
            $table->string('team')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_private')->default(false);
        });

        Schema::create('project_user', function (Blueprint $table): void {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'user_id']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('source_key')->nullable()->unique();
            $table->string('key')->nullable()->unique();
            $table->json('labels')->nullable();
            $table->json('subtasks')->nullable();
            $table->json('attachments')->nullable();
            $table->json('dependencies')->nullable();
            $table->string('estimate')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->string('recurrence')->nullable();
        });

        Schema::create('workspace_demo_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->json('source_data');
            $table->timestamp('source_generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_demo_snapshots');
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['source_key', 'key', 'labels', 'subtasks', 'attachments', 'dependencies', 'estimate', 'sort_order', 'recurrence']);
        });
        Schema::dropIfExists('project_user');
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign(['lead_id']);
            $table->dropUnique(['source_key']);
            $table->dropColumn(['source_key', 'icon', 'team', 'lead_id', 'is_private']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['source_key', 'role', 'team', 'title', 'color', 'status', 'last_active_at', 'timezone']);
        });
    }
};
