<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentProjectNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_a_project_sees_it_in_navigation_and_cannot_register_another(): void
    {
        $student = User::query()->create([
            'username' => 'existing_project_student',
            'full_name' => 'Existing Project Student',
            'email' => 'existing-project@example.test',
            'ic_number' => '060319111234',
            'password' => bcrypt('test-password'),
            'role' => 'Student',
        ]);

        $projectId = DB::table('projects')->insertGetId([
            'created_by' => $student->id,
            'student_id' => $student->id,
            'title' => 'Existing Project',
            'category' => 'Web application',
            'session' => '2026',
            'description' => 'An existing student project.',
        ]);
        DB::table('project_members')->insert([
            'project_id' => $projectId,
            'student_id' => $student->id,
            'role' => 'Leader',
        ]);

        $this->actingAs($student)->get('/student/dashboard')
            ->assertOk()
            ->assertSee('My Project')
            ->assertSee('Not Evaluated Yet')
            ->assertSee('Document Submission Status')
            ->assertDontSee('Current marks')
            ->assertDontSee('Register Project');

        $this->get('/student/documents')->assertOk()->assertSee('Upload Form')->assertSee('Submitted Documents List');
        $this->get('/student/project')->assertOk()->assertSee('My Project Information')->assertSee('Existing Project');
        $this->get('/student/milestones')->assertOk()->assertSee('Milestone Verification Status')->assertSee('Pending');
        $this->get('/student/deadlines')->assertOk()->assertSee('Document Submission Deadline Reminders');

        $this->get('/student/projects/create')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('status');

        $this->post('/student/projects', [
            'title' => 'Another Project',
            'category' => 'Web application',
            'description' => 'Should not be created.',
            'session' => '2026',
        ])->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseCount('projects', 1);
    }
}
