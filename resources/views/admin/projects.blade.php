@extends('layouts.app')
@section('title', 'Manage projects')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">ADMINISTRATION</span><h1>Projects</h1><p>Review project registrations and update their workflow status.</p></div>
</div>

<section class="panel-card">
    <div class="table-scroll">
        <table>
            <thead><tr><th>Group</th><th>Project</th><th>Leader</th><th>Category and session</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($projects as $project)
                    <tr>
                        <td>{{ $project->project_group_no ? 'Group '.$project->project_group_no : '-' }}</td>
                        <td><strong>{{ $project->title }}</strong><small class="table-sub">{{ $project->project_number ?: 'No project number' }}</small></td>
                        <td>{{ $project->leader_name }}</td>
                        <td>{{ $project->category }}<small class="table-sub">{{ $project->session }}</small></td>
                        <td><form class="inline-form" method="post" action="{{ route('admin.projects.update', $project->id) }}">@csrf @method('PATCH')<select name="status"><option @selected($project->status==='Draft')>Draft</option><option @selected($project->status==='Submitted')>Submitted</option><option @selected($project->status==='Approved')>Approved</option></select><button class="button compact secondary">Save</button></form></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $projects->links() }}
</section>
@endsection
