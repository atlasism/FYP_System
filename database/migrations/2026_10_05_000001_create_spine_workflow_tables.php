<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('project_group_no')->nullable();
            $table->boolean('is_panel_choice')->default(false);
            $table->dateTime('panel_choice_at')->nullable();
            $table->unsignedInteger('created_by')->unique();
            $table->unsignedInteger('supervisor_id')->nullable();
            $table->unsignedInteger('student_id')->nullable();
            $table->string('title');
            $table->string('department', 30)->default('JTMK');
            $table->string('program_name', 120)->default('JTMK - Information Technology');
            $table->string('course_code', 20)->default('DFT50114');
            $table->string('category', 100);
            $table->string('session', 50);
            $table->string('group_password', 100)->nullable();
            $table->text('description');
            $table->string('project_number', 20)->nullable()->unique();
            $table->string('status', 30)->default('Submitted');
            $table->boolean('is_complete_for_evaluation')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->index('project_group_no');
            $table->index('is_panel_choice');
        }); }

        if (! Schema::hasTable('project_members')) {
        Schema::create('project_members', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('student_id')->unique();
            $table->string('role', 20)->default('Member');
            $table->unsignedTinyInteger('member_order')->default(0);
            $table->timestamp('joined_at')->useCurrent();
            $table->index('project_id');
        }); }

        if (! Schema::hasTable('project_documents')) {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->string('doc_type', 50);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('status', 20)->default('Pending');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->index('project_id');
        }); }

        if (! Schema::hasTable('project_marks')) {
        Schema::create('project_marks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            foreach (['proposal_presentation', 'demonstration_1', 'demonstration_2', 'demonstration_3', 'final_poster', 'final_presentation', 'log_book', 'technical_report', 'proposal', 'final_report', 'slides', 'poster', 'source_code', 'system_image', 'other_docs', 'total_score'] as $column) {
                $table->decimal($column, 5, 2)->default(0);
            }
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        }); }

        if (! Schema::hasTable('supervisor_students')) {
        Schema::create('supervisor_students', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('supervisor_id');
            $table->unsignedInteger('student_id');
            $table->string('session', 50);
            $table->timestamp('created_at')->useCurrent();
        }); }

        if (! Schema::hasTable('panel_sessions')) {
        Schema::create('panel_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->char('token', 64)->unique();
            $table->unsignedInteger('created_by');
            $table->string('panel_staff_id', 30)->nullable();
            $table->string('panel_name', 150)->nullable();
            $table->string('panel_email', 190)->nullable();
            $table->string('status', 20)->default('Active');
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('submitted_at')->nullable();
            $table->index('project_id');
        }); }

        if (! Schema::hasTable('panel_session_projects')) {
        Schema::create('panel_session_projects', function (Blueprint $table) {
            $table->unsignedInteger('panel_session_id');
            $table->unsignedInteger('project_id');
            $table->primary(['panel_session_id', 'project_id']);
            $table->index('project_id');
        }); }

        if (! Schema::hasTable('panel_evaluations')) {
        Schema::create('panel_evaluations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('panel_session_id');
            $table->unsignedInteger('project_id');
            $table->string('panel_staff_id', 30)->nullable();
            $table->string('panel_name', 150);
            $table->string('panel_email', 190)->nullable();
            $table->string('assessor_types')->nullable();
            $table->longText('student_scores_json');
            $table->text('comments')->nullable();
            $table->decimal('average_score', 6, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['panel_session_id', 'project_id']);
            $table->index('project_id');
        }); }

        if (! Schema::hasTable('panel_student_marks')) {
        Schema::create('panel_student_marks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('panel_evaluation_id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('student_id');
            $table->decimal('total_score', 6, 2)->default(0);
            $table->decimal('demo3_score', 5, 2)->default(0);
            $table->unique(['panel_evaluation_id', 'student_id']);
            $table->index('project_id');
        }); }

        if (! Schema::hasTable('student_demo_status')) {
        Schema::create('student_demo_status', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('supervisor_id');
            $table->unsignedInteger('student_id');
            $table->string('demo_type', 20);
            $table->string('status', 20)->default('Pending');
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['supervisor_id', 'student_id', 'demo_type']);
            $table->index('student_id');
        }); }

        if (! Schema::hasTable('submission_deadlines')) {
        Schema::create('submission_deadlines', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
        }); }

        if (! Schema::hasTable('supervisor_logbook')) {
        Schema::create('supervisor_logbook', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('supervisor_id');
            $table->unsignedInteger('student_id');
            $table->unsignedTinyInteger('week_no');
            $table->boolean('is_verified')->default(false);
            $table->dateTime('verified_at')->nullable();
            $table->unique(['supervisor_id', 'student_id', 'week_no']);
            $table->index('student_id');
        }); }

        if (! Schema::hasTable('system_settings')) {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting_key', 50)->unique();
            $table->text('setting_value')->nullable();
        }); }
    }

    public function down(): void
    {
        // Legacy project tables predate Laravel in an imported database.
        // Keep them on rollback rather than risk deleting assessment records.
    }
};
