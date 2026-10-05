<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The supplied DFT50194 list orders these same student teams from 1 to 18.
        // The imported application records use DFT50114, so match teams by a member
        // rather than by local project IDs or their old group numbers.
        $anchors = [
            1 => ['34DIT24F1038', '34DIT24D1038'],
            2 => ['34DIT24F1031'],
            3 => ['34DIT24F1064'],
            4 => ['34DIT24F1024'],
            5 => ['34DIT24F1044'],
            6 => ['34DIT24F1047'],
            7 => ['34DIT24F1006'],
            8 => ['34DIT24F1018'],
            9 => ['34DIT24F1007'],
            10 => ['34DIT24F1011'],
            11 => ['34DIT24F1042'],
            12 => ['34DIT24F1049'],
            13 => ['34DIT24F1052'],
            14 => ['34DIT24F1056'],
            15 => ['34DIT24F1016'],
            16 => ['34DIT24F1010'],
            17 => ['34DIT24F1014'],
            18 => ['34DIT24F1017'],
        ];

        $cohort = fn () => DB::table('projects as p')
            ->where('p.department', 'JTMK')
            ->where('p.course_code', 'DFT50114')
            ->where('p.session', 'Session 1 2026/2027');

        $count = $cohort()->count();
        if ($count === 0) {
            return;
        }
        if ($count !== count($anchors)) {
            throw new RuntimeException("Expected 18 JTMK projects for group renumbering; found {$count}.");
        }

        $projectIds = [];
        foreach ($anchors as $groupNumber => $matricNumbers) {
            $matches = $cohort()
                ->join('project_members as pm', 'pm.project_id', '=', 'p.id')
                ->join('users as u', 'u.id', '=', 'pm.student_id')
                ->whereIn('u.matric_no', $matricNumbers)
                ->distinct()
                ->pluck('p.id');

            if ($matches->count() !== 1 || in_array((int) $matches->first(), $projectIds, true)) {
                throw new RuntimeException("Could not uniquely match PDF group {$groupNumber} to a project.");
            }
            $projectIds[$groupNumber] = (int) $matches->first();
        }

        DB::transaction(function () use ($projectIds): void {
            foreach ($projectIds as $groupNumber => $projectId) {
                DB::table('projects')->where('id', $projectId)
                    ->update(['project_group_no' => $groupNumber]);
            }
        });
    }

    public function down(): void
    {
        // Existing group numbers were imported data, so there is no safe
        // universal value to restore on rollback.
    }
};
