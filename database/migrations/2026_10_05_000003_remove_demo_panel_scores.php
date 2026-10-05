<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // These four September assessments were entered to demonstrate the
        // ranking before any real panel evaluation took place.
        $demoEvaluations = [
            3 => ['panel_session_id' => 12, 'project_id' => 1, 'panel_email' => 'aliexpress@abumail.com'],
            4 => ['panel_session_id' => 14, 'project_id' => 1, 'panel_email' => 'abumail@aliexpress.com'],
            5 => ['panel_session_id' => 12, 'project_id' => 9, 'panel_email' => 'aliexpress@abumail.com'],
            6 => ['panel_session_id' => 17, 'project_id' => 5, 'panel_email' => 'abumail@aliexpress.com'],
        ];
        $demoProjectTotals = [1 => '12.50', 9 => '12.97', 5 => '10.78'];

        $evaluations = DB::table('panel_evaluations')->whereIn('id', array_keys($demoEvaluations))->get()
            ->filter(function ($evaluation) use ($demoEvaluations) {
                $expected = $demoEvaluations[$evaluation->id];

                return (int) $evaluation->panel_session_id === $expected['panel_session_id']
                    && (int) $evaluation->project_id === $expected['project_id']
                    && $evaluation->panel_email === $expected['panel_email'];
            })->values();

        if ($evaluations->isEmpty()) {
            return;
        }

        $evaluationIds = $evaluations->pluck('id')->all();
        $studentMarks = DB::table('panel_student_marks')->whereIn('panel_evaluation_id', $evaluationIds)->get();
        $projectIds = $evaluations->pluck('project_id')->unique()->all();
        $demoUpdatedAt = $evaluations->groupBy('project_id')
            ->map(fn ($rows) => $rows->max('created_at'));
        $projectMarks = DB::table('project_marks')->whereIn('project_id', $projectIds)->get()
            ->filter(fn ($mark) => isset($demoProjectTotals[$mark->project_id])
                && (float) $mark->total_score === (float) $demoProjectTotals[$mark->project_id]
                && (string) $mark->updated_at === (string) $demoUpdatedAt[$mark->project_id])
            ->values();

        $backup = json_encode([
            'database' => DB::connection()->getDatabaseName(),
            'evaluations' => $evaluations,
            'student_marks' => $studentMarks,
            'project_marks' => $projectMarks,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $path = 'maintenance/demo-panel-scores-'.now()->format('Ymd-His').'.json';
        if (! Storage::disk('local')->put($path, $backup)) {
            throw new RuntimeException('Could not back up the demo panel scores. No scores were removed.');
        }

        DB::transaction(function () use ($evaluationIds, $projectMarks) {
            DB::table('panel_student_marks')->whereIn('panel_evaluation_id', $evaluationIds)->delete();
            DB::table('panel_evaluations')->whereIn('id', $evaluationIds)->delete();

            foreach ($projectMarks as $mark) {
                if (! DB::table('panel_evaluations')->where('project_id', $mark->project_id)->exists()) {
                    DB::table('project_marks')->where('project_id', $mark->project_id)
                        ->where('total_score', $mark->total_score)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Restoring a migration must never reintroduce demonstration scores.
        // The private JSON backup can be used for a deliberate manual restore.
    }
};
