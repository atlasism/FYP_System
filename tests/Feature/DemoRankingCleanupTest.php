<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoRankingCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_removes_only_the_identified_demo_marks_and_keeps_real_marks(): void
    {
        Storage::fake('local');

        DB::table('panel_evaluations')->insert([
            [
                'id' => 3, 'panel_session_id' => 12, 'project_id' => 1,
                'panel_name' => 'Demo Assessor', 'panel_email' => 'aliexpress@abumail.com',
                'student_scores_json' => '{}', 'created_at' => '2026-09-27 00:26:03',
            ],
            [
                'id' => 7, 'panel_session_id' => 22, 'project_id' => 2,
                'panel_name' => 'Real Assessor', 'panel_email' => 'real@example.test',
                'student_scores_json' => '{}', 'created_at' => '2026-10-08 00:26:03',
            ],
        ]);
        DB::table('panel_student_marks')->insert([
            ['panel_evaluation_id' => 3, 'project_id' => 1, 'student_id' => 1, 'total_score' => 84.38, 'demo3_score' => 12.66],
            ['panel_evaluation_id' => 7, 'project_id' => 2, 'student_id' => 2, 'total_score' => 90, 'demo3_score' => 13.50],
        ]);
        DB::table('project_marks')->insert([
            ['project_id' => 1, 'total_score' => 12.50, 'updated_at' => '2026-09-27 00:26:03'],
            ['project_id' => 2, 'total_score' => 13.50, 'updated_at' => '2026-10-08 00:26:03'],
        ]);

        $migration = require database_path('migrations/2026_10_05_000003_remove_demo_panel_scores.php');
        $migration->up();

        $this->assertDatabaseMissing('panel_evaluations', ['id' => 3]);
        $this->assertDatabaseMissing('panel_student_marks', ['panel_evaluation_id' => 3]);
        $this->assertDatabaseMissing('project_marks', ['project_id' => 1]);
        $this->assertDatabaseHas('panel_evaluations', ['id' => 7]);
        $this->assertDatabaseHas('panel_student_marks', ['panel_evaluation_id' => 7]);
        $this->assertDatabaseHas('project_marks', ['project_id' => 2]);
        $this->assertCount(1, Storage::disk('local')->files('maintenance'));
    }
}
