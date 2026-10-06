@extends('layouts.app')
@section('title', 'Manage JTMK Users')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-users.css') }}?v=1">
<div class="admin-users-page">
    <div class="page-heading admin-users-heading">
        <div><span class="eyebrow">ADMINISTRATION</span><h1><i class="fa-solid fa-users"></i> Manage JTMK Users</h1><p>Admin-only account management. Student imports are limited to the JTMK department.</p></div>
        <a class="button secondary" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="users-overview">
        <section class="student-count-card"><div><span>JTMK Students</span><strong>{{ $studentCount }}</strong></div><i class="fa-solid fa-graduation-cap"></i><div class="count-decoration"></div></section>
        <section class="users-panel find-student-panel">
            <h2><i class="fa-solid fa-magnifying-glass"></i> Find Student</h2>
            <label for="student-search">Search by student name, IC number, or matric number</label>
            <input id="student-search" type="search" autocomplete="off" placeholder="Type at least 2 characters..." aria-describedby="student-search-status">
            <p id="student-search-status" aria-live="polite">Search results will appear here.</p>
            <div id="student-search-results" hidden></div>
        </section>
    </div>

    <section class="users-panel import-panel">
        <h2><i class="fa-solid fa-file-arrow-up"></i> Import Student JTMK</h2>
        <p>Upload a CSV or XLSX with Name, IC No, Matric No, Session and Department columns. Only rows marked JTMK are imported; other departments are ignored.</p>
        <div class="import-note"><strong>Account setup:</strong> imported students use their IC number as their initial password. Passwords are stored as secure hashes; students should change the initial password after signing in.</div>
        <form class="import-form" method="post" action="{{ route('admin.users.import-students') }}" enctype="multipart/form-data">
            @csrf
            <label>Student data file<input type="file" name="student_file" accept=".csv,.xlsx" required></label>
            <button class="button primary" type="submit"><i class="fa-solid fa-upload"></i> Import Students</button>
        </form>
    </section>

    <section class="users-panel student-directory">
        <div class="users-section-title"><h2><i class="fa-solid fa-graduation-cap"></i> Student JTMK</h2><span>{{ $studentCount }} accounts</span></div>
        <div class="users-table-wrap"><table class="users-table"><thead><tr><th>Name</th><th>I/C No</th><th>Matric No</th><th>Email</th><th>Academic Session</th><th>Action</th></tr></thead><tbody>
        @forelse($studentsBySession as $session => $students)
            <tr class="session-row"><th colspan="6" scope="rowgroup">{{ $session }} <span>{{ $students->count() }}</span></th></tr>
            @foreach($students as $student)
                <tr>
                    <td class="student-name">{{ $student->full_name }}</td><td>{{ $student->ic_number }}</td><td>{{ $student->matric_no ?: '—' }}</td><td>{{ $student->email }}</td><td>{{ $student->academic_session ?: '—' }}</td>
                    <td class="user-actions">
                        <button class="button outline compact" type="button" data-edit-student data-id="{{ $student->id }}" data-name="{{ $student->full_name }}" data-ic="{{ $student->ic_number }}" data-matric="{{ $student->matric_no }}" data-email="{{ $student->email }}" data-session="{{ $student->academic_session }}"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                        @if((int)$student->id !== (int)auth()->id())<form method="post" action="{{ route('admin.users.delete', $student->id) }}" onsubmit="return confirm('Delete this student account?')">@csrf @method('DELETE')<button class="button danger compact" type="submit"><i class="fa-solid fa-trash"></i> Delete</button></form>@endif
                    </td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="6" class="empty-row">No JTMK student accounts yet. Import a roster above to get started.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>

    <section class="users-panel supervisor-directory">
        <div class="users-section-title"><h2><i class="fa-solid fa-address-card"></i> Lecturers / Supervisors</h2><span>{{ $supervisors->count() }} accounts</span></div>
        <div class="users-table-wrap"><table class="users-table"><thead><tr><th>Name</th><th>I/C No</th><th>Email</th></tr></thead><tbody>
        @forelse($supervisors as $supervisor)<tr><td class="student-name">{{ $supervisor->full_name }}</td><td>{{ $supervisor->ic_number }}</td><td>{{ $supervisor->email }}</td></tr>
        @empty<tr><td colspan="3" class="empty-row">No JTMK supervisors found.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <details class="users-panel account-tools">
        <summary>Additional account tools</summary>
        <p>Create individual accounts for staff, panel members or a single student.</p>
        <form class="account-create-form" method="post" action="{{ route('admin.users.store') }}">@csrf
            <label>Full name<input name="full_name" required maxlength="100"></label><label>Email<input name="email" type="email" required maxlength="100"></label><label>IC number<input name="ic_number" required maxlength="30"></label><label>Matric number<input name="matric_no" maxlength="30"></label><label>Role<select name="role"><option>Student</option><option>Supervisor</option><option>Panel</option><option>Admin</option></select></label><button class="button primary">Create account</button>
        </form>
    </details>
</div>

<dialog class="student-edit-dialog" id="student-edit-dialog">
    <form method="post" id="student-edit-form">@csrf @method('PATCH')
        <div class="dialog-heading"><div><span class="eyebrow">JTMK STUDENT</span><h2>Edit student</h2></div><button type="button" class="dialog-close" aria-label="Close">&times;</button></div>
        <label>Full name<input name="full_name" id="edit-full-name" required maxlength="100"></label>
        <label>IC number<input name="ic_number" id="edit-ic-number" required maxlength="30"></label>
        <label>Matric number<input name="matric_no" id="edit-matric-no" required maxlength="30"></label>
        <label>Email<input name="email" id="edit-email" type="email" required maxlength="100"></label>
        <label>Academic session<input name="academic_session" id="edit-session" maxlength="50" placeholder="e.g. I : 2026/2027"></label>
        <label class="reset-password"><input type="checkbox" name="reset_password" value="1"> Reset password to the student’s IC number</label>
        <input type="hidden" name="role" value="Student">
        <div class="dialog-actions"><button class="button secondary" type="button" data-close-dialog>Cancel</button><button class="button primary" type="submit">Save changes</button></div>
    </form>
</dialog>

<script>
(() => {
    const input = document.getElementById('student-search');
    const status = document.getElementById('student-search-status');
    const output = document.getElementById('student-search-results');
    let timer;
    let controller;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        const query = input.value.trim();
        output.replaceChildren(); output.hidden = true;
        if (query.length < 2) { status.textContent = 'Type at least 2 characters to find a JTMK student.'; return; }
        status.textContent = 'Searching students…';
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const response = await fetch('{{ route('admin.users.search-students') }}?q=' + encodeURIComponent(query), {headers: {'Accept': 'application/json'}, signal: controller.signal});
                if (!response.ok) throw new Error('Student search is unavailable.');
                const {results} = await response.json();
                if (!results.length) { status.textContent = 'No JTMK students match that search.'; return; }
                const table = document.createElement('table'); table.className = 'users-table search-table';
                const head = table.createTHead().insertRow();
                ['Name', 'Matric No', 'I/C No', 'Session', 'Email', 'Action'].forEach(label => { const th = document.createElement('th'); th.textContent = label; head.appendChild(th); });
                const body = table.createTBody();
                results.forEach(student => {
                    const row = body.insertRow();
                    [student.full_name, student.matric_no || '—', student.ic_number, student.academic_session || '—', student.email].forEach(value => { row.insertCell().textContent = value || '—'; });
                    const action = row.insertCell(); const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'button outline compact'; edit.innerHTML = '<i class="fa-regular fa-pen-to-square"></i> Edit';
                    edit.addEventListener('click', () => openEditor(student)); action.appendChild(edit);
                });
                output.appendChild(table); output.hidden = false;
                status.textContent = results.length === 20 ? 'Showing the first 20 matches. Refine your search.' : `${results.length} matching student${results.length === 1 ? '' : 's'}.`;
            } catch (error) { if (error.name !== 'AbortError') status.textContent = error.message; }
        }, 220);
    });

    const dialog = document.getElementById('student-edit-dialog');
    const editForm = document.getElementById('student-edit-form');
    function openEditor(student) {
        editForm.action = '{{ url('/admin/users') }}/' + student.id;
        document.getElementById('edit-full-name').value = student.full_name || '';
        document.getElementById('edit-ic-number').value = student.ic_number || '';
        document.getElementById('edit-matric-no').value = student.matric_no || '';
        document.getElementById('edit-email').value = student.email || '';
        document.getElementById('edit-session').value = student.academic_session || '';
        editForm.querySelector('[name="reset_password"]').checked = false;
        dialog.showModal();
    }
    document.querySelectorAll('[data-edit-student]').forEach(button => button.addEventListener('click', () => openEditor(button.dataset)));
    dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
})();
</script>
@endsection
