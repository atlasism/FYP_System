<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Official DFT50194 PDF order. The application currently stores this
        // cohort under DFT50114; changing that course code is a separate task.
        $groups = [
            1 => ['CSPS-COLLAB', '34DIT24F1038', '34DIT24F1035', '34DIT24F1054'],
            2 => ['DIGITAL QUALITY ASSURANCE UNIT MANAGEMENT SYSTEM (E-UJK)', '34DIT24F1053', '34DIT24F1031', '34DIT24F1025'],
            3 => ['EFORMULA BATIK', '34DIT24F1064', '34DIT24F1058', '34DIT24F1033'],
            4 => ['E-RAYUAN MANAGEMENT SYSTEM (ERMS)', '34DIT24F1024', '34DIT24F1001', '34DIT24F1062'],
            5 => ['FYP INVENTORY SYSTEM', '34DIT24F1032', '34DIT24F1037', '34DIT24F1044'],
            6 => ['I-JPP CENTER', '34DIT24F1003', '34DIT24F1008', '34DIT24F1047'],
            7 => ['I-JRKV', '34DIT24F1006', '34DIT24F1026', '34DIT24F1048'],
            8 => ['IMPROVE SELF LEARNING PLATFORM (ISEP)', '34DIT24F1028', '34DIT24F1018', '34DIT24F1040'],
            9 => ['MACHINE MANAGEMENT SYSTEM - JRKV (MMS - JRKV)', '34DIT24F1007', '34DIT24F1005', '34DIT24F1009'],
            10 => ['MYHEP: STUDENT AFFAIRS MANAGEMENT SYSTEM', '34DIT24F1011', '34DIT24F1022', '34DIT24F1063'],
            11 => ['POLIBEST – STAFFCLUB MANAGEMENT SYSTEM', '34DIT24F1042', '34DIT24F1004', '34DIT24F1019'],
            12 => ['POLISPACE (SISTEM PENEMPAHAN FASILITI)', '34DIT24F1029', '34DIT24F1027', '34DIT24F1049'],
            13 => ['SFB SYSTEM', '34DIT24F1045', '34DIT24F1052'],
            14 => ['SISTEM ADMIN JTMK (SAJTMK)', '34DIT24F1015', '34DIT24F1056', '34DIT24F1057'],
            15 => ['SISTEM E-PARCEL', '34DIT24F1043', '34DIT24F1055', '34DIT24F1016'],
            16 => ['SISTEM PENGURUSAN (ALK) & MESYUARAT (AGM)', '34DIT24F1010', '34DIT24F1046', '34DIT24F1061'],
            17 => ['SISTEM PENGURUSAN KOPERASI (COOP BEST)', '34DIT24F1014', '34DIT24F1020', '34DIT24F1036'],
            18 => ['UIDM HUB SYSTEM', '34DIT24F1013', '34DIT24F1017', '34DIT24F1051'],
        ];

        $matricCorrections = [
            '34DIT24D1038' => '34DIT24F1038',
            '34DIT24D1035' => '34DIT24F1035',
            '34DIT24D1054' => '34DIT24F1054',
            '34DIT34F1028' => '34DIT24F1028',
            '34DIT34F1051' => '34DIT24F1051',
        ];

        $nameCorrections = [
            '34DIT24F1054' => 'SHARVEHSHAN',
            '34DIT24F1053' => 'MUHAMMAD ADAM MUAZHAM BIN MOHD FARID',
            '34DIT24F1063' => 'TUAN NUR AWATIF BINTI TUAN ABDULLAH SANUSI',
            '34DIT24F1043' => 'MUHAMMAD RAFFIUDEEN BIN MOHD ROZALI',
        ];

        DB::transaction(function () use ($groups, $matricCorrections, $nameCorrections): void {
            $projects = DB::table('projects')
                ->where('department', 'JTMK')
                ->where('course_code', 'DFT50114')
                ->where('session', 'Session 1 2026/2027')
                ->get(['id', 'project_group_no']);

            if ($projects->isEmpty()) {
                return; // An unseeded installation has no roster to reconcile.
            }
            if ($projects->count() !== 18 || $projects->pluck('project_group_no')->unique()->count() !== 18) {
                throw new RuntimeException('Expected one project in each official group 1–18.');
            }

            foreach ($groups as $groupNumber => $definition) {
                $title = $definition[0];
                $expectedMatricNumbers = array_slice($definition, 1);
                $project = $projects->firstWhere('project_group_no', $groupNumber);
                if (! $project) {
                    throw new RuntimeException("Missing official group {$groupNumber}.");
                }

                $members = DB::table('project_members as pm')
                    ->join('users as u', 'u.id', '=', 'pm.student_id')
                    ->where('pm.project_id', $project->id)
                    ->get(['u.id', 'u.matric_no']);
                $actual = $members->pluck('matric_no')
                    ->map(fn ($matric) => $matricCorrections[$matric] ?? $matric)
                    ->sort()->values()->all();
                $expected = collect($expectedMatricNumbers)->sort()->values()->all();
                if ($actual !== $expected) {
                    throw new RuntimeException("Student roster does not match PDF group {$groupNumber}.");
                }

                DB::table('projects')->where('id', $project->id)->update(['title' => $title]);
                foreach ($members as $member) {
                    $matric = $matricCorrections[$member->matric_no] ?? $member->matric_no;
                    $updates = [];
                    if ($matric !== $member->matric_no) {
                        $updates['matric_no'] = $matric;
                    }
                    if (isset($nameCorrections[$matric])) {
                        $updates['full_name'] = $nameCorrections[$matric];
                    }
                    if ($updates) {
                        DB::table('users')->where('id', $member->id)->update($updates);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Imported titles and student details have no safe universal original values.
    }
};
