<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('panel_sessions', 'expected_panel_count')) {
            Schema::table('panel_sessions', function (Blueprint $table): void {
                $table->unsignedTinyInteger('expected_panel_count')->default(1)->after('panel_staff_id');
            });
        }

        if (! Schema::hasTable('panel_assessors')) {
            Schema::create('panel_assessors', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('panel_session_id');
                $table->string('panel_name', 150);
                $table->string('panel_email', 190)->nullable();
                $table->enum('status', ['Active', 'Submitted'])->default('Active');
                $table->timestamp('created_at')->useCurrent();
                $table->dateTime('submitted_at')->nullable();
                $table->unique(['panel_session_id', 'panel_name'], 'uq_panel_assessor_name');
            });
        }

        if (! Schema::hasTable('panel_session_choices')) {
            Schema::create('panel_session_choices', function (Blueprint $table): void {
                $table->unsignedInteger('panel_session_id');
                $table->unsignedInteger('project_id');
                $table->dateTime('selected_at')->useCurrent();
                $table->primary(['panel_session_id', 'project_id']);
                $table->index('project_id', 'idx_panel_session_choices_project');
            });
        }

        if (! Schema::hasColumn('panel_evaluations', 'panel_assessor_id')) {
            Schema::table('panel_evaluations', function (Blueprint $table): void {
                $table->unsignedInteger('panel_assessor_id')->nullable()->after('panel_session_id');
            });
        }

        $indexes = collect(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM panel_evaluations'))
            ->pluck('Key_name')->unique()->all();
        // Keep the foreign key supported after replacing the old composite unique index.
        if (! in_array('idx_panel_eval_session', $indexes, true)) {
            Schema::table('panel_evaluations', fn (Blueprint $table) => $table->index('panel_session_id', 'idx_panel_eval_session'));
        }
        if (in_array('uq_panel_evaluation_session_project', $indexes, true)) {
            Schema::table('panel_evaluations', fn (Blueprint $table) => $table->dropUnique('uq_panel_evaluation_session_project'));
        }
        if (! in_array('uq_panel_eval_assessor_project', $indexes, true)) {
            Schema::table('panel_evaluations', fn (Blueprint $table) => $table->unique(['panel_assessor_id', 'project_id'], 'uq_panel_eval_assessor_project'));
        }
    }

    public function down(): void
    {
        // Preserve panel sessions and evaluation records if this migration is rolled back.
    }
};
