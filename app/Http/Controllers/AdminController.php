<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\StudentRosterImport;

class AdminController extends Controller
{
    public function users(Request $request): View
    {
        $students = User::query()->where('role', 'Student')->where('department', 'JTMK')
            ->orderBy('academic_session')->orderBy('matric_no')->orderBy('full_name')->get();
        $studentsBySession = $students->groupBy(fn (User $student) => trim((string) $student->academic_session) ?: 'Session not specified');
        $studentsBySession = $studentsBySession->sortByDesc(function ($rows, $session) {
            preg_match('/(\d{4})\s*\/\s*\d{4}/', $session, $year);
            $termNumber = 0;
            if (preg_match('/\b(?:session|sesi)\s*([1-5])\b/i', $session, $term)) {
                $termNumber = (int) $term[1];
            } elseif (preg_match('/^\s*(I{1,3}|IV|V|[1-5])\s*:/i', $session, $term)) {
                $romanTerms = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5];
                $label = strtoupper($term[1]);
                $termNumber = $romanTerms[$label] ?? (int) $label;
            }

            // Newer academic years come first; within a year, Session 2 precedes Session 1.
            return ((int) ($year[1] ?? 0) * 10) + $termNumber;
        })->map(fn ($sessionStudents) => $sessionStudents->sort(
            fn (User $left, User $right) => strnatcasecmp((string) $left->matric_no, (string) $right->matric_no)
        )->values());

        return view('admin.users', [
            'studentCount' => $students->count(),
            'studentsBySession' => $studentsBySession,
            'supervisors' => User::query()->where('role', 'Supervisor')->where('department', 'JTMK')->orderBy('full_name')->get(),
        ]);
    }

    public function searchStudents(Request $request)
    {
        $search = trim((string) $request->query('q'));
        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $students = User::query()->where('role', 'Student')->where('department', 'JTMK')
            ->where(fn ($query) => $query->where('full_name', 'like', "%{$search}%")
                ->orWhere('ic_number', 'like', "%{$search}%")
                ->orWhere('matric_no', 'like', "%{$search}%"))
            ->orderBy('full_name')->limit(20)
            ->get(['id', 'full_name', 'ic_number', 'matric_no', 'email', 'academic_session']);

        return response()->json(['results' => $students]);
    }

    public function importStudents(Request $request, StudentRosterImport $importer): RedirectResponse
    {
        $request->validate(['student_file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx']]);
        $extension = strtolower($request->file('student_file')->getClientOriginalExtension());
        abort_unless(in_array($extension, ['csv', 'txt', 'xlsx'], true), 422, 'Only CSV or XLSX files are accepted.');

        try {
            $records = $importer->parse($request->file('student_file')->getRealPath(), $extension);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['student_file' => 'The file could not be read. Check that it contains Name, IC No, Matric No, Session and Department columns.']);
        }

        $seenIc = $seenMatric = [];
        $imported = $updatedSessions = $ignored = $skipped = 0;
        DB::transaction(function () use ($records, &$seenIc, &$seenMatric, &$imported, &$updatedSessions, &$ignored, &$skipped): void {
            foreach ($records as $record) {
                $student = $record['values'];
                $name = trim($student['full_name']);
                $ic = trim($student['ic_number']);
                $matric = trim($student['matric_no']);
                $session = trim($student['session']);
                if (strtoupper(trim($student['department'])) !== 'JTMK') { $ignored++; continue; }
                if ($name === '' || $ic === '' || $matric === '' || $session === '' || ! preg_match('/^[A-Za-z0-9]+$/', $matric)) { $skipped++; continue; }

                $email = strtolower($matric).'@jtmk.local';
                $icKey = mb_strtolower($ic);
                $matricKey = mb_strtolower($matric);
                if (isset($seenIc[$icKey]) || isset($seenMatric[$matricKey])) {
                    $skipped++; continue;
                }
                $existing = User::query()->where(fn ($query) => $query->where('ic_number', $ic)->orWhere('matric_no', $matric)->orWhere('email', $email)->orWhere('username', $ic))->first();
                if ($existing) {
                    // A roster can safely fill a missing session on the exact existing matric record.
                    if ($existing->matric_no === $matric && blank($existing->academic_session)) {
                        $existing->academic_session = $session;
                        $existing->save();
                        $updatedSessions++;
                    } else {
                        $skipped++;
                    }
                    $seenIc[$icKey] = $seenMatric[$matricKey] = true;
                    continue;
                }
                User::query()->create([
                    'username' => $ic, 'password' => Hash::make($ic), 'full_name' => $name, 'email' => $email,
                    'ic_number' => $ic, 'matric_no' => $matric, 'role' => 'Student', 'department' => 'JTMK',
                    'program_name' => 'JTMK - Information Technology', 'course_code' => 'DFT50114', 'academic_session' => $session,
                ]);
                $seenIc[$icKey] = $seenMatric[$matricKey] = true;
                $imported++;
            }
        });

        return back()->with('status', "{$imported} JTMK student(s) imported; {$updatedSessions} existing session(s) filled from the roster; {$ignored} non-JTMK row(s) ignored; {$skipped} invalid or duplicate row(s) skipped.");
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'ic_number' => ['required', 'string', 'max:30', 'unique:users,ic_number'],
            'matric_no' => ['required_if:role,Student', 'nullable', 'string', 'max:30', 'unique:users,matric_no'],
            'role' => ['required', Rule::in(['Student', 'Supervisor', 'Admin', 'Panel'])],
        ]);
        $username = $data['ic_number'];
        if (User::query()->where('username', $username)->exists()) {
            return back()->withErrors(['ic_number' => 'This IC number is already used as an account username.'])->withInput();
        }
        User::query()->create([
            ...$data, 'username' => $username, 'password' => Hash::make($data['ic_number']),
            'department' => 'JTMK', 'program_name' => 'JTMK - Information Technology', 'course_code' => 'DFT50114',
        ]);

        return back()->with('status', 'Account created. Share the sign-in details through your approved staff process.');
    }

    public function updateUser(Request $request, int $user): RedirectResponse
    {
        $account = User::query()->findOrFail($user);
        $data = $request->validate([
            'role' => ['required', Rule::in(['Student', 'Supervisor', 'Admin', 'Panel'])],
            'full_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($account->id)],
            'matric_no' => ['nullable', 'string', 'max:30', Rule::unique('users', 'matric_no')->ignore($account->id)],
            'ic_number' => ['required', 'string', 'max:30', Rule::unique('users', 'ic_number')->ignore($account->id)],
            'academic_session' => ['nullable', 'string', 'max:50'],
            'reset_password' => ['sometimes', 'boolean'],
        ]);
        if ($account->is(auth()->user()) && $data['role'] !== 'Admin') {
            return back()->withErrors(['role' => 'You cannot remove your own administrator access.']);
        }
        $resetPassword = (bool) ($data['reset_password'] ?? false);
        unset($data['reset_password']);
        if ($account->username !== $data['ic_number'] && User::query()->where('username', $data['ic_number'])->whereKeyNot($account->id)->exists()) {
            return back()->withErrors(['ic_number' => 'This IC number is already used as another account username.'])->withInput();
        }
        $account->fill($data);
        $account->username = $data['ic_number'];
        if ($resetPassword || ($account->password_changed_at === null && $account->ic_number !== $data['ic_number'])) {
            $account->password = Hash::make($data['ic_number']);
            $account->password_changed_at = null;
        }
        $account->save();

        return back()->with('status', 'Account details updated.');
    }

    public function deleteUser(int $user): RedirectResponse
    {
        abort_if((int) $user === (int) auth()->id(), 409, 'You cannot delete your own account.');
        $hasRecords = DB::table('projects')->where('created_by', $user)->orWhere('supervisor_id', $user)->exists()
            || DB::table('project_members')->where('student_id', $user)->exists()
            || DB::table('supervisor_students')->where('supervisor_id', $user)->orWhere('student_id', $user)->exists()
            || DB::table('panel_sessions')->where('created_by', $user)->exists()
            || DB::table('panel_student_marks')->where('student_id', $user)->exists()
            || DB::table('student_demo_status')->where('supervisor_id', $user)->orWhere('student_id', $user)->exists()
            || DB::table('supervisor_logbook')->where('supervisor_id', $user)->orWhere('student_id', $user)->exists();
        if ($hasRecords) {
            return back()->withErrors(['account' => 'This account is linked to project or supervision records. Reassign or archive those records before removal.']);
        }
        User::query()->findOrFail($user)->delete();

        return back()->with('status', 'Account deleted.');
    }

    public function projects(): View
    {
        $projects = DB::table('projects as p')->leftJoin('users as sv', 'sv.id', '=', 'p.supervisor_id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
            ->select('p.*', 'sv.full_name as supervisor_name')
            ->orderByRaw('CASE WHEN p.project_group_no IS NULL THEN 1 ELSE 0 END')
            ->orderBy('p.project_group_no')->orderBy('p.id')->get();
        $membersByProject = collect();
        if ($projects->isNotEmpty()) {
            $members = DB::table('projects as p')->join('project_members as pm', 'pm.project_id', '=', 'p.id')
                ->join('users as u', 'u.id', '=', 'pm.student_id')
                ->leftJoin('student_demo_status as d1', function ($join): void {
                    $join->on('d1.student_id', '=', 'u.id')->on('d1.supervisor_id', '=', 'p.supervisor_id')->where('d1.demo_type', '=', 'Demo 1');
                })
                ->leftJoin('student_demo_status as d2', function ($join): void {
                    $join->on('d2.student_id', '=', 'u.id')->on('d2.supervisor_id', '=', 'p.supervisor_id')->where('d2.demo_type', '=', 'Demo 2');
                })
                ->whereIn('p.id', $projects->pluck('id'))
                ->select('p.id as project_id', 'u.id as student_id', 'u.full_name', 'u.matric_no', 'u.ic_number', 'pm.role as member_role', 'pm.member_order')
                ->selectRaw("COALESCE(d1.status, 'Pending') as demo1_status, COALESCE(d2.status, 'Pending') as demo2_status")
                ->orderBy('pm.member_order')->orderBy('u.full_name')->get();
            $membersByProject = $members->groupBy('project_id');

            $projectsWithoutMembers = $projects->filter(fn ($project) => ! $membersByProject->has($project->id) && $project->student_id);
            if ($projectsWithoutMembers->isNotEmpty()) {
                $leaders = DB::table('projects as p')->join('users as u', 'u.id', '=', 'p.student_id')
                    ->whereIn('p.id', $projectsWithoutMembers->pluck('id'))
                    ->select('p.id as project_id', 'u.id as student_id', 'u.full_name', 'u.matric_no', 'u.ic_number')
                    ->selectRaw("'Leader' as member_role, 0 as member_order, 'Pending' as demo1_status, 'Pending' as demo2_status")
                    ->get()->groupBy('project_id');
                $membersByProject = $membersByProject->union($leaders);
            }
        }

        return view('admin.projects', [
            'projects' => $projects->map(function ($project) use ($membersByProject) {
                $project->members = $membersByProject->get($project->id, collect());
                return $project;
            }),
            'supervisors' => User::query()->where('role', 'Supervisor')->where('department', 'JTMK')->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function assignProjectSupervisor(Request $request, int $project): RedirectResponse
    {
        $data = $request->validate(['supervisor_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'Supervisor')->where('department', 'JTMK'))]]);
        $projectRecord = DB::table('projects')->where('id', $project)->where('department', 'JTMK')->where('course_code', 'DFT50114')->first();
        abort_unless($projectRecord, 404);

        DB::transaction(function () use ($project, $projectRecord, $data): void {
            DB::table('projects')->where('id', $project)->update(['supervisor_id' => $data['supervisor_id']]);
            $members = DB::table('project_members')->where('project_id', $project)->pluck('student_id');
            if ($members->isEmpty() && $projectRecord->student_id) $members = collect([$projectRecord->student_id]);
            if ($members->isEmpty()) return;

            DB::table('supervisor_students')->whereIn('student_id', $members)->delete();
            $now = now();
            DB::table('supervisor_students')->insert($members->map(fn ($studentId) => [
                'supervisor_id' => $data['supervisor_id'], 'student_id' => $studentId,
                'session' => $projectRecord->session, 'created_at' => $now,
            ])->all());
        });

        return back()->with('status', 'Supervisor assignment updated for the whole project group.');
    }

    public function updateProject(Request $request, int $project): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['Draft', 'Submitted', 'Approved'])]]);
        DB::table('projects')->where('id', $project)->update(['status' => $data['status']]);

        return back()->with('status', 'Project status updated.');
    }

    public function panelQr(): View
    {
        $latestSession = DB::table('projects')->where('department', 'JTMK')->where('course_code', 'DFT50114')
            ->whereNotNull('project_group_no')->orderByDesc('id')->value('session');
        $projects = $latestSession ? DB::table('projects')->where('department', 'JTMK')->where('course_code', 'DFT50114')
            ->whereNotNull('project_group_no')->where('session', $latestSession)->orderBy('project_group_no')->orderBy('id')
            ->get(['id', 'project_group_no as group_no', 'title', 'session']) : collect();

        return view('admin.panel-qr', ['projects' => $projects, 'generatedQrs' => session('generated_panel_qrs', [])]);
    }

    public function generatePanelQr(Request $request): RedirectResponse
    {
        $latestSession = DB::table('projects')->where('department', 'JTMK')->where('course_code', 'DFT50114')
            ->whereNotNull('project_group_no')->orderByDesc('id')->value('session');
        $projects = $latestSession ? DB::table('projects')->where('department', 'JTMK')->where('course_code', 'DFT50114')
            ->whereNotNull('project_group_no')->where('session', $latestSession)->orderBy('project_group_no')->orderBy('id')->get(['id', 'project_group_no as group_no']) : collect();
        abort_if($projects->isEmpty(), 422, 'No current DFT50114 project groups are available.');

        $mode = $request->input('mode');
        $data = $request->validate([
            'mode' => ['required', Rule::in(['shared', 'split'])],
            'total_panels' => ['required', 'integer', 'min:2', 'max:255'],
            'qr_count' => [Rule::requiredIf($mode === 'split'), 'nullable', 'integer', 'min:1', 'max:'.$projects->count()],
            'group_sizes' => [Rule::requiredIf($mode === 'split'), 'nullable', 'array'],
            'group_sizes.*' => ['required_with:group_sizes', 'integer', 'min:1', 'max:'.$projects->count()],
            'panel_counts' => [Rule::requiredIf($mode === 'split'), 'nullable', 'array'],
            'panel_counts.*' => ['required_with:panel_counts', 'integer', 'min:2', 'max:255'],
        ]);

        $totalPanels = (int) $data['total_panels'];
        $batchCount = $mode === 'shared' ? 1 : (int) $data['qr_count'];
        $groupSizes = $mode === 'shared' ? [$projects->count()] : array_map('intval', array_values($data['group_sizes']));
        $panelCounts = $mode === 'shared' ? [$totalPanels] : array_map('intval', array_values($data['panel_counts']));
        if (count($groupSizes) !== $batchCount || count($panelCounts) !== $batchCount) {
            return back()->withErrors(['qr_count' => 'Set the group and panel member counts for every QR batch.'])->withInput();
        }
        if (array_sum($groupSizes) !== $projects->count()) {
            return back()->withErrors(['group_sizes' => 'Group counts across batches must add up to all current groups.'])->withInput();
        }
        if (array_sum($panelCounts) < $totalPanels) {
            return back()->withErrors(['panel_counts' => 'Assign all panel members to at least one QR batch.'])->withInput();
        }

        $projectChunks = [];
        $offset = 0;
        foreach ($groupSizes as $groupSize) {
            $projectChunks[] = $projects->slice($offset, $groupSize)->values();
            $offset += $groupSize;
        }

        $generated = DB::transaction(function () use ($projectChunks, $panelCounts): array {
            $ranges = [];
            foreach ($projectChunks as $index => $batch) {
                $token = bin2hex(random_bytes(32));
                $panelSessionId = DB::table('panel_sessions')->insertGetId([
                    'project_id' => $batch->first()->id,
                    'token' => $token,
                    'created_by' => auth()->id(),
                    'expected_panel_count' => $panelCounts[$index],
                    'status' => 'Active',
                    'expires_at' => now()->addDays(7),
                    'created_at' => now(),
                ]);
                DB::table('panel_session_projects')->insert($batch->map(fn ($project) => [
                    'panel_session_id' => $panelSessionId, 'project_id' => $project->id,
                ])->all());
                $ranges[] = [
                    'url' => url('/panel.php?token='.$token),
                    'groups' => $batch->pluck('group_no')->all(),
                    'group_count' => $batch->count(),
                    'panel_count' => $panelCounts[$index],
                ];
            }
            return $ranges;
        });

        $message = $mode === 'shared'
            ? 'QR generated for all '.$projects->count().' groups, with a limit of '.$totalPanels.' panel members for 7 days.'
            : count($generated).' QR batches generated for '.$projects->count().' groups. Each QR is valid for 7 days.';

        return redirect()->route('admin.panel-qr.index')->with('status', $message)->with('generated_panel_qrs', $generated);
    }

    public function settings(): View
    {
        $configuration = [
            'System Name' => 'SPInE Politeknik Besut',
            'Department' => 'JTMK - Information Technology',
            'Course Code' => 'DFT50114',
            'Course Name' => 'Integrated Project',
            'Project Scope' => 'JTMK IT projects only',
            'Assessment Model' => 'Supervisor verification only: Passed / Not Passed',
        ];

        return view('admin.settings', compact('configuration'));
    }

    public function reports(Request $request): View
    {
        $scope = fn () => DB::table('projects')->where('department', 'JTMK')->where('course_code', 'DFT50114');
        $byCategory = $scope()->select('category', DB::raw('COUNT(*) AS project_count'))
            ->groupBy('category')->orderByDesc('project_count')->orderBy('category')->get();
        $byStatus = $scope()->select('status', DB::raw('COUNT(*) AS project_count'))
            ->groupBy('status')->orderBy('status')->get();

        $demoSummary = DB::table('student_demo_status as sds')
            ->join('users as sv', 'sv.id', '=', 'sds.supervisor_id')
            ->join('users as student', 'student.id', '=', 'sds.student_id')
            ->where('sv.role', 'Supervisor')->where('sv.department', 'JTMK')
            ->where('student.role', 'Student')->where('student.department', 'JTMK')
            ->whereIn('sds.demo_type', ['Demo 1', 'Demo 2'])
            ->select('sds.demo_type', 'sds.status', DB::raw('COUNT(*) AS total'))
            ->groupBy('sds.demo_type', 'sds.status')->get()
            ->groupBy('demo_type')->map(fn ($rows) => $rows->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]));

        $panelBatches = DB::table('panel_sessions as ps')
            ->join('panel_session_projects as psp', 'psp.panel_session_id', '=', 'ps.id')
            ->join('projects as p', 'p.id', '=', 'psp.project_id')
            ->leftJoin('panel_evaluations as pe', 'pe.panel_session_id', '=', 'ps.id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
            ->select('ps.id', 'ps.created_at')
            ->selectRaw('MAX(pe.created_at) AS latest_evaluation_at, COUNT(DISTINCT psp.project_id) AS project_count, MIN(p.project_group_no) AS first_group_no, MAX(p.project_group_no) AS last_group_no')
            ->groupBy('ps.id', 'ps.created_at')->orderByDesc('ps.created_at')->orderByDesc('ps.id')->get();
        $selectedBatch = $panelBatches->first();
        if ($request->filled('panel_batch')) {
            $selectedBatch = $panelBatches->firstWhere('id', (int) $request->query('panel_batch')) ?? $selectedBatch;
        }

        $projectRanking = collect();
        if ($selectedBatch) {
            $projectRanking = DB::table('panel_student_marks as psm')
                ->join('panel_evaluations as pe', 'pe.id', '=', 'psm.panel_evaluation_id')
                ->join('projects as p', 'p.id', '=', 'pe.project_id')
                ->leftJoin('users as leader', 'leader.id', '=', 'p.student_id')
                ->where('pe.panel_session_id', $selectedBatch->id)
                ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
                ->select('p.id', 'p.title', 'p.project_group_no', 'p.category', 'leader.full_name as leader_name')
                ->selectRaw('COUNT(DISTINCT pe.id) AS evaluation_count, AVG(psm.total_score) AS average_score, AVG(psm.demo3_score) AS average_demo3_score')
                ->groupBy('p.id', 'p.title', 'p.project_group_no', 'p.category', 'leader.full_name')
                ->orderByDesc('average_demo3_score')->orderByDesc('average_score')->limit(5)->get();
        }

        $latestQrTimestamp = DB::table('panel_sessions as ps')
            ->join('panel_session_projects as psp', 'psp.panel_session_id', '=', 'ps.id')
            ->join('projects as p', 'p.id', '=', 'psp.project_id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
            ->orderByDesc('ps.created_at')->orderByDesc('ps.id')->value('ps.created_at');
        $latestQrSessionIds = $latestQrTimestamp ? DB::table('panel_sessions as ps')
            ->join('panel_session_projects as psp', 'psp.panel_session_id', '=', 'ps.id')
            ->join('projects as p', 'p.id', '=', 'psp.project_id')
            ->where('ps.created_at', $latestQrTimestamp)
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
            ->distinct()->pluck('ps.id') : collect();
        $latestQrProjectIds = $latestQrSessionIds->isNotEmpty() ? DB::table('panel_session_projects')
            ->whereIn('panel_session_id', $latestQrSessionIds)->distinct()->pluck('project_id') : collect();
        $panelReportProjects = $latestQrProjectIds->isNotEmpty() ? DB::table('projects')
            ->whereIn('id', $latestQrProjectIds)->where('department', 'JTMK')->where('course_code', 'DFT50114')
            ->orderBy('project_group_no')->get(['id', 'title', 'project_group_no']) : collect();
        $evaluationsByProject = $latestQrProjectIds->isNotEmpty() ? DB::table('panel_evaluations as pe')
            ->whereIn('pe.panel_session_id', $latestQrSessionIds)->whereIn('pe.project_id', $latestQrProjectIds)
            ->orderBy('pe.created_at')->get(['pe.id', 'pe.project_id', 'pe.panel_name', 'pe.panel_email', 'pe.assessor_types', 'pe.student_scores_json', 'pe.comments', 'pe.average_score', 'pe.created_at'])
            ->map(function ($evaluation) {
                $scores = json_decode($evaluation->student_scores_json, true) ?: [];
                $evaluation->members = $scores['members'] ?? [];
                $evaluation->aspects = $scores['aspects'] ?? [];
                $evaluation->results = $scores['results'] ?? [];
                $demo3Scores = array_map(fn ($result) => (float) ($result['demo3_score'] ?? 0), $evaluation->results);
                $evaluation->group_demo3_score = $demo3Scores ? round(array_sum($demo3Scores) / count($demo3Scores), 2) : null;
                return $evaluation;
            })->groupBy('project_id') : collect();
        $panelReportGroups = $panelReportProjects->map(function ($project) use ($evaluationsByProject) {
            $evaluations = $evaluationsByProject->get($project->id, collect());
            return (object) [
                'group_no' => $project->project_group_no,
                'title' => $project->title,
                'evaluations' => $evaluations,
                'average_rating' => $evaluations->isNotEmpty() ? round($evaluations->avg('average_score'), 2) : null,
                'average_demo3' => $evaluations->whereNotNull('group_demo3_score')->isNotEmpty() ? round($evaluations->whereNotNull('group_demo3_score')->avg('group_demo3_score'), 2) : null,
            ];
        });
        $panelSubmissionCount = $evaluationsByProject->sum(fn ($evaluations) => $evaluations->count());

        if ($selectedBatch) {
            $panelChoices = DB::table('panel_session_choices as psc')
                ->join('projects', 'projects.id', '=', 'psc.project_id')
                ->leftJoin('users as leader', 'leader.id', '=', 'projects.student_id')
                ->where('psc.panel_session_id', $selectedBatch->id)
                ->where('projects.department', 'JTMK')->where('projects.course_code', 'DFT50114')
                ->select('projects.title', 'projects.project_group_no', 'projects.category', 'projects.session', 'leader.full_name as leader_name')
                ->distinct()->orderBy('projects.project_group_no')->get();
        } else {
            $panelChoices = DB::table('projects')->leftJoin('users as leader', 'leader.id', '=', 'projects.student_id')
                ->where('projects.department', 'JTMK')->where('projects.course_code', 'DFT50114')
                ->where('projects.is_panel_choice', 1)
                ->select('projects.title', 'projects.project_group_no', 'projects.category', 'projects.session', 'leader.full_name as leader_name');
            $panelChoices = $panelChoices->orderBy('projects.project_group_no')->get();
        }

        return view('admin.reports', compact('byCategory', 'byStatus', 'demoSummary', 'panelBatches', 'selectedBatch', 'projectRanking', 'panelChoices', 'panelReportGroups', 'panelSubmissionCount'));
    }
}
