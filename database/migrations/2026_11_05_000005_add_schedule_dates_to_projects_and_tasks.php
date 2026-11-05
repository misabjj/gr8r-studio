<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('status');
            $table->date('due_date')->nullable()->after('start_date');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('start_date');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['start_date', 'due_date']);
        });
    }
};
