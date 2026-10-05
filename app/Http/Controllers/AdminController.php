<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function users(Request $request): View
    {
        $query = User::query()->orderBy('role')->orderBy('full_name');
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('ic_number', 'like', "%{$search}%")
                    ->orWhere('matric_no', 'like', "%{$search}%");
            });
        }

        return view('admin.users', ['users' => $query->paginate(25)->withQueryString()]);
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
            'matric_no' => ['nullable', 'string', 'max:30'],
        ]);
        if ($account->is(auth()->user()) && $data['role'] !== 'Admin') {
            return back()->withErrors(['role' => 'You cannot remove your own administrator access.']);
        }
        $account->fill($data)->save();

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
        $projects = DB::table('projects as p')->leftJoin('users as u', 'u.id', '=', 'p.student_id')
            ->select('p.*', 'u.full_name as leader_name')
            ->orderByRaw('CASE WHEN p.project_group_no IS NULL THEN 1 ELSE 0 END')
            ->orderBy('p.project_group_no')->orderBy('p.id')->paginate(25);

        return view('admin.projects', compact('projects'));
    }

    public function updateProject(Request $request, int $project): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['Draft', 'Submitted', 'Approved'])]]);
        DB::table('projects')->where('id', $project)->update(['status' => $data['status']]);

        return back()->with('status', 'Project status updated.');
    }

    public function deadlines(): View
    {
        return view('admin.deadlines', ['deadlines' => DB::table('submission_deadlines')->orderBy('due_date')->get()]);
    }

    public function storeDeadline(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'due_date' => ['nullable', 'date']]);
        $data['due_date'] = filled($data['due_date']) ? \Illuminate\Support\Carbon::parse($data['due_date'])->format('Y-m-d H:i:s') : null;
        DB::table('submission_deadlines')->insert([...$data, 'created_at' => now()]);

        return back()->with('status', 'Deadline added.');
    }

    public function updateDeadline(Request $request, int $deadline): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'due_date' => ['nullable', 'date']]);
        $data['due_date'] = filled($data['due_date']) ? \Illuminate\Support\Carbon::parse($data['due_date'])->format('Y-m-d H:i:s') : null;
        DB::table('submission_deadlines')->where('id', $deadline)->update($data);

        return back()->with('status', 'Deadline updated.');
    }

    public function deleteDeadline(int $deadline): RedirectResponse
    {
        DB::table('submission_deadlines')->where('id', $deadline)->delete();

        return back()->with('status', 'Deadline removed.');
    }

    public function settings(): View
    {
        $settings = DB::table('system_settings')->orderBy('setting_key')->get();

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string', 'max:5000']]);
        foreach ($data['settings'] as $key => $value) {
            DB::table('system_settings')->updateOrInsert(['setting_key' => (string) $key], ['setting_value' => $value]);
        }

        return back()->with('status', 'System settings saved.');
    }

    public function reports(): View
    {
        $byCategory = DB::table('projects')->select('category', DB::raw('COUNT(*) AS project_count'))->groupBy('category')->orderByDesc('project_count')->get();
        $byStatus = DB::table('projects')->select('status', DB::raw('COUNT(*) AS project_count'))->groupBy('status')->get();
        $topMarks = DB::table('projects as p')->leftJoin('project_marks as m', 'm.project_id', '=', 'p.id')
            ->select('p.title', 'p.session', 'm.total_score')->orderByDesc('m.total_score')->limit(20)->get();

        return view('admin.reports', compact('byCategory', 'byStatus', 'topMarks'));
    }
}
