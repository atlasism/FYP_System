<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalController extends Controller
{
    private const CATEGORIES = [
        'Multimedia and animation', 'Internet of Things (IOT)', 'Artificial Intelligent (AI)',
        'Software application', 'Web application', 'Mobile application', 'Networking system',
        'Hardware design', 'Robotic programming', 'Information system', 'Security system',
        'Data management & visualization', 'Data analysis',
    ];

    private const DOCUMENT_TYPES = [
        'A' => 'Proposal Presentation', 'B' => 'Project Demonstration 1', 'C' => 'Project Demonstration 2',
        'D' => 'Project Demonstration 3', 'E' => 'Final Presentation - Poster', 'F' => 'Final Presentation',
        'TECHNICAL_REPORT' => 'Technical Report',
    ];

    public function dashboard(): RedirectResponse
    {
        return match (auth()->user()->role) {
            'Student' => redirect()->route('student.dashboard'),
            'Supervisor' => redirect()->route('supervisor.dashboard'),
            'Admin' => redirect()->route('admin.dashboard'),
            'Panel' => redirect()->route('panel.dashboard'),
            default => abort(403),
        };
    }

    public function home(): View
    {
        $announcement = DB::table('system_settings')->where('setting_key', 'announcement_text')->value('setting_value') ?: 'No current announcements.';
        $topRankings = DB::table('projects as p')
            ->join('panel_evaluations as pe', 'pe.project_id', '=', 'p.id')
            ->join('panel_student_marks as psm', 'psm.panel_evaluation_id', '=', 'pe.id')
            ->leftJoin('users as leader', 'leader.id', '=', 'p.student_id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
            ->select('p.id', 'p.project_group_no', 'p.title', 'leader.full_name as leader_name')
            ->selectRaw('AVG(psm.total_score) as avg_total_score, AVG(psm.demo3_score) as avg_demo3_score')
            ->groupBy('p.id', 'p.project_group_no', 'p.title', 'leader.full_name')
            ->orderByDesc('avg_total_score')->orderByDesc('avg_demo3_score')->limit(5)->get();
        $panelChoices = DB::table('projects as p')
            ->leftJoin('users as leader', 'leader.id', '=', 'p.student_id')
            ->leftJoin('project_members as pm', 'pm.project_id', '=', 'p.id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')->where('p.is_panel_choice', 1)
            ->select('p.id', 'p.project_group_no', 'p.title', 'p.session', 'leader.full_name as leader_name')
            ->selectRaw('COUNT(DISTINCT pm.student_id) as member_count')
            ->groupBy('p.id', 'p.project_group_no', 'p.title', 'p.session', 'leader.full_name')
            ->orderBy('p.project_group_no')->get();
        $deadlines = DB::table('submission_deadlines')->orderBy('due_date')->orderBy('id')->get()
            ->sortBy(fn ($deadline) => ($deadline->due_date === null || str_starts_with($deadline->due_date, '0000-00-00') ? '9999-12-31' : $deadline->due_date).sprintf('%010d', $deadline->id))
            ->values();
        $currentProjects = DB::table('projects as p')->join('users as u', 'u.id', '=', 'p.student_id')
            ->where(function ($query) { $query->whereNull('p.is_complete_for_evaluation')->orWhere('p.is_complete_for_evaluation', 0); })
            ->where(function ($query) { $query->whereNull('p.status')->orWhere('p.status', '<>', 'Completed'); })
            ->select('p.*', 'u.full_name')->orderBy('p.project_group_no')->limit(6)->get();

        return view('home', compact('announcement', 'topRankings', 'panelChoices', 'deadlines', 'currentProjects'));
    }

    public function studentDashboard(): View
    {
        $user = auth()->user();
        $project = $this->studentProject($user->id);
        $team = $project ? DB::table('project_members as pm')->join('users as u', 'u.id', '=', 'pm.student_id')
            ->where('pm.project_id', $project->id)->orderBy('pm.member_order')->select('u.full_name', 'u.matric_no', 'pm.role')->get() : collect();
        $documents = $project ? DB::table('project_documents')->where('project_id', $project->id)->orderByDesc('uploaded_at')->get() : collect();
        $deadlines = DB::table('submission_deadlines')->orderBy('due_date')->get();
        $marks = $project ? DB::table('project_marks')->where('project_id', $project->id)->first() : null;

        return view('portal.student', compact('user', 'project', 'team', 'documents', 'deadlines', 'marks'));
    }

    public function createProject(): View|RedirectResponse
    {
        if ($this->studentProject(auth()->id())) {
            return redirect()->route('student.dashboard')->with('status', 'You are already part of a project. Your current project is shown below.');
        }

        return view('portal.project-create', ['categories' => self::CATEGORIES]);
    }

    public function storeProject(Request $request): RedirectResponse
    {
        if ($this->studentProject(auth()->id())) {
            return redirect()->route('student.dashboard')->with('status', 'You are already part of a project. Your current project is shown below.');
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'description' => ['required', 'string', 'max:10000'],
            'session' => ['required', 'string', 'max:50'],
            'member_2' => ['nullable', 'string', 'max:30'],
            'member_3' => ['nullable', 'string', 'max:30'],
        ]);

        $members = [auth()->id()];
        foreach (['member_2', 'member_3'] as $field) {
            if (blank($data[$field] ?? null)) {
                continue;
            }
            $member = User::query()->where('matric_no', $data[$field])->where('role', 'Student')->first();
            if (! $member || in_array($member->id, $members, true) || $this->studentProject($member->id)) {
                return back()->withErrors([$field => 'That student was not found, is already selected, or already belongs to a project.'])->withInput();
            }
            $members[] = $member->id;
        }

        DB::transaction(function () use ($data, $members) {
            $projectId = DB::table('projects')->insertGetId([
                'student_id' => auth()->id(), 'created_by' => auth()->id(), 'title' => $data['title'],
                'department' => 'JTMK', 'program_name' => 'JTMK - Information Technology',
                'course_code' => 'DFT50114', 'category' => $data['category'], 'session' => $data['session'],
                'description' => $data['description'], 'status' => 'Submitted', 'created_at' => now(),
            ]);

            foreach ($members as $position => $studentId) {
                DB::table('project_members')->insert([
                    'project_id' => $projectId, 'student_id' => $studentId,
                    'role' => $position === 0 ? 'Leader' : 'Member', 'member_order' => $position + 1,
                    'joined_at' => now(),
                ]);
            }
        });

        return redirect()->route('student.dashboard')->with('status', 'Your project and team have been registered.');
    }

    public function uploadDocument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'doc_type' => ['required', Rule::in(array_keys(self::DOCUMENT_TYPES))],
            'document_file' => ['required', 'file', 'max:102400', 'mimes:pdf,docx,zip'],
        ]);
        $project = $this->studentProject(auth()->id());
        abort_unless($project, 409, 'Register your project before submitting a document.');
        $duplicate = DB::table('project_documents')->where('project_id', $project->id)->where('doc_type', $data['doc_type'])->exists();
        if ($duplicate) {
            return back()->withErrors(['document_file' => 'Your project team has already submitted this document type.']);
        }

        $file = $request->file('document_file');
        $path = $file->store('project-documents/'.$project->id, 'local');
        DB::table('project_documents')->insert([
            'project_id' => $project->id, 'doc_type' => $data['doc_type'], 'file_path' => $path,
            'original_name' => $file->getClientOriginalName(), 'status' => 'Pending', 'uploaded_at' => now(),
        ]);

        return back()->with('status', 'Document uploaded for supervisor review.');
    }

    public function supervisorDashboard(): View
    {
        $supervisorId = auth()->id();
        $students = DB::table('supervisor_students as ss')->join('users as u', 'u.id', '=', 'ss.student_id')
            ->where('ss.supervisor_id', $supervisorId)->select('u.*', 'ss.session')->orderBy('u.full_name')->get();
        $projects = DB::table('projects as p')->leftJoin('users as u', 'u.id', '=', 'p.student_id')
            ->where('p.supervisor_id', $supervisorId)->select('p.*', 'u.full_name as leader_name')
            ->orderByRaw('CASE WHEN p.project_group_no IS NULL THEN 1 ELSE 0 END')
            ->orderBy('p.project_group_no')->orderBy('p.id')->get();
        $documents = DB::table('project_documents as d')->join('projects as p', 'p.id', '=', 'd.project_id')
            ->where('p.supervisor_id', $supervisorId)->select('d.*', 'p.title as project_title')->orderByDesc('d.uploaded_at')->get();

        return view('portal.supervisor', compact('students', 'projects', 'documents'));
    }

    public function reviewDocument(Request $request, int $document): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['Reviewed', 'Approved', 'Rejected'])]]);
        $record = DB::table('project_documents as d')->join('projects as p', 'p.id', '=', 'd.project_id')
            ->where('d.id', $document)->where('p.supervisor_id', auth()->id())->select('d.id')->first();
        abort_unless($record, 404);
        DB::table('project_documents')->where('id', $document)->update(['status' => $data['status']]);

        return back()->with('status', 'Document review status saved.');
    }

    public function adminDashboard(): View
    {
        $stats = [
            'students' => User::query()->where('role', 'Student')->count(),
            'supervisors' => User::query()->where('role', 'Supervisor')->count(),
            'projects' => DB::table('projects')->count(),
            'pending_documents' => DB::table('project_documents')->where('status', 'Pending')->count(),
        ];
        $projects = DB::table('projects')->orderByDesc('created_at')->limit(12)->get();
        $deadlines = DB::table('submission_deadlines')->orderBy('due_date')->get();

        return view('portal.admin', compact('stats', 'projects', 'deadlines'));
    }

    public function panelDashboard(): View
    {
        return view('portal.panel');
    }

    public function downloadDocument(int $document)
    {
        $record = DB::table('project_documents as d')->join('projects as p', 'p.id', '=', 'd.project_id')
            ->where('d.id', $document)->select('d.*', 'p.supervisor_id', 'p.id as project_id')->first();
        abort_unless($record, 404);

        $user = auth()->user();
        $allowed = $user->role === 'Admin'
            || ($user->role === 'Supervisor' && (int) $record->supervisor_id === (int) $user->id)
            || ($user->role === 'Student' && (int) $this->studentProject($user->id)?->id === (int) $record->project_id);
        abort_unless($allowed, 403);

        if (Storage::disk('local')->exists($record->file_path)) {
            return Storage::disk('local')->download($record->file_path, $record->original_name ?: basename($record->file_path));
        }

        $legacyRoot = realpath(base_path('legacy/uploads/documents'));
        $legacyFile = $legacyRoot ? realpath($legacyRoot.DIRECTORY_SEPARATOR.basename($record->file_path)) : false;
        abort_unless($legacyRoot && $legacyFile && str_starts_with($legacyFile, $legacyRoot.DIRECTORY_SEPARATOR) && is_file($legacyFile), 404);

        return response()->download($legacyFile, $record->original_name ?: basename($legacyFile));
    }

    private function studentProject(int $studentId): ?object
    {
        return DB::table('projects as p')->join('project_members as pm', 'pm.project_id', '=', 'p.id')
            ->where('pm.student_id', $studentId)->select('p.*')->first();
    }
}
