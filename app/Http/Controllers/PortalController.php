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
            ->where('pm.project_id', $project->id)->orderBy('pm.member_order')->select('u.id', 'u.full_name', 'u.ic_number', 'u.matric_no', 'pm.role')->get() : collect();
        $documents = $project ? DB::table('project_documents')->where('project_id', $project->id)->orderByDesc('uploaded_at')->get() : collect();
        $deadlines = DB::table('submission_deadlines')->orderBy('due_date')->get();
        $supervisor = $project && $project->supervisor_id ? DB::table('users')->where('id', $project->supervisor_id)->value('full_name') : null;
        $totalScore = $project ? DB::table('panel_evaluations as pe')
            ->join('panel_student_marks as psm', 'psm.panel_evaluation_id', '=', 'pe.id')
            ->where('pe.project_id', $project->id)->avg('psm.total_score') : null;

        return view('portal.student', compact('user', 'project', 'team', 'documents', 'deadlines', 'supervisor', 'totalScore'));
    }

    public function studentDocuments(): View
    {
        $project = $this->studentProject(auth()->id());
        $documents = $project ? DB::table('project_documents')->where('project_id', $project->id)->orderByDesc('uploaded_at')->get() : collect();

        return view('portal.student-documents', compact('project', 'documents'));
    }

    public function studentProjectPage(): View
    {
        $project = $this->studentProject(auth()->id());
        $team = $project ? DB::table('project_members as pm')->join('users as u', 'u.id', '=', 'pm.student_id')
            ->where('pm.project_id', $project->id)->orderBy('pm.member_order')->select('u.id', 'u.full_name', 'u.ic_number', 'u.matric_no', 'pm.role')->get() : collect();

        return view('portal.student-project', compact('project', 'team'));
    }

    public function studentMilestones(): View
    {
        $verification = DB::table('student_demo_status')->where('student_id', auth()->id())->pluck('status', 'demo_type');

        return view('portal.student-milestones', compact('verification'));
    }

    public function studentDeadlines(): View
    {
        $deadlines = DB::table('submission_deadlines')->where('title', '<>', 'Log Book')->orderBy('id')->get();

        return view('portal.student-deadlines', compact('deadlines'));
    }

    public function studentGroups(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $projects = DB::table('projects as p')->leftJoin('users as u', 'u.id', '=', 'p.student_id')
            ->select('p.id', 'p.project_group_no', 'p.title', 'p.session', 'p.category', 'u.full_name as leader_name');
        if ($search !== '') {
            $projects->where(function ($query) use ($search) {
                $query->where('p.title', 'like', '%'.$search.'%')->orWhere('u.full_name', 'like', '%'.$search.'%');
            });
        }
        $projects = $projects->orderBy('p.project_group_no')->limit(100)->get();
        $members = $projects->isEmpty() ? collect() : DB::table('project_members as pm')
            ->join('users as u', 'u.id', '=', 'pm.student_id')
            ->whereIn('pm.project_id', $projects->pluck('id'))
            ->orderBy('pm.member_order')->select('pm.project_id', 'u.full_name', 'u.matric_no', 'pm.role')->get()->groupBy('project_id');

        return view('portal.student-groups', compact('projects', 'search', 'members'));
    }

    public function studentArchive(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $session = trim((string) $request->query('session', ''));
        $sessions = DB::table('projects')->whereNotNull('session')->where('session', '<>', '')->distinct()->orderBy('session')->pluck('session');
        $projects = DB::table('projects as p')->leftJoin('users as u', 'u.id', '=', 'p.student_id')
            ->select('p.id', 'p.project_group_no', 'p.title', 'p.description', 'p.session', 'p.category', 'u.full_name as leader_name');
        if ($search !== '') {
            $projects->where(function ($query) use ($search) {
                $query->where('p.title', 'like', '%'.$search.'%')->orWhere('p.description', 'like', '%'.$search.'%');
            });
        }
        if ($session !== '') {
            $projects->where('p.session', $session);
        }
        $projects = $projects->orderBy('p.project_group_no')->limit(100)->get();

        return view('portal.student-archive', compact('projects', 'search', 'session', 'sessions'));
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

        $members = $this->supervisorProjectMembers($projects->pluck('id'));
        $deadlines = DB::table('submission_deadlines')->orderBy('due_date')->orderBy('id')->get();

        return view('portal.supervisor', compact('students', 'projects', 'documents', 'members', 'deadlines'));
    }

    public function supervisorProjects(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $projects = $this->supervisorProjectQuery()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('p.title', 'like', '%'.$search.'%')
                        ->orWhere('p.session', 'like', '%'.$search.'%')
                        ->orWhere('u.full_name', 'like', '%'.$search.'%')
                        ->orWhereExists(function ($members) use ($search) {
                            $members->selectRaw('1')->from('project_members as pm')
                                ->join('users as member', 'member.id', '=', 'pm.student_id')
                                ->whereColumn('pm.project_id', 'p.id')
                                ->where(function ($memberFilter) use ($search) {
                                    $memberFilter->where('member.full_name', 'like', '%'.$search.'%')
                                        ->orWhere('member.matric_no', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->orderByRaw('CASE WHEN p.project_group_no IS NULL THEN 1 ELSE 0 END')
            ->orderBy('p.project_group_no')->orderBy('p.id')->get();
        $members = $this->supervisorProjectMembers($projects->pluck('id'));

        return view('portal.supervisor-projects', compact('projects', 'members', 'search'));
    }

    public function supervisorStudents(): View
    {
        $supervisorId = auth()->id();
        $students = DB::table('supervisor_students as ss')->join('users as u', 'u.id', '=', 'ss.student_id')
            ->where('ss.supervisor_id', $supervisorId)
            ->select('u.id', 'u.full_name', 'u.matric_no', 'u.department', 'u.program_name', 'ss.session')
            ->selectSub(DB::table('student_demo_status')->select('status')->whereColumn('student_id', 'u.id')
                ->where('supervisor_id', $supervisorId)->where('demo_type', 'Demo 1')->limit(1), 'demo1_status')
            ->selectSub(DB::table('student_demo_status')->select('status')->whereColumn('student_id', 'u.id')
                ->where('supervisor_id', $supervisorId)->where('demo_type', 'Demo 2')->limit(1), 'demo2_status')
            ->orderBy('u.full_name')->get();

        return view('portal.supervisor-students', compact('students'));
    }

    public function supervisorStudentMilestones(int $student): View
    {
        $record = DB::table('supervisor_students as ss')->join('users as u', 'u.id', '=', 'ss.student_id')
            ->where('ss.supervisor_id', auth()->id())->where('ss.student_id', $student)
            ->select('u.id', 'u.full_name', 'u.matric_no', 'ss.session')->first();
        abort_unless($record, 404);
        $verification = DB::table('student_demo_status')->where('supervisor_id', auth()->id())
            ->where('student_id', $student)->pluck('status', 'demo_type');

        return view('portal.supervisor-milestones', ['student' => $record, 'verification' => $verification]);
    }

    public function updateSupervisorStudentMilestones(Request $request, int $student): RedirectResponse
    {
        $data = $request->validate([
            'demo_1' => ['required', Rule::in(['Pending', 'Passed', 'Not Passed'])],
            'demo_2' => ['required', Rule::in(['Pending', 'Passed', 'Not Passed'])],
        ]);
        $assigned = DB::table('supervisor_students')->where('supervisor_id', auth()->id())
            ->where('student_id', $student)->exists();
        abort_unless($assigned, 404);

        foreach (['Demo 1' => $data['demo_1'], 'Demo 2' => $data['demo_2']] as $demo => $status) {
            DB::table('student_demo_status')->updateOrInsert(
                ['supervisor_id' => auth()->id(), 'student_id' => $student, 'demo_type' => $demo],
                ['status' => $status, 'updated_at' => now()],
            );
        }

        return redirect()->route('supervisor.students.milestones', $student)->with('status', 'Milestone verification saved.');
    }

    public function supervisorArchive(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $session = trim((string) $request->query('session', ''));
        $categories = DB::table('projects')->whereNotNull('category')->where('category', '<>', '')
            ->distinct()->orderBy('category')->pluck('category');
        $sessions = DB::table('projects')->whereNotNull('session')->where('session', '<>', '')
            ->distinct()->orderBy('session')->pluck('session');
        $projectsQuery = DB::table('projects as p')->leftJoin('users as leader', 'leader.id', '=', 'p.student_id')
            ->leftJoin('users as supervisor', 'supervisor.id', '=', 'p.supervisor_id')
            ->select('p.id', 'p.project_group_no', 'p.title', 'p.description', 'p.session', 'p.category',
                'leader.full_name as leader_name', 'supervisor.full_name as supervisor_name');
        if ($search !== '') {
            $projectsQuery->where(function ($query) use ($search) {
                $query->where('p.title', 'like', '%'.$search.'%')
                    ->orWhere('p.description', 'like', '%'.$search.'%')
                    ->orWhere('leader.full_name', 'like', '%'.$search.'%');
            });
        }
        if ($category !== '') {
            $projectsQuery->where('p.category', $category);
        }
        if ($session !== '') {
            $projectsQuery->where('p.session', $session);
        }
        $projects = $projectsQuery->orderBy('p.project_group_no')->limit(100)->get();

        return view('portal.supervisor-archive', compact('projects', 'categories', 'sessions', 'search', 'category', 'session'));
    }

    public function supervisorDocuments(): View
    {
        $projects = $this->supervisorProjectQuery()->orderBy('p.project_group_no')->orderBy('p.id')->get();

        return view('portal.supervisor-documents', compact('projects'));
    }

    public function supervisorProjectDocuments(int $project): View
    {
        $projectRecord = $this->supervisorProjectQuery()->where('p.id', $project)->first();
        abort_unless($projectRecord, 404);
        $documents = DB::table('project_documents')->where('project_id', $project)
            ->orderByDesc('uploaded_at')->orderBy('id')->get();

        return view('portal.supervisor-project-documents', compact('projectRecord', 'documents'));
    }

    public function viewSupervisorDocument(int $project, int $document)
    {
        $record = DB::table('project_documents as d')->join('projects as p', 'p.id', '=', 'd.project_id')
            ->where('p.id', $project)->where('p.supervisor_id', auth()->id())->where('d.id', $document)
            ->select('d.*')->first();
        abort_unless($record, 404);

        $filename = $record->original_name ?: basename($record->file_path);
        if (Storage::disk('local')->exists($record->file_path)) {
            return Storage::disk('local')->response($record->file_path, $filename, [], 'inline');
        }

        $legacyRoot = realpath(base_path('legacy/uploads/documents'));
        $legacyFile = $legacyRoot ? realpath($legacyRoot.DIRECTORY_SEPARATOR.basename($record->file_path)) : false;
        abort_unless($legacyRoot && $legacyFile && str_starts_with($legacyFile, $legacyRoot.DIRECTORY_SEPARATOR) && is_file($legacyFile), 404);

        return response()->file($legacyFile, [
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_INLINE,
                $filename,
            ),
        ]);
    }

    public function supervisorDeadlines(): View
    {
        $deadlines = DB::table('submission_deadlines')->orderBy('due_date')->orderBy('id')->get()
            ->sortBy(fn ($deadline) => ($deadline->due_date === null || str_starts_with($deadline->due_date, '0000-00-00') ? '9999-12-31' : $deadline->due_date).sprintf('%010d', $deadline->id))
            ->values();

        return view('portal.supervisor-deadlines', compact('deadlines'));
    }

    public function supervisorUpdateDeadline(Request $request, int $deadline): RedirectResponse
    {
        $data = $request->validate(['due_date' => ['required', 'date']]);
        $exists = DB::table('submission_deadlines')->where('id', $deadline)->exists();
        abort_unless($exists, 404);
        DB::table('submission_deadlines')->where('id', $deadline)->update([
            'due_date' => \Illuminate\Support\Carbon::parse($data['due_date'])->format('Y-m-d H:i:s'),
        ]);

        return back()->with('status', 'Submission deadline date updated.');
    }

    private function supervisorProjectQuery()
    {
        return DB::table('projects as p')->leftJoin('users as u', 'u.id', '=', 'p.student_id')
            ->where('p.supervisor_id', auth()->id())
            ->select('p.*', 'u.full_name as leader_name');
    }

    private function supervisorProjectMembers($projectIds)
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return DB::table('project_members as pm')->join('users as u', 'u.id', '=', 'pm.student_id')
            ->whereIn('pm.project_id', $projectIds)->select('pm.project_id', 'pm.role', 'pm.member_order', 'u.full_name', 'u.matric_no')
            ->orderBy('pm.member_order')->get()->groupBy('project_id');
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
        ];
        $statusCounts = ['Draft' => 0, 'Submitted' => 0, 'Approved' => 0];
        foreach (DB::table('projects')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->get() as $status) {
            $normalized = strtolower(trim((string) $status->status));
            $bucket = match (true) {
                str_contains($normalized, 'draft') => 'Draft',
                str_contains($normalized, 'approv') => 'Approved',
                str_contains($normalized, 'submit'), str_contains($normalized, 'pending') => 'Submitted',
                default => null,
            };
            if ($bucket !== null) {
                $statusCounts[$bucket] += (int) $status->total;
            }
        }
        $rankings = DB::table('projects as p')
            ->join('panel_evaluations as pe', 'pe.project_id', '=', 'p.id')
            ->join('panel_student_marks as psm', 'psm.panel_evaluation_id', '=', 'pe.id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')
            ->select('p.id', 'p.project_group_no', 'p.title')
            ->selectRaw('AVG(psm.total_score) as average_score, AVG(psm.demo3_score) as average_demo3_score')
            ->groupBy('p.id', 'p.project_group_no', 'p.title')
            ->orderByDesc('average_score')->orderByDesc('average_demo3_score')->limit(5)->get();
        $panelChoices = DB::table('projects as p')->leftJoin('users as leader', 'leader.id', '=', 'p.student_id')
            ->where('p.department', 'JTMK')->where('p.course_code', 'DFT50114')->where('p.is_panel_choice', 1)
            ->select('p.id', 'p.project_group_no', 'p.title', 'p.session', 'p.category', 'leader.full_name as leader_name')
            ->orderBy('p.project_group_no')->get();

        return view('portal.admin', compact('stats', 'statusCounts', 'rankings', 'panelChoices'));
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
